/**
 * socio_list.js
 * Handles Infinite Scroll for the User Directory View
 */

document.addEventListener('DOMContentLoaded', function () {

    // ADMIN view uses DataTables (Server-side rendering handled by template for now, or DataTables plugin)
    // USER view uses Infinite Scroll

    // Check if we are in User View locally by checking for the grid container
    const gridContainer = document.getElementById('user-directory-grid');

    // Check if we are in Admin View (Table)
    const adminTable = document.getElementById('table-soci');

    if (adminTable) {
        // Initialize DataTables for Admin
        $(adminTable).DataTable({
            language: {
                processing: "Elaborazione...",
                search: "Cerca:",
                lengthMenu: "Visualizza _MENU_ elementi",
                info: "Vista da _START_ a _END_ di _TOTAL_ elementi",
                infoEmpty: "Vista da 0 a 0 di 0 elementi",
                infoFiltered: "(filtrati da _MAX_ elementi totali)",
                infoPostFix: "",
                loadingRecords: "Caricamento...",
                zeroRecords: "La ricerca non ha portato alcun risultato.",
                emptyTable: "Nessun dato presente nella tabella",
                paginate: {
                    first: "Inizio",
                    previous: "Precedente",
                    next: "Successivo",
                    last: "Fine"
                },
                aria: {
                    sortAscending: ": attiva per ordinare la colonna in ordine crescente",
                    sortDescending: ": attiva per ordinare la colonna in ordine decrescente"
                }
            },
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false, // Keep layout clean
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 5 } // Disable sorting on Action column
            ]
        });
        return; // Exit, no need for infinite scroll logic
    }

    if (!gridContainer) return; // Exit if not in User View or Admin View (shouldn't happen)

    let page = 1;
    let loading = false;
    let hasMore = true;
    const sentinel = document.getElementById('scroll-sentinel');

    const loadMoreSoci = async () => {
        if (loading || !hasMore) return;

        loading = true;
        sentinel.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

        try {
            // Fetch next page from API
            page++;
            const response = await fetch(`${window.BASE_URL}/api/v1/soci?page=${page}&per_page=12`);

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            // Check if we have data
            if (!data.data || data.data.length === 0) {
                hasMore = false;
                sentinel.innerHTML = '<p class="text-secondary small">Non ci sono altri soci da visualizzare.</p>';
                return;
            }

            // Append cards
            data.data.forEach(socio => {
                const cardHtml = createSocioCard(socio);
                gridContainer.insertAdjacentHTML('beforeend', cardHtml);
            });

            // Update meta
            if (page >= data.meta.total_pages) {
                hasMore = false;
                sentinel.innerHTML = '<p class="text-secondary small">Hai raggiunto la fine della lista.</p>';
            } else {
                sentinel.innerHTML = ''; // Clear loader, wait for next intersection
            }

        } catch (error) {
            console.error('Error loading soci:', error);
            sentinel.innerHTML = '<p class="text-danger small">Errore nel caricamento. Riprova.</p>';
        } finally {
            loading = false;
        }
    };

    // Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            loadMoreSoci();
        }
    }, { rootMargin: '200px' });

    observer.observe(sentinel);

});

// Helper: Card Template Builder (Matches Mustache Template)
function createSocioCard(socio) {
    const initial = socio.nome.charAt(0) + socio.cognome.charAt(0);
    const badge = socio.stato === 'ATTIVO'
        ? '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Socio Attivo</span>'
        : '<span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-3">Non Attivo</span>';

    const role = (window.USER_ROLE || '').toLowerCase();
    const authorizedRoles = ['system_admin', 'segreteria', 'direttore_associazione', 'sviluppo'];
    const showGestione = authorizedRoles.includes(role);

    let gestioneButton = '';
    if (showGestione) {
        gestioneButton = `
            <div class="mt-2 text-center"> <!-- Added wrapper for spacing -->
                <div class="dropdown d-inline-block w-100">
                    <button class="btn btn-outline-secondary btn-sm w-100 rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                        <i class="fa-solid fa-bars me-1"></i> Gestione
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end bg-dark border-secondary shadow w-100" style="z-index: 9999;">
                        <li><h6 class="dropdown-header text-uppercase small opacity-50">Azioni Rapide</h6></li>
                        <li><a class="dropdown-item text-success fw-bold" href="${window.BASE_URL}/soci/nuovo"><i class="fa-solid fa-user-plus me-2"></i>Nuovo Socio</a></li>
                        <li><hr class="dropdown-divider border-secondary"></li>

                        <li><h6 class="dropdown-header text-uppercase small opacity-50">Anagrafica</h6></li>
                        <li><a class="dropdown-item text-white" href="${window.BASE_URL}/soci/${socio.cf}"><i class="fa-solid fa-eye me-2"></i>Dettagli</a></li>
                        <li><a class="dropdown-item text-info" href="${window.BASE_URL}/soci/${socio.cf}/edit"><i class="fa-solid fa-pen me-2"></i>Modifica</a></li>
                        <li><hr class="dropdown-divider border-secondary"></li>

                        <li><h6 class="dropdown-header text-uppercase small opacity-50">Amministrazione</h6></li>
                        <li><a class="dropdown-item text-white" href="${window.BASE_URL}/soci/${socio.cf}/impostazioni"><i class="fa-solid fa-sliders me-2"></i>Impostazioni</a></li>
                        <li>
                            <form action="${window.BASE_URL}/soci/${socio.cf}/delete" method="POST" onsubmit="return confirm('ATTENZIONE: Stai per eliminare DEFINITIVAMENTE questo socio.\\n\\nQuesta azione è IRREVERSIBILE.\\n\\nSei sicuro di voler procedere?');">
                                <button type="submit" class="dropdown-item text-danger w-100 text-start"><i class="fa-solid fa-trash me-2"></i>Elimina Definitivamente</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        `;
    }

    return `
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card h-100 border-0 shadow bg-dark text-white hover-translate-y transition-all">
                <div class="card-body text-center p-4">
                     <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <span class="fw-bold fs-4 text-primary">${initial}</span>
                    </div>
                    <h5 class="fw-bold mb-1">${socio.nome} ${socio.cognome}</h5>
                    <p class="text-secondary small mb-3">Membro dal 2024</p>
                    
                    ${badge}

                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                         <a href="${window.BASE_URL}/soci/${socio.cf}" class="btn btn-outline-light btn-sm w-100 rounded-pill mb-1">Visualizza Profilo</a>
                         ${gestioneButton}
                    </div>
                </div>
            </div>
        </div>
    `;
}
