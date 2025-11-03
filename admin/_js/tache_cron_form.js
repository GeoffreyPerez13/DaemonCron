/* 
 * @author Geoffrey
 */

jQuery(document).ready(function () { // Exécute le script une fois que le DOM est chargé

    /* ==================== GESTION CHECKBOXES ==================== */
    // Met à jour l'état de la checkbox "Tout sélectionner"
    function updateCheckAll() {
        const allChecked = jQuery(".icheckJour").length > 0 && jQuery(".icheckJour:not(:checked)").length === 0; // Vérifie s'il y a des checkbox de jour, qu'aucune n'est décochée
        jQuery(".checkAllJours").iCheck(allChecked ? "check" : "uncheck"); // iCheck: coche/décoche tout
        jQuery(".checkAllJours")[0].checked = allChecked; // Met à jour la valeur native pour le HTML
    }
    updateCheckAll(); // Initialisation au chargement

    // Quand on clique sur "Tout sélectionner"
    jQuery(".checkAllJours").on("ifClicked", function () {
        const willCheck = !jQuery(this).is(":checked"); // Détermine si on doit cocher ou décocher
        setTimeout(() => {
            jQuery(".icheckJour").iCheck(willCheck ? "check" : "uncheck"); // Applique le clic à toutes les checkbox
        }, 0); // Timeout 0 pour attendre la mise à jour iCheck
    });

    // Quand une checkbox jour change
    jQuery(".icheckJour").on("ifChanged", function () {
        updateCheckAll(); // Met à jour "Tout sélectionner"

        const joursChecked = jQuery(".icheckJour:checked"); // Récupère tous les jours cochés
        const label = jQuery(".jours-flex").closest(".form-group").find(".control-label"); // Label parent

        // Si au moins un jour est coché
        if (joursChecked.length > 0)
            label.css("color", ""); // Réinitialise la couleur du label
    });

    /* ==================== GESTION AJOUT/SUPPRESSION HEURES ==================== */
    // Met à jour la visibilité du bouton "Ajouter une heure"
    function updateAddButton() {
        const count = jQuery("#heures-lancement-container .time-field-wrapper").length; // Compte les inputs
        jQuery("#add-time-field").toggle(count < 4); // Masque si >=4 inputs
    }

    // Ajoute un champ horaire
    function addTimeField() {
        const container = jQuery("#heures-lancement-container"); // Conteneur des inputs

        // Limite maximum à 4 inputs
        if (container.find(".time-field-wrapper").length >= 4)
            return;

        const newIndex = container.find(".time-field-wrapper").length; // Indice pour le nouvel input
        const titleSupprimer = window.translations.supprimer; // Texte pour tooltip bouton supprimer

        // Création de l'input avec bouton supprimer
        const newField = jQuery(`
            <div class="col-md-3 margin-bottom-10 time-field-wrapper" data-index="${newIndex}">
                <div class="input-group">
                    <input class="form-control" type="time" name="heure_lancement[]" value="">
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-danger remove-time-field" title="${titleSupprimer}">
                            <i class="fa fa-times"></i>
                        </button>
                    </span>
                </div>
            </div>
        `);
        container.append(newField); // Ajoute le champ au DOM
        updateAddButton(); // Met à jour le bouton ajouter
    }

    // Supprime un champ horaire
    function removeTimeField(button) {
        button.closest(".time-field-wrapper").remove(); // Supprime le parent wrapper

        // Re-indexe tous les inputs
        jQuery("#heures-lancement-container .time-field-wrapper").each(function (i) {
            jQuery(this).attr("data-index", i);
        });
        updateAddButton(); // Met à jour le bouton ajouter
    }

    jQuery("#add-time-field").on("click", addTimeField); // Bouton ajouter une heure

    // Supprimer un champ horaire
    jQuery("#heures-lancement-container").on("click", ".remove-time-field", function () {
        removeTimeField(jQuery(this));
    });
    updateAddButton(); // Initialisation bouton ajouter

    /* ==================== GESTION AJOUT/SUPPRESSION JOURS DU MOIS ==================== */
    // Met à jour la visibilité des boutons supprimer
    function updateRemoveButtons() {
        const container = jQuery("#jours-du-mois-container"); // Récupère le conteneur qui contient tous les inputs pour les jours du mois
        container.find(".remove-jour-du-mois").show(); // Tous visibles
        container.find(".jour-du-mois-wrapper:first-child .remove-jour-du-mois").hide(); // Masque le premier
    }

    // Met à jour le bouton ajouter un jour
    function updateAddJourButton() {
        const count = jQuery("#jours-du-mois-container .jour-du-mois-wrapper").length; // Compte le nombre de champs "jour du mois" actuellement présents dans le conteneur
        jQuery("#add-jour-du-mois").toggle(count < 4); // Limite maximum à 4 inputs
    }

    // Ajoute un input jour du mois
    function addJourDuMois(value = "") {
        const container = jQuery("#jours-du-mois-container"); // Récupère le conteneur qui contient tous les inputs pour les jours du mois

        // Limite maximum à 4 inputs
        if (container.find(".jour-du-mois-wrapper").length >= 4)
            return;

        const placeholderJour = window.translations.jourDuMois; // Placeholder
        const titleSupprimer = window.translations.supprimer; // Tooltip bouton supprimer

        // Création du champ avec bouton
        const newInput = jQuery(`
            <div class="jour-du-mois-wrapper">
                <input type="number" min="1" max="31" name="periode_lancement[]" class="form-control" placeholder="${placeholderJour}" value="${value}">
                <button type="button" class="btn btn-danger remove-jour-du-mois" title="${titleSupprimer}">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        `);
        container.append(newInput); // Ajoute au DOM
        updateRemoveButtons(); // Met à jour la visibilité boutons supprimer
        updateAddJourButton(); // Met à jour bouton ajouter
    }

    // Ajouter un jour
    jQuery("#add-jour-du-mois").on("click", function () {
        addJourDuMois();
    });

    // Supprimer un jour
    jQuery(document).on("click", ".remove-jour-du-mois", function () {
        jQuery(this).closest(".jour-du-mois-wrapper").remove(); // Supprime le wrapper
        updateRemoveButtons(); // Met à jour boutons supprimer
        updateAddJourButton(); // Met à jour bouton ajouter
    });

    /* ==================== RESTRICTION CHAMPS JOURS DU MOIS (1–31 uniquement) ==================== */
    // Limiter les nombres de 1 à 31
    jQuery(document).on('input', '.jour-du-mois-wrapper input[type="number"]', function () {
        let val = this.value.replace(/[^0-9]/g, ''); // Supprime tout sauf chiffres

        // Si la valeur n'est pas vide
        if (val !== '')
            val = Math.min(Math.max(parseInt(val), 1), 31); // Contraint 1 à 31
        this.value = val; // Remet la valeur corrigée
    });

    /* ==================== TOGGLE SEMAINE / MOIS ==================== */
    jQuery("#togglePeriodeLancement").on("click", function (e) {
        e.preventDefault(); // Empêche le comportement par défaut

        // Réinitialisation erreurs et styles
        jQuery(".jours-flex").closest(".form-group").find(".control-label").css("color", ""); // Réinitialise la couleur du label du groupe de jours à la valeur par défaut (utile pour enlever les erreurs)
        jQuery("#heures-lancement-container input[name='heure_lancement[]']").css({border: '', backgroundColor: ''}); // Réinitialise le style (bordure et fond) des champs horaires pour enlever l'affichage d'erreur
        jQuery("#jours-du-mois-container input[name='periode_lancement[]']").val('').css({border: '', backgroundColor: ''}); // Réinitialise le style (bordure et fond) des champs horaires pour enlever l'affichage d'erreur
        jQuery(".icheckJour").iCheck('uncheck'); // Décoche tous les jours
        jQuery(".checkAllJours").iCheck('uncheck'); // Décoche "tout"
        jQuery(".alert-danger").addClass("display-hide"); // Masque les messages d'erreur

        // Toggle affichage
        jQuery("#mode-semaine").toggle(); // Affiche/masque semaine
        jQuery("#mode-mois").toggle(); // Affiche/masque mois

        const newMode = jQuery("#periode_mode").val() === "semaine" ? "mois" : "semaine"; // Détermine nouveau mode
        jQuery("#periode_mode").val(newMode); // Met à jour le champ caché

        // Si le mode passé est "mois" et qu'il n'y a aucun champ jour du mois existant, on doit en ajouter un
        if (newMode === "mois" && jQuery("#jours-du-mois-container .jour-du-mois-wrapper").length === 0) {
            addJourDuMois(); // Ajoute un input par défaut si vide
        }
        updateAddJourButton(); // Met à jour bouton ajouter
    });

    /* ==================== VALIDATION FORMULAIRE ==================== */
    // Fonction de validation : vérifie que tous les champs correspondants au sélecteur sont remplis
    function validateFields(selector, isSelect2 = false) {
        let isValid = true; // Définir un flag par défaut à true pour indiquer que tout est valide

        // Initialisation de la variable de validation à vrai
        jQuery(selector).each(function () {
            const field = jQuery(this); // Parcourt tous les éléments correspondant au sélecteur
            const value = (field.val() || "").trim(); // Stocke l'élément courant, récupère la valeur et supprime les espaces inutiles
            // Si un champ est vide
            if (value === "") {
                isValid = false; // Marque le formulaire comme invalide

                // Si c'est un champ Select2
                if (isSelect2) {
                    field.closest(".form-group").find(".select2-choice").css({border: "1px solid red", backgroundColor: "#f1e1e1"}); // Applique le style d'erreur au select2
                    field.closest(".form-group").find(".control-label").css("color", "#e02222"); // Change la couleur du label en rouge

                } else {
                    // Sinon on est dans un input normal
                    field.css({border: "1px solid red", backgroundColor: "rgb(241,225,225)"}); // Applique le style d'erreur
                    field.closest(".form-group").find(".control-label").css("color", "#e02222"); // Change la couleur du label en rouge
                }
            }
        });
        return isValid; // Retourne vrai si tous les champs sont remplis
    }

    // Réinitialise les erreurs quand on modifie un champ
    function resetFieldError(selector, container = document) {
        // Sur le conteneur, écoute les événements input et change sur les champs correspondants
        jQuery(container).on("input change", selector, function () {
            const field = jQuery(this); // Récupère le champ actuel

            // Si le champ est visible et non vide
            if (field.is(":visible") && field.val().trim() !== "") {
                field.css({border: "", backgroundColor: ""}); // Réinitialise le style
                field.closest(".form-group").find(".control-label").css("color", ""); // Réinitialise la couleur du label

            }
        });
    }
    // Applique réinitialisation pour différents champs
    resetFieldError("input[name='heure_lancement[]']", "#heures-lancement-container");
    resetFieldError("input[name='periode_lancement[]']", "#jours-du-mois-container");
    resetFieldError(".form-control", "#formTacheCron");

    // Validation à la soumission
    jQuery("#formTacheCron").on("submit", function (event) {
        let isValid = true; // Initialise la variable de validation globale du formulaire
        const errorMessage = jQuery(".alert-danger"); // Sélectionne l'élément qui contient le message d'erreur
        errorMessage.addClass("display-hide"); // Masque erreurs au départ

        isValid &= validateFields("select[name='tache_cron']", true); // Vérifie select
        isValid &= validateFields("input[name='heure_lancement[]']"); // Vérifie heures
        const periodeMode = jQuery("#periode_mode").val(); // Récupère le mode actuel
        
        // Récupère le mode actuel (semaine ou mois)
        if (periodeMode === "semaine") { // Mode semaine
            // Si aucun jour de semaine n'est coché
            if (jQuery("#mode-semaine input[name='periode_lancement[]']:checked").length === 0) {
                isValid = false; // Formulaire invalide
                jQuery(".jours-flex").closest(".form-group").find(".control-label").css("color", "#e02222"); // Label rouge pour indiquer l'erreur
            }
        } else if (periodeMode === "mois") { // Mode mois
            // Récupère tous les champs visibles de jours du mois
            const visibleInputs = jQuery("#jours-du-mois-container input[name='periode_lancement[]']:visible");

            // Pour chaque champ
            visibleInputs.each(function () {
                const field = jQuery(this); // Champ actuel

                // Si le champ est vide
                if (field.val().trim() === "") {
                    isValid = false; // Formulaire invalide
                    field.css({border: "1px solid red", backgroundColor: "rgb(241,225,225)"}); // Style d'erreur
                    field.closest(".form-group").find(".control-label").css("color", "#e02222"); // Label en rouge
                }
            });
        }

        // Si tout n'est pas valide
        if (!isValid) {
            errorMessage.removeClass("display-hide"); // Affiche erreurs
            event.preventDefault(); // Empêche l'envoi
        }
    });

    /* ==================== GESTION SELECT2 TACHE CRON ==================== */
    // Quand on change la tâche
    jQuery("select[name='tache_cron']").on("change", function () {
        const selected = jQuery(this).find("option:selected"); // Option sélectionnée
        const tacheCronSelect2 = jQuery(".select2-choice"); // Sélectionne le conteneur visuel du Select2

        // Si la valeur du select n'est pas vide
        if (jQuery(this).val().trim() !== "") {
            tacheCronSelect2.css({border: "", backgroundColor: ""}).find(".select2-chosen").css({color: ""}); // Réinitialise le style Select2
            tacheCronSelect2.closest(".form-group").find(".control-label").css("color", ""); // Réinitialise le label
        }
        // Mise à jour des champs cachés
        jQuery("#classCronHidden").val(selected.data('class') || ''); // Met à jour la valeur du champ caché avec la classe Cron
        jQuery("#fileHidden").val(selected.data('file') || ''); // Met à jour la valeur du champ caché avec le fichier associé

        const cronClass = selected.data("class") || ""; // Récupère la classe Cron de l'option sélectionnée
        jQuery("#cron-class-display").text(cronClass).toggle(!!cronClass); // Affiche le nom de la classe si elle existe
    });

    // Initialisation au chargement
    const initialOption = jQuery("select[name='tache_cron'] option:selected"); // Récupère l'option sélectionnée au chargement
    jQuery("#cron-class-display").text(initialOption.data("class") || "").toggle(!!initialOption.data("class")); // Affiche son nom si existant
    jQuery("#classCronHidden").val(initialOption.data('class') || ''); // Met à jour le champ caché class
    jQuery("#fileHidden").val(initialOption.data('file') || ''); // Met à jour le champ caché file

    /* ==================== INITIALISATION MODE MOIS AVEC JOURS EXISTANTS ==================== */
    // Fonction auto-exécutée pour initialiser le mode mois si nécessaire
    (function initModeMois() {
        const periodeMode = jQuery("#periode_mode").val(); // Récupère le mode actuel

        // Si le mode est "mois"
        if (periodeMode === "mois") {
            jQuery("#mode-semaine").hide(); // Masque le mode semaine
            jQuery("#mode-mois").show(); // Affiche le mode mois

            const container = jQuery("#jours-du-mois-container"); // Récupère le conteneur des jours du mois
            container.empty(); // Vide tous les champs existants

            // Si on a des jours pré-sélectionnés
            if (Array.isArray(window.joursSelectionnesMois) && window.joursSelectionnesMois.length > 0) {
                window.joursSelectionnesMois.forEach(jour => addJourDuMois(jour)); // Ajoute chaque jour existant
            } else {
                // Sinon ajoute un champ vide par défaut
                addJourDuMois();
            }
        }
    })();
});