<?php

/**
 * Vérification de l'accès aux scripts cron
 * Compatible mode HTTP et CLI (daemon)
 * @author Geoffrey
 */

// Inclusion Paramètres Globaux (Classes, Session, Accès Opérateur,...)
require_once( "_inc/header.php");

// Vérification Habilitation
if (!$session->get("operateurGalaxy")->estHabiliteAdmin()) {
    Warning::getInstance()->redirect("/erreur/accesRefuse.php", null);
}

// Inclusion du fichier des secrets pour récupérer le jeton et l'IP autorisée / Chemin relatif à partir du répertoire actuel
include_once(__DIR__ . "/cron_secrets.php");

/**
 * Vérifier la validité du token et de l'adresse IP autorisée pour les scripts cron
 * Fonction utilisable en mode HTTP et en mode CLI (daemon)
 */
function verifierCronAcces() {
    // Si le mode d'exécution est mode CLI
    if (php_sapi_name() === 'cli') {
        // Tableau des arguments passés en CLI
        global $argv;

        // Vérifier si un token est fourni en argument
        if (!isset($argv[1])) {
            die("Erreur : Le token secret est manquant (CLI).");
        }
        // Vérifier que le token fourni correspond au token défini dans cron_secrets.php
        if ($argv[1] !== CRON_SECRET_TOKEN) {
            die("Accès interdit (CLI) : token incorrect.");
        }

        // Pas besoin de vérifier l’IP en CLI, car c’est forcément ton propre serveur qui exécute le cron
        return true;
    } else {
        
        // Vérifier si le paramètre 'secretToken' est présent dans l'URL
        if (!isset($_GET['secretToken'])) {
            die("Erreur : Le token secret est manquant dans l'URL.");
        }
        // Vérifier que le token récupéré dans l'URL correspond au token enregistré dans cron_secrets.php
        if ($_GET['secretToken'] !== CRON_SECRET_TOKEN) {
            die("Accès interdit : token incorrect.");
        }

        // Récupérer l'adresse IP du serveur
        $ipServeur = gethostbyname(gethostname());

        // Vérifier que l'adresse IP du serveur correspond à l'adresse IP enregistrée dans cron_secrets.php
        if ($ipServeur !== CRON_ALLOWED_IP) {
            die("Accès interdit : IP non autorisée.");
        }

        return true;
    }
}