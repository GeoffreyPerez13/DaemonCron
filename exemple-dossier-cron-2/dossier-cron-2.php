<?php

/**
 * Archiver un hôtel
 * class ArchiverMoisHotel
 * @modifié par Geoffrey
 */

include_once "_inc/headerPhp.php";

class ArchiverMoisHotel {

    public $connexion = array();
    public $hotelsConfig = array();

    function __construct() {

        $this->connexion = Sql::getInstance();

        /* @var $hotels HotelConfig */
        $this->hotelsConfig = AmobService::getInstance()->getAmobHotelService()->rechercherHotelPourCloture();
    }

    public function archiver() {
    }
}

$archiverMoisHotel = new ArchiverMoisHotel();
$archiverMoisHotel->archiver();
