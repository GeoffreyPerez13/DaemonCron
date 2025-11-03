<?php

/* * *
 * @author Geoffrey
 */

include_once( "_inc/headerPhp.php");
include_once( "_inc/fonctions/validation.php");

class TachesCronAction extends AmobAction {

    // Page de redirection
    const REDIRECT_TACHES_CRON = "/admin/tableau-bord/taches-cron.php";
    const REDIRECT_TACHE_CRON_FORM = "/admin/tableau-bord/tache-cron-form.php";

    // Ajouter une tâche cron
    function ajouter() {

        $monObjetWarning = Warning::getInstance();

        // ENREGISTREMENT EN BASE
        $maConnexionSql = Sql::getInstance();
        $maConnexionSql->beginTransaction();

        // Vérification des champs obligatoires
        if (!champObligatoire("tache_cron") || trim((string) $_POST['tache_cron']) === '')
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_CHAMP_TACHE_CRON_OBLIGATOIRE"));
        if (!champObligatoire("periode_lancement") || trim((string) $_POST['periode_lancement']) === '')
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_CHAMP_PERIODE_LANCEMENT_OBLIGATOIRE"));
        if (!champObligatoire("heure_lancement") || !isset($_POST['heure_lancement']) || !is_array($_POST['heure_lancement']) || in_array('', array_map('trim', $_POST['heure_lancement']), true))
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_CHAMP_HEURE_DE_LANCEMENT_OBLIGATOIRE"));
        if (count($monObjetWarning->getError()) > 0)
            $monObjetWarning->redirect(self::REDIRECT_TACHE_CRON_FORM, null);

        // Nouvelle tâche cron
        $tacheCron = new AdminTacheCron();
        $tacheCron->tache_cron = getVar("tache_cron", "");

        // Récupération de la période de lancement
        $periodeLancement = is_array(getVar("periode_lancement", array())) ? getVar("periode_lancement", array()) : getVar("periode_lancement", array()); // Convertir le tableau en chaîne
        $estModeMois = true; // Définir le flag du type de période de lancement à "mois"
        
        // Pour chaque élément de periode_lancement récupéré
        foreach ($periodeLancement as $valeur) {
            // Si leur valeur n'est pas numérique
            if (!is_numeric($valeur)) {
                $estModeMois = false; // On est pas dans le type "mois"
                break;
            }
        }
        // Si on est mode "mois"
        if ($estModeMois) {
            $periodeLancement = array_map('intval', $periodeLancement); // Convertir en entiers
            $periodeLancement = array_unique($periodeLancement); // Supprimer les doublons
            sort($periodeLancement); // Tri croissant
            
            $tacheCron->periode_mode = "mois";
        } else {
           $tacheCron->periode_mode = "semaine"; 
        }
        
        $tacheCron->periode_lancement = implode(',', $periodeLancement);
        
        // Tri des heures de lancement
        $heuresLancement = is_array(getVar("heure_lancement", array())) ? getVar("heure_lancement", array()) : []; // Convertir le tableau en chaîne
        $heuresLancement = array_filter(array_map('trim', $heuresLancement), function ($heure) { // Supprime les espaces inutiles au début et à la fin de chaque valeur du tableau $heuresLancement
            return $heure !== ''; // Retire toutes les valeurs vides pour ne conserver que des heures valides
        });
        $heuresLancement = array_unique($heuresLancement); // Supprimer les doublons
        sort($heuresLancement); // Tri par ordre chronologique

        $tacheCron->heure_lancement = implode(',', $heuresLancement); // Stockage en base
        $tacheCron->cron_class = getVar("cron_class", "");
        $tacheCron->file_name = getVar("file_name", "");
        $tacheCron->etat = AdminTacheCron::STATUT_A_VENIR;

        // Rechercher une tâche déjà existante avec le même intitulé
        $tacheCronExistante = AmobService::getInstance()->getAdminTacheCronService()->rechercherTacheCron($tacheCron->tache_cron);

        // Redirection si la tâche cron existe déjà
        if ($tacheCronExistante) {
            $maConnexionSql->rollbackTransaction();
            $maConnexionSql->close_db();
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_LA_TACHE_CRON_EXISTE_DEJA"));
            Warning::getInstance()->redirect(self::REDIRECT_TACHE_CRON_FORM, null);
        }

        // Ajout d'une tâche cron
        $nouvelleTacheCron = AmobService::getInstance()->getAdminTacheCronService()->ajouterTacheCron($tacheCron);

        // Redirection si la tâche n'a pas été ajoutée
        if (!$nouvelleTacheCron || !$nouvelleTacheCron) {
            $maConnexionSql->rollbackTransaction();
            $maConnexionSql->close_db();
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_ERREUR_LORS_DE_L_AJOUT_DE_LA_TACHE_CRON"));
            Warning::getInstance()->redirect(self::REDIRECT_TACHE_CRON_FORM, null);
        }

        $maConnexionSql->commitTransaction();
        $maConnexionSql->close_db();

        $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON, array(Text::_("GM_TACHE_CRON_AJOUTEE")), "OK");
    }

    // Modifier une tâche cron
    function modifier() {

        $monObjetWarning = Warning::getInstance();

        // ENREGISTREMENT EN BASE
        $maConnexionSql = Sql::getInstance();
        $maConnexionSql->beginTransaction();

        // Récupérer l'ID de la tâche cron
        $idTacheCron = (int) getVar("id", 0);

        // Vérification des champs obligatoires
        if (!champObligatoire("tache_cron") || trim((string) $_POST['tache_cron']) === '')
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_CHAMP_TACHE_CRON_OBLIGATOIRE"));
        if (!champObligatoire("periode_lancement") || trim((string) $_POST['periode_lancement']) === '')
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_CHAMP_PERIODE_LANCEMENT_OBLIGATOIRE"));
        if (!champObligatoire("heure_lancement") || !isset($_POST['heure_lancement']) || !is_array($_POST['heure_lancement']) || in_array('', array_map('trim', $_POST['heure_lancement']), true))
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_CHAMP_HEURE_DE_LANCEMENT_OBLIGATOIRE"));
        if (count($monObjetWarning->getError()) > 0)
            $monObjetWarning->redirect(self::REDIRECT_TACHE_CRON_FORM . "?id=" . $idTacheCron, null);

        // Mise à jour de la tâche cron
        $tacheCron = new AdminTacheCron();
        $tacheCron->id = (int) getVar("id", 0);
        $tacheCron->tache_cron = getVar("tache_cron", "");
        
        // Récupération de la période de lancement
        $periodeLancement = is_array(getVar("periode_lancement", array())) ? getVar("periode_lancement", array()) : getVar("periode_lancement", array()); // Convertir le tableau en chaîne
        $estModeMois = true; // Définir le flag du type de période de lancement à "mois"
        
        // Pour chaque élément de periode_lancement récupéré
        foreach ($periodeLancement as $valeur) {
            // Si leur valeur n'est pas numérique
            if (!is_numeric($valeur)) {
                $estModeMois = false; // On est pas dans le type "mois"
                break;
            }
        }
        // Si on est mode "mois"
        if ($estModeMois) {
            $periodeLancement = array_map('intval', $periodeLancement); // Convertir en entiers
            $periodeLancement = array_unique($periodeLancement); // Supprimer les doublons
            sort($periodeLancement); // Tri croissant
            
            $tacheCron->periode_mode = "mois";
        } else {
           $tacheCron->periode_mode = "semaine"; 
        }
        
        $tacheCron->periode_lancement = implode(',', $periodeLancement);
        
        // Tri des heures de lancement
        $heuresLancement = is_array(getVar("heure_lancement", array())) ? getVar("heure_lancement", array()) : []; // Convertir le tableau en tableau PHP
        $heuresLancement = array_filter(array_map('trim', $heuresLancement), function ($heure) { // Supprime les espaces inutiles
            return $heure !== ''; // Retire toutes les valeurs vides
        });
        $heuresLancement = array_unique($heuresLancement); // Supprimer les doublons
        sort($heuresLancement); // Tri par ordre chronologique

        $tacheCron->heure_lancement = implode(',', $heuresLancement); // Stockage en base
        $tacheCron->cron_class = getVar("cron_class", "");
        $tacheCron->file_name = getVar("file_name", "");

        // Charger la tâche actuelle depuis la base (pour comparer l'état et les anciennes heures)
        $tacheCronActuelle = AmobService::getInstance()->getAdminTacheCronService()->rechercherTacheCronId($idTacheCron);

        // Détermination du nouvel état
        $nouvelEtat = getVar("etat", $tacheCronActuelle->etat); // Par défaut, on garde l'état actuel
        // Pour la tache cron actuelle
        if ($tacheCronActuelle) {
            // Anciennes heures (en base)
            $anciennesHeures = !empty($tacheCronActuelle->heure_lancement) ? explode(',', $tacheCronActuelle->heure_lancement) : [];
            sort($anciennesHeures);

            $nouvellesHeures = $heuresLancement; // Récupérer les anciennes heures de lancement pour comparaison
            $heuresAjoutees = array_diff($nouvellesHeures, $anciennesHeures); // Identifier si de nouvelles heures ont été ajoutées
            // Cas 1 - Si l'état était "Terminé" (3)
            if ($tacheCronActuelle->etat == 3) {
                // Si une ou plusieurs heures ont été ajoutées
                if (!empty($heuresAjoutees)) {
                    $nouvelEtat = 2; // On repasse l'état à "En cours" (2)
                }
            }
            // Cas 2 - Si l'état était "En cours" (2)
            elseif ($tacheCronActuelle->etat == 2) {
                if (empty($heuresAjoutees) && !empty($anciennesHeures) && empty(array_diff($nouvellesHeures, $anciennesHeures))) {
                    $nouvelEtat = 3; // On repasse l'état à "Terminé" (3)
                }
            }
        }
        $tacheCron->etat = $nouvelEtat; // Nouvel état de la tâche cron
        
        // Rechercher une tâche déjà existante avec le même intitulé
        $tacheCronExistante = AmobService::getInstance()->getAdminTacheCronService()->rechercherTacheCron($tacheCron->tache_cron);

        // Si la tâche cron existe
        if ($tacheCronExistante) {
            // Rechercher une tâche déjà existante avec le même id
            $tacheCronExistanteAvecId = AmobService::getInstance()->getAdminTacheCronService()->rechercherTacheCronId($idTacheCron);

            // Si la tâche cron avec le même id n'est pas trouvée
            if (!$tacheCronExistanteAvecId) {
                $maConnexionSql->rollbackTransaction();
                $maConnexionSql->close_db();
                $monObjetWarning->setMessage("ERROR", "", Text::_("GM_LA_TACHE_CRON_EXISTE_DEJA"));
                Warning::getInstance()->redirect(self::REDIRECT_TACHE_CRON_FORM . "?id=" . $idTacheCron, null);
            }
        }

        // Modification de la tâche cron
        $modifierTacheCron = AmobService::getInstance()->getAdminTacheCronService()->modifierTacheCron($tacheCron);

        // Redirection si la tâche cron n'a pas pu être mise à jour
        if (!$modifierTacheCron) {
            $maConnexionSql->rollbackTransaction();
            $maConnexionSql->close_db();
            Warning::getInstance()->redirect(self::REDIRECT_TACHE_CRON_FORM . "?id=" . $idTacheCron, null);
        }

        $maConnexionSql->commitTransaction();
        $maConnexionSql->close_db();

        $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON, array(Text::_("GM_TACHE_CRON_MODIFIEE")), "OK");
    }

    // Supprimer une tâche cron
    function supprimer() {

        $monObjetWarning = Warning::getInstance();

        // ENREGISTREMENT EN BASE
        $maConnexionSql = Sql::getInstance();
        $maConnexionSql->beginTransaction();

        // Récupérer l'ID du compte
        $idTacheCron = (int) getVar("id", 0);

        // Vérifier l'id rde la tâche cron récupérée
        if ($idTacheCron == 0 || !$idTacheCron) {
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_TACHE_CRON_INTROUVABLE"));
            $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON);
        }

        // Rechercher une tâche déjà existante la même id
        $tacheCronExistante = AmobService::getInstance()->getAdminTacheCronService()->rechercherTacheCronId($idTacheCron);

        // Vérifier si la tâche cron existe
        if (!$tacheCronExistante) {
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_LA_TACHE_CRON_N_EXISTE_PAS"));
            $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON);
        }

        // Supprimer la tâche cron
        AmobService::getInstance()->getAdminTacheCronService()->supprimerTacheCron($idTacheCron);

        $maConnexionSql->commitTransaction();
        $maConnexionSql->close_db();

        $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON, array(Text::_("GM_TACHE_CRON_SUPPRIMEE")), "OK");
    }

    // Supprimer les tâches cron sélectionnées
    function supprimerTachesSelectionnees() {
        $monObjetWarning = Warning::getInstance();

        // ENREGISTREMENT EN BASE
        $maConnexionSql = Sql::getInstance();
        $maConnexionSql->beginTransaction();

        // Tableau des ID des tâches cron cochées
        $tachesIds = isset($_POST['taches_ids']) ? $_POST['taches_ids'] : array();

        // Si aucune tâche n'est cochée
        if (empty($tachesIds)) {
            $monObjetWarning->setMessage("ERROR", "", Text::_("GM_AUCUNE_TACHE_SELECTIONNEE"));
            $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON);
        }

        // Pour chaque tâche cron
        foreach ($tachesIds as $idTacheCron) {
            // Si la tâche cron existe
            if ((int) $idTacheCron) {
                AmobService::getInstance()->getAdminTacheCronService()->supprimerTacheCron($idTacheCron);
            }
        }

        $maConnexionSql->commitTransaction();
        $maConnexionSql->close_db();

        // Redirection après suppression
        $monObjetWarning->redirect(self::REDIRECT_TACHES_CRON, array(Text::_("GM_TACHES_CRON_SUPPRIMEES")), "OK");
    }
}

$upkeyAction = new TachesCronAction();
$upkeyAction->execute();

