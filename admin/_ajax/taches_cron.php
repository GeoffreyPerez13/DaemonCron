<?php

/* * *
 * @author Geoffrey
 */

// Inclusion des paramètres globaux (Classes, Session, Accès Opérateur,...)
require_once("_inc/headerPhp.php");
include_once("_inc/fonctions/validation.php");

$monObjetWarning = Warning::getInstance();

// Vérifier si l'opérateur est identifié
if (!$session->has("operateurGalaxy")) {
    $monObjetWarning->setMessage("ERROR", "", "<strong>" . Text::_("GM_VOTRE_SESSION_A_EXPIREE") . "</strong>", false);
    echo json_encode($monObjetWarning->getAllMessageJSON());
    exit();
}

$operateurGalaxy = $session->get("operateurGalaxy");

// Récupération des paramètres de pagination pour DataTables
$draw = (int) getVar("draw", 0, "request"); // Contrôle de la synchronisation des requêtes
$start = (int) getVar("start", 0, "request"); // Index de départ pour la pagination
$length = (int) getVar("length", 10, "request"); // Nombre de lignes à afficher par page

$statutTacheCron = AdminTacheCron::getLibellesStatutTacheCron(); // Récupérer les libellés des statuts des tâche cron
$tachesCronExistantes = AmobService::getInstance()->getAdminTacheCronService()->rechercherToutesLesTachesCron(); // Récupérer toutes les tâches cron

// Initialisation de la structure de réponse pour DataTables
$records = array(); // Tableau pour contenir les données
$records["data"] = array();
$totalRecords = count($tachesCronExistantes); // Nombre total de produits dans l'hôtel
$filteredRecords = array_slice($tachesCronExistantes, $start, $length); // Règles automatiques pour la page actuelle

// Tableau pour traduire les codes des jours en noms complets
$joursLibelle = [
    'mon' => Text::_("GM_LUNDI"),
    'tue' => Text::_("GM_MARDI"),
    'wed' => Text::_("GM_MERCREDI"),
    'thu' => Text::_("GM_JEUDI"),
    'fri' => Text::_("GM_VENDREDI"),
    'sat' => Text::_("GM_SAMEDI"),
    'sun' => Text::_("GM_DIMANCHE")
];

// Préparer les lignes de données pour chaque tâche cron
foreach ($tachesCronExistantes as $tacheCron) {
    $joursArray = array_map('trim', explode(',', $tacheCron->periode_lancement)); // Découper la chaîne des jours en tableau et enlever les espaces éventuels autour de chaque élément
    $periodeLancementTexte = []; // Initialiser un tableau vide qui contiendra les noms complets des jours
    $tousLesJours = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']; // Tableau des 7 jours
    $estModeMois = false; // Vérifier si la période correspond à des jours de semaine ou à des jours du mois

    // Pour chaque élément de periode_lancement récupéré
    foreach ($joursArray as $valeur) {
        // Si leur valeur n'est pas numérique
        if (!is_numeric($valeur)) {
            $estModeMois = false; // On est pas dans le type "mois"
            break;
        }
        $estModeMois = true; // Sinon on est bien dans le type "mois"
    }

    // Sin on est dans le type "mois"
    if ($estModeMois) {
        // Encodage du texte à afficher
        $periodeLancementTexte = Text::_("GM_TOUS_LES") . ' ' . htmlspecialchars(implode(', ', $joursArray), ENT_QUOTES, ini_get("default_charset")) . ' ' . Text::_("GM_DU_MOIS");
    } else {
        // Sinon, si on est en mode "semaine" et que tous les jours sont présents
        if (count(array_diff($tousLesJours, $joursArray)) === 0) {
            $periodeLancementTexte = Text::_("GM_TOUS_LES_JOURS"); // Afficher le texte correspondant
        } else {
            // Sinon, parcourir chaque code de jour pour le transformer en nom complet
            foreach ($joursArray as $jour) {
                // Vérifier si le code du jour existe dans notre tableau de correspondance
                if (isset($joursLibelle[$jour])) {
                    $periodeLancementTexte[] = $joursLibelle[$jour]; // Ajouter le nom complet correspondant dans le tableau final
                }
            }
            // Transformer le tableau des noms de jours en une chaîne lisible, séparée par des virgules
            $periodeLancementTexte = implode(', ', $periodeLancementTexte);
        }
    }
    // Mise en forme des heures de lancement avec les heures exécutées (exactes ou dans les 10 min) en vert
    $heuresPrevues   = array_map('trim', explode(',', $tacheCron->heure_lancement)); // Heures prévues
    $heuresExecutees = $tacheCron->execution_tache ? array_map('trim', explode(',', $tacheCron->execution_tache)) : []; // Heures réellement exécutées
    $heuresAffichees = []; // Tableau qui contiendra le HTML pour chaque heure

    // Pour chaque heure prévu dans les heures de lancement
    foreach ($heuresPrevues as $heurePrevue) {
        $prevueTimestamp = strtotime($heurePrevue); // Convertit l'heure prévue en timestamp
        $estExecutee = false; // Par défaut on considère que l'heure prévue n'a pas encore été exécutée

        // Pour chaque heure exécutée
        foreach ($heuresExecutees as $heureExec) {
            $execTimestamp = strtotime($heureExec); // Convertit l'heure d'exécution réelle en timestamp

            // On vérifie si l'heure courante est dans la plage pour exécuter la tâche (+10 min)
            if ($execTimestamp >= $prevueTimestamp && $execTimestamp <= $prevueTimestamp + 600) {
                $estExecutee = true; // Marque l'heure prévue comme exécutée
                break; // Sort de la boucle dès qu'une exécution valide est trouvée
            }
        }
        // Si l'heure prévue a été exécutée
        if ($estExecutee) {
            // On l'affiche en vert et en gras pour signaler qu'elle est validée
            $heuresAffichees[] = '<span class="text-success fw-bold">' . htmlspecialchars($heurePrevue, ENT_QUOTES, ini_get("default_charset")) . '</span>';
        } else {
            // Sinon on l'affiche normalement
            $heuresAffichees[] = htmlspecialchars($heurePrevue, ENT_QUOTES, ini_get("default_charset"));
        }
    }

    // Recomposer la chaîne avec séparateur
    $heureLancementHtml = implode(' &middot; ', $heuresAffichees);

    // Récupérer les différents état d'une tâche cron
    $etatClasses = [
        AdminTacheCron::STATUT_A_VENIR => "label-info",
        AdminTacheCron::STATUT_EN_COURS => "label-warning",
        AdminTacheCron::STATUT_TERMINE => "label-success"
    ];

    // Afficher le bon visuel en fonction de l'état de la tâche
    $etatTacheCron = '<div class="label label-sm ' . $etatClasses[$tacheCron->etat] . '">' . $statutTacheCron[$tacheCron->etat] . '</div>';

    // Afficher les boutons d'action de la colonne action
    $action = '
        <a href="/admin/tableau-bord/tache-cron-form.php?id=' . $tacheCron->id . '" data-tooltip="tooltip" data-placement="top" data-html="true" data-container="body" data-original-title="' . Text::_("GM_MODIFIER") . '"><span class="label label-sm label-info"><i class="fas fa-pencil"></i></span></a>
        &nbsp;
        <a target="_blank" href="/admin/tableau-bord/modal/tache-cron-supprimer.php?id=' . $tacheCron->id . '" data-toggle="modal" data-target="#modal-supprimer-tache-cron" data-tooltip="tooltip" data-placement="top" data-html="true" data-container="body" data-original-title="' . Text::_("GM_SUPPRIMER") . '"><span class="label label-sm label-danger"><i class="fa fa-trash-alt"></i></span></a>
        &nbsp;
    ';

    $row = array(
        "id" => $tacheCron->id,
        "libelle" => $tacheCron->cron_class,
        "description" => $tacheCron->tache_cron,
        "fichier" => "<i>" . htmlentities($tacheCron->file_name, ENT_QUOTES, ini_get("default_charset")) . "</i>",
        "periode_lancement" => $periodeLancementTexte, // Affichage des jours complets ou "Tous les jours" / mois
        "heure_lancement" => $heureLancementHtml, // Heures exécutées (exactes ou +10 min) en vert
        "etat" => $etatTacheCron,
        "action" => $action
    );

    $records["data"][] = $row;
}

// Ajouter les informations pour DataTables
$records["draw"] = $draw;
$records["recordsTotal"] = $totalRecords;
$records["recordsFiltered"] = $totalRecords;

// Retourner le JSON
echo json_encode($records);
