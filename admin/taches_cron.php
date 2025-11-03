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

// Configuration de la page actuelle
/* @var $amobPage AmobPage */
$amobPage = AmobPage::getInstance();

$amobPage->setTitre(Text::_("GM_ADMINISTRATION") . " <small> " . " - <span style=\"color:#3DBCFA; font-size: 12px;\">" . Text::_("GM_TACHES_CRON") . "</span> </small> ");

$amobPage->ajouterJs("/admin/tableau-bord/_js/taches-cron.js");
$amobPage->ajouterCss("/admin/tableau-bord/_css/taches-cron.css");

/* @var $operateurGalaxy Operateur */
$operateurGalaxy = $session->get("operateurGalaxy");

// Incluse du template (en-tete)
include_once( "_templates/defaut/header.php");
?>

<!-- BEGIN PAGE HEAD -->
<div class="page-head">
    <div class="container">
        <!-- BEGIN PAGE TITLE -->
        <div class="page-title">
            <h1><?php echo $amobPage->getTitre() ?></h1>
        </div>
    </div>
</div>

<!-- BEGIN PAGE CONTENT -->
<div class="page-content">
    <div class="container">

        <!-- BEGIN PAGE CONTENT INNER -->
        <div class="row">
            <div class="col-md-12">

                <div id="galaxyAlertsContainer">
                    <?php if ($session->has("ERREURS")) { ?>
                        <?php $session->get("ERREURS")->afficherErreurs(); ?>
                    <?php } ?>
                </div>

                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light">
                    <div class="portlet-title">
                        <div class="caption">
                            <h3 class="font-green-sharp no-space"><?php echo Text::_("GM_TACHES_CRON"); ?></h3>
                        </div>
                        <div class="actions btn-set">
                            <!-- Bouton de suppression des tâches cron -->
                            <button type="submit" id='btnSupprimerTachesCron' data-target="#modal-supprimer-taches-cron" class="btn red btn-circle pull-left"><i class="fa fa-trash-alt"></i> <?php echo Text::_("GM_SUPPRIMER") ?></button>
                            <!-- Bouton de suppression d'une tâche cron -->
                            <a class="btn green-haze btn-circle" href="/admin/tableau-bord/tache-cron-form.php">
                                <i class="fa fa-plus"></i> <?php echo Text::_("GM_AJOUTER"); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="alert alert-danger alert-danger-checkbox display-hide">
                        <button class="close" data-close="alert"></button>
                        <?php echo Text::_("GM_VEUILLEZ_SELECTIONNER_AU_MOINS_UNE_TACHE_CRON_A_SUPPRIMER"); ?>
                    </div>
                    
                    <div class="portlet-body">
                        <div id="table-listing">
                            <table data-count-fixed-columns="1" class="table table-striped table-bordered table-hover" id="datatableTachesCron">
                                <thead>
                                    <tr role="row" class="text-center">
                                        <th></th>
                                        <th><?php echo Text::_("GM_LIBELLE"); ?></th>
                                        <th><?php echo Text::_("GM_DESCRIPTION"); ?></th>
                                        <th><?php echo Text::_("GM_FICHIER"); ?></th>
                                        <th><?php echo Text::_("GM_PERIODE_DE_LANCEMENT"); ?></th>
                                        <th><?php echo Text::_("GM_HEURE_DE_LANCEMENT"); ?></th>
                                        <th><?php echo Text::_("GM_ETAT"); ?></th>
                                        <th><?php echo Text::_("GM_ACTION"); ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
            </div>
        </div>
        <!-- END PAGE CONTENT INNER -->
    </div>
</div>

<!-- END PAGE CONTENT -->
<div class="modal fade" id="modal-supprimer-tache-cron" aria-hidden="true">

    <div class="modal-body">
        <img src="/_images/loading-spinner-grey.gif" alt="" class="loading">
        <span>
            &nbsp;&nbsp;<?php echo Text::_("GM_CHARGEMENT"); ?> 
        </span>
    </div>
</div>

<div id="modal-supprimer-taches-cron" class="modal fade modal-pms" tabindex="-1" data-width="550">
    <form id="FormSuppressionTachesCron" name="FormSuppressionTachesCron" method="POST" action="/admin/tableau-bord/_actions/TachesCronAction.php">
        <input type="hidden" name="methode" value="supprimerTachesSelectionnees" />

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
            <h4 class="modal-title"><strong><?php echo Text::_("GM_SUPPRIMER_TACHES_CRON"); ?></strong></h4>
        </div>
        <div class="modal-body">
            <p><?php echo Text::_("GM_LES_TACHES_CRON_SUIVANTES_SERONT_SUPPRIMEES"); ?>.<br> <?php echo Text::_("GM_VOULEZ_VOUS_CONTINUER"); ?> </p>
            <div id="liste-taches-selectionnees"></div> <!-- conteneur pour injecter la liste des tâches cron à supprimer -->
        </div>
        <div class="modal-footer">
            <button type="button" data-dismiss="modal" class="btn btn-default"> <?php echo Text::_("GM_ANNULER"); ?> </button>
            <button type="submit" form="FormSuppressionTachesCron" class="btn red"> <?php echo Text::_("GM_SUPPRIMER"); ?> </button>
        </div>
    </form>
</div>

<?php
// Incluse du template (pied de page)
include_once( "_templates/defaut/footer.php");
?>