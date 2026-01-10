/**
 * Socio List Page Logic
 * Location: public/js/pages/socio_list.js
 * 
 * Handles Delete Modal interactions and DataTables initialization.
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- 1. Delete Modal Logic ---
    var deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var cf = button.getAttribute('data-cf');
            var nome = button.getAttribute('data-nome');

            var modalBodyName = deleteModal.querySelector('#modal-delete-name');
            var modalForm = deleteModal.querySelector('#modal-delete-form');

            if (modalBodyName) modalBodyName.textContent = nome;

            // Construct the action URL dynamically based on base_url
            // We assume base_url is available via a global or data attribute.
            // Failing that, we rely on relative paths or a pre-defined data-base-url on body.
            // Best practice: use data-action-template on the form.

            // However, the original code used: modalForm.action = '{{base_url}}/soci/' + cf + '/elimina';
            // We need to access base_url. It is commonly attached to window or body.
            // Let's check for window.BASE_URL (from layout_header)

            const baseUrl = window.BASE_URL || '';
            if (modalForm) modalForm.action = baseUrl + '/soci/' + cf + '/elimina';
        });
    }

    // --- 2. DataTables Initialization ---
    var table = $('.smart-table').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 10,
        "orderCellsTop": true, // Critical for input filters
        "language": {
            "search": "<i class='fa-solid fa-search text-secondary me-2'></i>Cerca in tutto:",
            "lengthMenu": "Mostra _MENU_",
            "info": "Record _START_-_END_ di _TOTAL_",
            "infoEmpty": "Nessun dato",
            "infoFiltered": "(su _MAX_)",
            "paginate": {
                "first": '<i class="fa-solid fa-angles-left"></i>',
                "last": '<i class="fa-solid fa-angles-right"></i>',
                "next": '<i class="fa-solid fa-angle-right"></i>',
                "previous": '<i class="fa-solid fa-angle-left"></i>'
            },
            "zeroRecords": "Nessun socio trovato con questi criteri"
        },
        "dom": '<"d-flex justify-content-between align-items-center mb-3"<"text-secondary"l><"search-box"f>>t<"d-flex justify-content-between align-items-center mt-3"<"text-secondary small"i><"pagination-box"p>>',
        "initComplete": function () {
            // Custom Styling for Dark Theme
            $('.dataTables_filter input').addClass('form-control form-control-sm bg-dark text-white border-secondary').css('display', 'inline-block').css('width', 'auto');
            $('.dataTables_length select').addClass('form-select form-select-sm bg-dark text-white border-secondary').css('display', 'inline-block').css('width', 'auto');
            $('.page-link').addClass('bg-dark text-secondary border-secondary');
            $('.page-item.active .page-link').addClass('bg-primary text-white border-primary').removeClass('bg-dark text-secondary');

            // Style Pagination on draw
            this.api().on('draw', function () {
                $('.page-link').addClass('bg-dark text-secondary border-secondary');
                $('.page-item.active .page-link').removeClass('bg-dark text-secondary').addClass('bg-primary text-white border-primary');
            });
        }
    });
});
