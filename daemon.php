<?php

/**
 * Daemon PHP pour exécuter les tâches planifiées
 * Détecte automatiquement tous les sous-dossiers de _cron et exécute les fichiers PHP qu’ils contiennent
 * Compatible CLI (daemon) et HTTP (ancienne utilisation)
 * @author Geoffrey
 */

// Inclusion du fichier des secrets pour récupérer le jeton et l'IP autorisée et inclusion du fichier de vérification des accès cron
require_once __DIR__ . "/config/cron_secrets.php";
require_once __DIR__ . "/config/cron_verification.php";

$start = time(); // Boucle infinie contrôlée A RETIRER A LA FIN
$maxTemps = 20 * 60; // 20 minutes en secondes A RETIRER A LA FIN

set_time_limit(0); // Désactive la limite de temps d’exécution pour les scripts CLI
verifierCronAcces(); // Vérification d'accès au fichier cron

echo "Fichier daemon.php démarré à " . date('Y-m-d H:i:s') . "\n" . "<br>";

// Boucle infinie
while (true) {
    $jourCourant = date('D'); // Récupérer le jour en cours
    $heureCourante = date('H:i'); // Récupérer l'heure actuelle
    $toutesTachesExecutees = true; // Flag pour vérifier si toutes les tâches sont terminées
    $tachesCronExistantes = AmobService::getInstance()->getAdminTacheCronService()->rechercherToutesLesTachesCron(); // Récupérer toutes les tâches cron
    
    // Pour chaque tâche cron
    foreach ($tachesCronExistantes as $tacheCron) {
        $doitExecuter = false; // Flag pour savoir si la tâche doit être exécutée
        $joursAutorises = array_map('trim', explode(',', $tacheCron->periode_lancement)); // Récupérer les jours de lancement de la tâche cron
        $periodeMode = $tacheCron->periode_mode; // Récupération du mode de période
        
        // Si le type de période est "semaine"
        if ($periodeMode === 'semaine') {
            // Si le jour en cours ne correspond pas à un des jours des jours de lancement de la tâche cron
            if (in_array(strtolower($jourCourant), array_map('strtolower', $joursAutorises))) {
                $doitExecuter = true;
            }

            // Si le type de période est "mois"
        } elseif ($periodeMode === 'mois') {
            // Mode MOIS → vérifier si le jour du mois correspond
            $joursNumeriques = array_map('intval', $joursAutorises); // Convertit la liste des jours en entiers pour comparaison
            $jourCourantMois = (int) date('j'); // Récupérer le jour en cours
            $dernierJourMois = (int) date('t'); // Récupérer le dernier jour du mois (28, 29, 30 ou 31 selon le mois)
            
            // Si le jour courant est explicitement listé
            if (in_array($jourCourantMois, $joursNumeriques)) {
                $doitExecuter = true; // Active l'exécution si le jour du mois correspond à un jour configuré
            
                // Sinon, si la tâche est programmée sur un jour inexistant (ex : 31 février)
            } else {
                // Pour chaque jour programmé
                foreach ($joursNumeriques as $jourProgramme) {
                    // Si un jour programmé dépasse le dernier jour du mois et qu'on est actuellement le dernier jour du mois
                    if ($jourProgramme > $dernierJourMois && $jourCourantMois === $dernierJourMois) {
                        $doitExecuter = true; // Force l'exécution le dernier jour du mois
                        echo "Exécution forcée de la tâche '{$tacheCron->nom}' car le jour {$jourProgramme} n'existe pas ce mois-ci.\n" . "<br>";
                        break; // On sort de la boucle dès qu’une correspondance est trouvée
                    }
                }
            }
        }
        // Si la tâche n'est pas prévue pour aujourd'hui
        if (!$doitExecuter) {
            continue; // On passe au jour suivant
        }
        $heuresPrevues = array_map('trim', explode(',', $tacheCron->heure_lancement)); // Récupère les heures prévues pour la tâche sous forme de tableau
        $heuresExecutees = $tacheCron->execution_tache ? explode(',', $tacheCron->execution_tache) : []; // Transformer execution_tache en tableau pour vérifier les heures déjà effectuées
        
        // Si certaines heures ne sont pas encore exécutées
        if (count($heuresExecutees) < count($heuresPrevues)) {
            $toutesTachesExecutees = false; // On marque le flag à false
        }

        // Pour chaque heure prévue
        foreach ($heuresPrevues as $heure) {
            // Convertir les heures en timestamp pour faire des comparaisons
            $timestampCourant = strtotime($heureCourante); // Heure courante
            $timestampPrevue = strtotime($heure); // Heure prévue
            
            // On vérifie si l'heure courante est dans la plage pour exécuter la tâche (+10 min)
            if ($timestampCourant >= $timestampPrevue && $timestampCourant <= ($timestampPrevue + 600) && !in_array($heure, $heuresExecutees)) {
                // Si un fichier PHP existe pour la tâche
                if (!empty($tacheCron->file_name) && file_exists($tacheCron->file_name)) {
                    include_once $tacheCron->file_name; // Inclut le fichier une seule fois pour éviter les erreurs de classe
                }
                $heuresExecutees[] = $heure; // Ajouter l'heure prévue à la liste des heures déjà exécutées
                $tacheCron->execution_tache = implode(',', $heuresExecutees); // Mettre à jour le champ execution_tache avec toutes les heures exécutées séparées par des virgules
                
                // Mettre à jour l'exécution de la tâche en base de données
                AmobService::getInstance()->getAdminTacheCronService()->mettreAJourExecutionTacheCron($tacheCron->id, $tacheCron->execution_tache);

                // Si toutes les heures prévues ont été exécutées
                if (count($heuresExecutees) === count($heuresPrevues)) {
                    $tacheCron->etat = AdminTacheCron::STATUT_TERMINE; // Marquer la tâche comme terminée
                    
                    // Sinon, si au moins une heure a été exécutée
                } elseif (count($heuresExecutees) > 0) {
                    $tacheCron->etat = AdminTacheCron::STATUT_EN_COURS; // Marquer la tâche comme en cours
                }
                // Mettre à jour l'état de la tâche cron
                AmobService::getInstance()->getAdminTacheCronService()->mettreAJourEtatTacheCron($tacheCron->id, $tacheCron->etat);
            }
        }
    }

    // Message une fois que toutes les tâches prévues sont exécutées
    if ($toutesTachesExecutees) {
        echo "[" . date('Y-m-d H:i:s') . "] Toutes les tâches prévues ont été exécutées pour ce cycle.\n" . "<br>";
    }

    echo "Cycle terminé à " . date('Y-m-d H:i:s') . ". Prochain cycle dans 2,5 minutes.\n" . "<br>";

    // Vérifier si le temps max est dépassé
    if ((time() - $start) >= $maxTemps) {
        echo "Fin du script après $maxTemps secondes.\n" . "<br>";
        break;
    }
    // Pause avant le prochain cycle
    sleep(150); // Cycle toutes les 2,5 minutes A MODIFIER A LA FIN
}