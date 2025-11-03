/* 
 * @author Geoffrey
 */

jQuery(document).ready(function () {
    // Initialisation du DataTable
    var tableTachesCron = $('#datatableTachesCron').DataTable({
        responsive: true,
        autoWidth: false,
        ordering: false,
        paging: false,
        info: false,
        ajax: {
            url: "/admin/tableau-bord/_ajax/taches-cron.php"
        },
        columns: [
            {
                data: null,
                width: "2%",
                orderable: false,
                className: "text-center",
                render: function (data, type, row) {
                    return '<input type="checkbox" class="checkbox-tache" value="' + row.id + '">'; // Affiche une checkbox pour chaque ligne, avec l'ID de la tâche
                }
            },
            {data: "libelle", width: "15%"},
            {data: "description", width: "30%"},
            {data: "fichier", width: "18%"},
            {data: "periode_lancement", width: "8%"},
            {data: "heure_lancement", width: "8%"},
            {data: "etat", width: "4%"},
            {data: "action", width: "3%", orderable: false}
        ],
        oLanguage: {sUrl: "/lang/datatable/fr.json"},
        drawCallback: function () {
            // Vérifie si la checkbox "Select All" existe déjà dans le header
            if ($('#selectAllTaches').length === 0) {
                // Ajoute la checkbox globale dans la première colonne du header
                $('#datatableTachesCron thead th').first().html('<input type="checkbox" id="selectAllTaches">');
            }

            // Gestion du clic sur la checkbox de sélectionner de toutes les tâches cron
            $('#selectAllTaches').off('click').on('click', function () {
                var checked = $(this).prop('checked'); // Récupère l'état coché ou non
                $('#datatableTachesCron tbody .checkbox-tache').prop('checked', checked); // Applique cet état à toutes les checkboxes de chaque ligne
            });
        }
    });

    // Gestion du bouton "Supprimer"
    $('#btnSupprimerTachesCron').on('click', function (e) {
        e.preventDefault(); // Empêche le comportement par défaut du bouton

        $('.alert-danger-checkbox').addClass('display-hide'); // Masque le message d'erreur s'il était affiché

        // Récupérer les tâches sélectionnées
        var selectedIds = []; // ID des tâches sélectionnées
        var selectedCronClasses = []; // Noms des tâches à afficher dans le modal

        // Parcourt chaque checkbox cochée
        $('#datatableTachesCron tbody .checkbox-tache:checked').each(function () {
            selectedIds.push($(this).val()); // Ajoute l'ID de la tâche

            var row = $(this).closest('tr'); // Accéder à la ligne du tableau de la checkbox
            var cronClass = row.find('td').eq(1).text().trim(); // Récupérer le texte de la colonne "libelle" (cron_class) dans cette ligne
            selectedCronClasses.push(cronClass); // Ajouter le nom de la tâche sélectionnée dans le tableau pour le modal
        });

        // Si aucune tâche n'est sélectionnée
        if (selectedIds.length === 0) {
            $('.alert-danger-checkbox').removeClass('display-hide'); // On affiche l'alerte
            return; // Stoppe l'exécution
        }

        var modalForm = $('#FormSuppressionTachesCron'); // Préparer le formulaire pour la soumission
        modalForm.find('input[name="taches_ids[]"]').remove(); // Supprime les anciennes valeurs pour éviter les doublons

        // Pour chaque tâche sélectionnée
        selectedIds.forEach(function (id) {
            modalForm.append('<input type="hidden" name="taches_ids[]" value="' + id + '">'); // Ajouter le champ hidden
        });

        // Création de la liste HTML en gras
        var listeHtml = '<ul>'; // Début de la liste non ordonnée

        // Parcourt chaque cron_class sélectionnée pour créer un <li> correspondant
        selectedCronClasses.forEach(function (cronClass) {
            listeHtml += '<li><strong>' + cronClass + '</strong></li>'; // Chaque tâche est affichée en gras grâce à <strong>
        });
        listeHtml += '</ul>'; // Fermeture de la liste non ordonnée

        $('#liste-taches-selectionnees').html(listeHtml); // Injection uniquement dans le conteneur prévu
        $('#modal-supprimer-taches-cron').modal('show'); // Affiche le modal
    });
});