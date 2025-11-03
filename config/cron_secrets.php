<?php

/* * *
 * @author Geoffrey
 */

// Inclusion Paramètres Globaux (Classes, Session, Accès Opérateur,...)
require_once( "_inc/header.php");

// Vérification Habilitation
if (!$session->get("operateurGalaxy")->estHabiliteAdmin()) {
    Warning::getInstance()->redirect("/erreur/accesRefuse.php", null);
}

define('CRON_SECRET_TOKEN', '31a36df6550885e63876a825dfd949b4f5c5322360ca9a644c9b044a382c841d'); // Jeton secret unique - Token généré grâce à la méthode native de PHP : bin2hex(openssl_random_pseudo_bytes(32))
define('CRON_ALLOWED_IP', '192.168.1.250'); // IP autorisée (adresse du serveur qui exécute les crons)

