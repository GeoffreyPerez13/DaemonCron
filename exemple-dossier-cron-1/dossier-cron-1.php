<?php

/**
 * Envoyer automatiquement les notifications des alertes programmées sur les réservations d'un hôtel
 * class EnvoyerNotificationAlerteReservation
 * @author Geoffrey
 */

// Inclusion Parametres Globaux (Classes, Session, Acces Operateur,...)
include_once("_inc/header.php");
include_once("_inc/fonctions/validation.php");

class EnvoyerNotificationAlerteReservation {
}

$Action = new EnvoyerNotificationAlerteReservation();
$Action->envoyerNotificationAlerte();
