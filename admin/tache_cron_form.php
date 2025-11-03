<?php
/* * *
 * @author Geoffrey
 */

// Inclusion Paramètres Globaux (Classes, Session, Accès Opérateur,...)
require_once( "_inc/header.php");
include_once( "_inc/fonctions/validation.php");

// Configuration de la page actuelle
/* @var $amobPage AmobPage */
$amobPage = AmobPage::getInstance();

/* @var $monObjetWarning Warning */
$monObjetWarning = Warning::getInstance();

/* @var $operateurGalaxy Operateur */
$operateurGalaxy = $session->get("operateurGalaxy");

// Vérification Habilitation
if (!$session->get("operateurGalaxy")->estHabiliteAdmin()) {
    Warning::getInstance()->redirect("/erreur/accesRefuse.php", null);
}

// Récupérer l'id de la tâche cron
$cronId = (int) getVar("id", 0, "get");

// Récupérer toutes les tâches cron
$tachesCronExistantes = AmobService::getInstance()->getAdminTacheCronService()->rechercherToutesLesTachesCron();

// Si l'id est supérieure à 0
if ($cronId > 0) {
    // Rechercher la tâche cron grâce à son id
    $tacheCronExistante = AmobService::getInstance()->getAdminTacheCronService()->rechercherTacheCronId($cronId);

    // Affichage texte
    $amobPage->setTitre(Text::_("GM_ADMINISTRATION") . " <small> " . " - <span style=\"color:#3DBCFA; font-size: 12px;\">" . Text::_("GM_MODIFIER_TACHE_CRON") . "</span> </small> ");

    // Redirection si la tâche cron n'est pas trouvée
    if (!$tacheCronExistante) {
        $monObjetWarning->redirect("/admin/tableau-bord/taches-cron.php", array(Text::_("GM_TACHE_CRON_INTROUVABLE")));
    }
} else {
    /** @var AdminTacheCron $tacheCron */
    $tacheCron = new AdminTacheCron;

    $amobPage->setTitre(Text::_("GM_ADMINISTRATION") . " <small> " . " - <span style=\"color:#3DBCFA; font-size: 12px;\">" . Text::_("GM_AJOUTER_TACHE_CRON") . "</span> </small> ");
}

$amobPage->ajouterCss("/admin/tableau-bord/_css/taches-cron.css");
$amobPage->ajouterJs("/admin/tableau-bord/_js/taches-cron-form.js");
$amobPage->ajouterJs("/_js/icheck-1.0.3/icheck.js"); // Style des checkboxs
$amobPage->ajouterCss("/_js/icheck-1.0.3/skins/all.css"); // Style des checkboxs


// Remonter deux niveaux pour atteindre le dossier "galaxy-manager.fr"
$basePath = dirname(__DIR__, 2);

// Définir le chemin du dossier contenant les fichiers cron
$cronDirectory = $basePath . DIRECTORY_SEPARATOR . '_cron';

/**
 * Fonction pour récupérer toutes les tâches cron contenues dans les fichiers du dossier "_cron".
 * @param string $directory Chemin du dossier contenant les sous-dossiers de tâches cron
 * @return array Liste des tâches cron avec leur description, nom de classe (si existant) et le fichier source
 */
function getCronJobsFromDir($directory) {
    // Vérifier si le répertoire existe et est accessible en lecture
    if (!is_dir($directory) || !is_readable($directory)) {
        return []; // Retourne un tableau vide si le dossier n'est pas accessible
    }
    // Scanner les dossiers à l'intérieur de "_cron" (chaque dossier contient des fichiers cron)
    $folders = scandir($directory);
    $jobs = []; // Tableau pour stocker les informations sur les tâches cron
    
    // Parcourir chaque dossier
    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..')
            continue; // Ignorer les entrées spéciales

        $folderPath = $directory . DIRECTORY_SEPARATOR . $folder; // Construire le chemin complet du dossier
        
        // Vérifier si c'est bien un dossier lisible
        if (is_dir($folderPath) && is_readable($folderPath)) {
            $files = scandir($folderPath); // Lister les fichiers dans le dossier
            
            // Parcourir chaque fichier dans le dossier
            foreach ($files as $file) {
                if ($file === '.' || $file === '..')
                    continue; // Ignorer les entrées spéciales

                $filePath = $folderPath . DIRECTORY_SEPARATOR . $file; // Construire le chemin complet du fichier
                
                // Vérifier que c'est bien un fichier lisible
                if (is_file($filePath) && is_readable($filePath)) {
                    $handle = fopen($filePath, "r"); // Ouvrir le fichier en lecture
                    
                    // Si le fichier est ouvert
                    if ($handle) {
                        $description = null; // Variable pour stocker la description de la tâche
                        $className = null; // Variable pour stocker le nom de la classe
                        $insideCommentBlock = false; // Indique si on est à l'intérieur du bloc de commentaire
                        
                        // Lire le fichier ligne par ligne
                        while (($line = fgets($handle)) !== false) {
                            $line = trim($line); // Supprimer les espaces inutiles
                            
                            // Détection du début du bloc de commentaire
                            if (strpos($line, '/**') === 0) {
                                $insideCommentBlock = true;
                                continue; // Passer à la ligne suivante
                            }
                            // Détection de la fin du bloc de commentaire
                            if (strpos($line, '*/') === 0) {
                                $insideCommentBlock = false;
                                break; // Sortir de la boucle
                            }

                            // Si on est dans un bloc de commentaire
                            if ($insideCommentBlock) {
                                // Extraire la description
                                if (!$description && strpos($line, '* class ') === false) {
                                    $description = ltrim($line, "* \t"); // Supprimer les "*" et espaces en début de ligne
                                }
                                // Extraire le nom de la classe
                                if (!$className && preg_match('/\*\s*class\s+([a-zA-Z0-9_]+)/', $line, $matches)) {
                                    $className = $matches[1]; // Récupérer uniquement le nom de la classe
                                }
                            }
                        }
                        fclose($handle); // Fermer le fichier
                        
                        // Si une description a été trouvée, ajouter la tâche au tableau
                        if ($description) {
                            $jobs[] = [
                                'ID' => md5($folder . $file), // Générer un ID unique pour chaque tâche
                                'Description' => $description, // Description extraite du commentaire
                                'Class' => $className, // Nom de la classe (si trouvé)
                                'Fichier' => "$folder/$file" // Nom du fichier contenant la tâche
                            ];
                        }
                    }
                }
            }
        }
    }
    return $jobs; // Retourne la liste des tâches cron trouvées
}
// Exécuter la fonction pour récupérer toutes les tâches cron
$cronJobs = getCronJobsFromDir($cronDirectory);

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

                <div class="alert alert-danger display-hide">
                    <button class="close" data-close="alert"></button>
                    <?php echo Text::_("GM_VEUILLEZ_VERIFIER_LES_INFORMAITONS"); ?>
                </div>

                <form id="formTacheCron" name="formTacheCron" class="form-horizontal" method="POST" action="/admin/tableau-bord/_actions/TachesCronAction.php">      
                    <?php if (isset($tacheCronExistante) && $tacheCronExistante->id > 0) { ?>
                        <input type="hidden" name="id" value="<?php echo $tacheCronExistante->id ?>" />
                        <input type="hidden" name="tache_cron" value="<?php echo $tacheCronExistante->tache_cron ?>" />
                        <input type="hidden" name="periode_lancement" value="<?php echo $tacheCronExistante->periode_lancement ?>" />
                        <input type="hidden" name="heure_lancement" value="<?php echo $tacheCronExistante->heure_lancement ?>" />
                        <input type="hidden" id="classCronHidden" name="cron_class" value="<?php echo $tacheCronExistante->cron_class ?>" />
                        <input type="hidden" id="fileHidden" name="file_name" value="<?php echo $tacheCronExistante->file_name ?>" />
                        <input type="hidden" name="etat" value="<?php echo $tacheCronExistante->etat ?>" />
                        <input type="hidden" name="execution_tache" value="<?php echo $tacheCronExistante->execution_tache ?>" />
                        <input type="hidden" name="methode" value="modifier" />
                    <?php } else { ?>
                        <input type="hidden" name="methode" value="ajouter" />
                    <?php } ?>

                    <!-- BEGIN SAMPLE TABLE PORTLET -->
                    <div class="portlet light">
                        <div class="portlet-title">
                            <div class="portlet-body form">
                                <div class="tab-content">
                                    <div class="form-body">
                                        <div class="row">
                                            <div class="col-md-6 margin-bottom-15">
                                                <h3 class="font-green-haze no-space margin-bottom-15"><?php echo Text::_("GM_CONFIGURATION"); ?></h3>

                                                <div class="row">
                                                    <div class="col-md-12">
                                                        
                                                        <!-- TÂCHE CRON A SELECTIONNER -->
                                                        <div class="form-group">
                                                            <label class="control-label col-md-4">
                                                                <?php echo Text::_("GM_TACHE_CRON"); ?> <span class="required">*</span>
                                                            </label>
                                                            <div class="col-md-7">
                                                                <?php if (isset($tacheCronExistante) && $tacheCronExistante->id > 0) { ?>
                                                                    <label class="padding-top-5">
                                                                        <?php echo htmlentities($tacheCronExistante->tache_cron, ENT_QUOTES, ini_get("default_charset")); ?>
                                                                    </label>
                                                                <?php } else { ?>
                                                                    <div class="input-group input-group-select-cron">
                                                                        <select id="tacheCronSelect" name="tache_cron" size="1" class="form-control select2" placeholder="<?php echo Text::_("GM_SELECTIONNER_UNE_TACHE_CRON"); ?>">
                                                                            <option value=""></option>
                                                                            <?php foreach ($cronJobs as $job) { ?>
                                                                                <!-- Filtrer pour ne pas afficher les tâches déjà utilisées -->
                                                                                <?php if (!in_array($job['Description'], array_column($tachesCronExistantes, 'tache_cron'))) { ?>
                                                                                    <option value="<?php echo htmlentities($job['Description'], ENT_QUOTES, ini_get("default_charset")) ?>"
                                                                                            data-class="<?php echo htmlentities($job['Class'], ENT_QUOTES, ini_get("default_charset")) ?>"
                                                                                            data-file="<?php echo htmlentities($job['Fichier'], ENT_QUOTES, ini_get("default_charset")) ?>"
                                                                                            <?php if (isset($tacheCronExistante) && $job['Description'] == $tacheCronExistante->tache_cron) echo "selected"; ?>>
                                                                                        <?php echo htmlentities($job['Description'], ENT_QUOTES, ini_get("default_charset")); ?>
                                                                                    </option>
                                                                                <?php } ?>
                                                                            <?php } ?>
                                                                        </select>
                                                                        <span id="cron-class-display" class="cron-class-display"></span>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>

                                                        <!-- Champ caché pour la classe cron sélectionnée -->
                                                        <input type="hidden" id="classCronHidden" name="cron_class" value="<?php echo isset($tacheCronExistante) ? htmlentities($tacheCronExistante->cron_class, ENT_QUOTES, ini_get("default_charset")) : ''; ?>" />
                                                        <!-- Champ caché pour le fichier -->
                                                        <input type="hidden" id="fileHidden" name="file_name" value="<?php echo isset($tacheCronExistante) ? htmlentities($tacheCronExistante->file_name, ENT_QUOTES, ini_get("default_charset")) : ''; ?>" />

                                                        <!-- PERIODE DE LANCEMENT A SELECTIONNER -->
                                                        <div class="row form-group">
                                                            <!-- Label cliquable -->
                                                            <label class="control-label col-md-4">
                                                                <span id="togglePeriodeLancement" class="label-toggle-inner" data-toggle="tooltip" data-placement="top" title="<?php echo Text::_("GM_CHANGER_DE_TYPE_DE_PERIODE_DE_LANCEMENT"); ?>">
                                                                    <?php echo Text::_("GM_PERIODE_DE_LANCEMENT"); ?>
                                                                </span>
                                                                <i class="fa fa-info-circle popovers" data-trigger="hover" data-container="body" data-placement="top" data-content="<?php echo Text::_("GM_PERIODE_DE_LANCEMENT_EXPLICATIONS"); ?>" data-original-title="<?php echo Text::_("GM_PERIODE_DE_LANCEMENT"); ?>"></i>
                                                                <span class="required">*</span>
                                                            </label>
                                                            <div class="col-md-8" style="padding-left: 0;">
                                                                <!-- Mode semaine -->
                                                                <div id="mode-semaine">
                                                                    <div class="icheck-list jours-flex">
                                                                        <label class="checkbox-inline">
                                                                            <input type="checkbox" class="icheck checkAllJours" data-checkbox="icheckbox_flat-blue">
                                                                            <?php echo Text::_("GM_TOUS"); ?>
                                                                        </label>

                                                                        <?php
                                                                        $joursSemaine = [
                                                                            'mon' => 'GM_LUNDI',
                                                                            'tue' => 'GM_MARDI',
                                                                            'wed' => 'GM_MERCREDI',
                                                                            'thu' => 'GM_JEUDI',
                                                                            'fri' => 'GM_VENDREDI',
                                                                            'sat' => 'GM_SAMEDI',
                                                                            'sun' => 'GM_DIMANCHE'
                                                                        ];
                                                                        $joursSelectionnes = !empty($tacheCronExistante) ? array_map('trim', explode(',', $tacheCronExistante->periode_lancement)) : [];

                                                                        foreach ($joursSemaine as $codeJour => $libelleJour): 
                                                                            $checked = in_array($codeJour, $joursSelectionnes) ? 'checked' : '';
                                                                        ?>
                                                                            <label class="checkbox-inline">
                                                                                <input name="periode_lancement[]" value="<?php echo $codeJour; ?>" 
                                                                                    type="checkbox" class="icheck icheckJour" data-checkbox="icheckbox_flat-blue" <?php echo $checked; ?> />
                                                                                <?php echo Text::_($libelleJour); ?>
                                                                            </label>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>

                                                                <!-- Mode mois -->
                                                                <div id="mode-mois" style="display: none;">
                                                                    <div id="jours-du-mois-container" class="d-flex flex-wrap" style="gap: 10px;"></div>
                                                                    <button type="button" id="add-jour-du-mois" class="btn green">
                                                                        <i class="fa fa-plus"></i> <?php echo Text::_("GM_AJOUTER_UN_JOUR_DU_MOIS"); ?>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                            
                                                        <?php
                                                        $periodeMode = "semaine"; // Initialiser le mode de période par défaut sur "semaine"
                                                        $joursSelectionnesMois = []; // Initialiser un tableau vide pour stocker les jours du mois si la tâche existe
                                                        
                                                        // Si une tâche existe et si le champ "periode_lancement" n'est pas vide
                                                        if (isset($tacheCronExistante) && !empty($tacheCronExistante->periode_lancement)) {
                                                            // Si le contenu de "periode_lancement" correspond à un format de jours du mois
                                                            if (preg_match('/^\d+(,\d+)*$/', $tacheCronExistante->periode_lancement)) {
                                                                $periodeMode = "mois"; // Si oui, on considère que le mode est "mois"
                                                                $joursSelectionnesMois = array_map('trim', explode(',', $tacheCronExistante->periode_lancement)); // Convertir la chaîne de jours en tableau et supprimer les espaces éventuels
                                                            } else {
                                                                $periodeMode = "semaine"; // Sinon, on reste en mode "semaine"
                                                            }
                                                        }
                                                        ?>
                                                        <!-- Champ caché pour savoir quel mode est actif -->
                                                        <input type="hidden" id="periode_mode" name="periode_mode" value="<?php echo $periodeMode; ?>">

                                                        <!-- HEURE DE LANCEMENT A SELECTIONNER -->
                                                        <div class="form-group">
                                                            <label class="control-label col-md-4"><?php echo Text::_("GM_HEURE_DE_LANCEMENT"); ?> <span class="required">*</span></label>
                                                            <div class="col-md-8">
                                                                <div class="row" id="heures-lancement-container">
                                                                    <?php
                                                                    // Si on édite une tâche existante ET que son champ "heure_lancement" n'est pas vide, on découpe la chaîne en tableau avec explode, sinon on met un tableau contenant une chaîne vide.
                                                                    $heuresLancement = isset($tacheCronExistante) && !empty($tacheCronExistante->heure_lancement) ? explode(',', $tacheCronExistante->heure_lancement) : [''];
                                                                    $heuresLancement = array_slice($heuresLancement, 0, 4); // On limite le nombre d'heures affichées au maximum de 3

                                                                    // Boucle sur chaque heure de lancement
                                                                    foreach ($heuresLancement as $index => $heure): 
                                                                    ?>
                                                                    <div class="col-md-3 margin-bottom-10 time-field-wrapper" data-index="<?php echo $index; ?>">
                                                                        <div class="input-group">
                                                                            <input class="form-control" type="time" name="heure_lancement[]" 
                                                                                   value="<?php echo htmlentities($heure, ENT_QUOTES, ini_get("default_charset")); ?>">
                                                                            <?php if ($index > 0): // Si ce n'est pas le premier champ, on affiche le bouton de suppression ?>
                                                                            <span class="input-group-btn">
                                                                                <button type="button" class="btn btn-danger remove-time-field">
                                                                                    <i class="fa fa-times"></i>
                                                                                </button>
                                                                            </span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                                
                                                                <button type="button" id="add-time-field" class="btn green">
                                                                    <i class="fa fa-plus"></i> <?php echo Text::_("GM_AJOUTER_UNE_HEURE"); ?>
                                                                </button>

                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>     

                                    <div class="form-actions actions-fixed">
                                        <div class="row text-align-reverse margin-right-20">
                                            <div class="col-md-12">
                                                <a class="btn grey" href="/admin/tableau-bord/taches-cron.php"><?php echo Text::_("GM_ANNULER"); ?></a>
                                                <button type="submit"  class="btn green"><?php echo Text::_("GM_VALIDER"); ?></button>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- END SAMPLE TABLE PORTLET-->
                        </div>

                    </div> 
                </form>
            </div>
            <!-- END PAGE CONTENT INNER -->
        </div>
    </div>
</div>
<!-- END PAGE CONTENT -->
<script>
    // On crée un objet global pour stocker les traductions
    window.translations = {
        jourDuMois: '<?php echo Text::_("GM_JOURS_DU_MOIS"); ?>',
        supprimer: '<?php echo Text::_("GM_SUPPRIMER"); ?>'
    };
    // Jours sélectionnés du mois (depuis PHP)
    window.joursSelectionnesMois = <?php echo json_encode($joursSelectionnesMois ?? []); ?>;
</script>

<?php
// Incluse du template (pied de page)
include_once( "_templates/defaut/footer.php");
?>