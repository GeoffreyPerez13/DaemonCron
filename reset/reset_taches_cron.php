<?php

/**
 * Script de réinitialisation quotidienne des tâches cron
 * Vide les heures exécutées et remet les états à "A FAIRE"
 * S'exécute uniquement en CLI avec un token valide
 * @author Geoffrey
 */

// Inclusion du fichier des secrets pour récupérer le jeton et l'IP autorisée et inclusion du fichier de vérification des accès cron
require_once __DIR__ . "/../config/cron_secrets.php";
require_once __DIR__ . "/../config/cron_verification.php";

verifierCronAcces(); // Vérification d'accès au fichier cron

// Récupérer toutes les tâches cron
$tachesCronExistantes = AmobService::getInstance()->getAdminTacheCronService()->rechercherToutesLesTachesCron();

// Pour chaque tâche cron
foreach ($tachesCronExistantes as $tacheCron) {
    $tacheCron->execution_tache = ""; // On vide les heures exécutées
    $tacheCron->etat = AdminTacheCron::STATUT_A_VENIR; // On repasse à "A venir"
    
    // Mise à jour en base de la tâche cron
    AmobService::getInstance()->getAdminTacheCronService()->mettreAJourExecutionTacheCron($tacheCron->id, "");
    AmobService::getInstance()->getAdminTacheCronService()->mettreAJourEtatTacheCron($tacheCron->id, AdminTacheCron::STATUT_A_VENIR);
}

echo "Fichier reset_taches_cron.php démarré à " . date('Y-m-d H:i:s') . "\n" . "<br>";
echo "Toutes les tâches cron ont été réinitialisées avec succès.\n";