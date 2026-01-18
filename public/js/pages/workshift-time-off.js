/**
 * WorkShift Time Off Management
 * Handles Leave Requests, Filtering, and API Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // Determine Base URL
    const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
    const client = new WorkShiftAPI(baseUrl);

    console.log('[TimeOff] Initialized');

    // === State ===
    const state = {
        employees: []
    };

    // === Elements ===
    const els = {
        modal: document.getElementById('requestModal'),
        btnSave: document.getElementById('btnSaveRequest'),
        filters: document.querySelectorAll('.filter-btn'),
        items: document.querySelectorAll('.request-item'),
        inputs: {
            employee: document.getElementById('reqEmployee'),
            start: document.getElementById('reqStart'),
            end: document.getElementById('reqEnd'),
            reason: document.getElementById('reqReason')
        }
    };

    // === Initialization ===
    loadEmployees();

    // === Event Listeners ===

    // Filter Logic
    els.filters.forEach(btn => {
        btn.addEventListener('click', () => {
            // UI toggle
            els.filters.forEach(b => {
                b.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg', 'active');
                b.classList.add('bg-slate-800', 'text-gray-400');
            });
            btn.classList.remove('bg-slate-800', 'text-gray-400');
            btn.classList.add('bg-indigo-600', 'text-white', 'shadow-lg', 'active');

            // Filter logic
            const filter = btn.dataset.filter;
            filterRequests(filter);
        });
    });

    // Save Request
    if (els.btnSave) {
        els.btnSave.addEventListener('click', saveRequest);
    }

    // === Functions ===

    async function loadEmployees() {
        try {
            const employees = await client.getEmployees();
            state.employees = employees;
            populateEmployeeSelect(employees);
        } catch (error) {
            console.error('Failed to load employees', error);
        }
    }

    function populateEmployeeSelect(employees) {
        const select = els.inputs.employee;
        if (!select) return;

        select.innerHTML = '<option value="">Seleziona...</option>';
        employees.forEach(emp => {
            const opt = document.createElement('option');
            opt.value = emp.id;
            opt.textContent = `${emp.name} (${emp.role})`;
            select.appendChild(opt);
        });
    }

    function filterRequests(filter) {
        console.log('Filtering by:', filter);
        els.items.forEach(item => {
            const status = item.dataset.status;
            if (filter === 'all' || status === filter) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    }

    async function saveRequest() {
        // Validation
        const data = {
            employee_id: els.inputs.employee.value,
            start_date: els.inputs.start.value,
            end_date: els.inputs.end.value,
            reason: els.inputs.reason.value,
            type: 'Ferie' // Default for now, could add select
        };

        // Explicit Validation with Missing Fields List
        const missingFields = [];

        if (!data.employee_id) missingFields.push('Dipendente');
        if (!data.start_date) missingFields.push('Data Inizio');
        if (!data.end_date) missingFields.push('Data Fine');
        if (!data.reason || data.reason.trim() === '') missingFields.push('Motivazione');

        if (missingFields.length > 0) {
            const listHtml = `<ul style="text-align: left; margin-top: 10px; margin-left: 20px;">${missingFields.map(f => `<li>• <b>${f}</b></li>`).join('')}</ul>`;

            Swal.fire({
                title: 'Dati Mancanti',
                html: `Per favore compila i seguenti campi obbligatori:<br>${listHtml}`,
                icon: 'warning',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#4f46e5'
            });
            return;
        }

        // Send
        try {
            els.btnSave.disabled = true;
            els.btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio...';

            const res = await client.saveRequest(data);

            if (res.success) {
                // 1. Close Modal Immediately
                document.getElementById('requestModal').classList.add('hidden');

                // 2. Show Auto-Closing Success Toast
                Swal.fire({
                    title: 'Richiesta Inviata!',
                    text: 'La richiesta ferie è stata registrata correttamente.',
                    icon: 'success',
                    timer: 1500, // Auto-close after 1.5s
                    showConfirmButton: false, // No "OK" button
                    background: '#0f172a',
                    color: '#fff'
                }).then(() => {
                    // 3. Reload Page
                    location.reload();
                });
            } else {
                Swal.fire('Errore', 'Errore: ' + (res.error || 'Sconosciuto'), 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Errore', 'Errore di comunicazione col server.', 'error');
        } finally {
            els.btnSave.disabled = false;
            els.btnSave.innerHTML = '<i data-feather="check" class="w-4 h-4"></i> Invia Richiesta';
            if (window.feather) feather.replace();
        }
    }

});

// === Global Functions (Exposed for HTML onclick attributes) ===

// Update Request Status
window.updateRequest = async function (id, status) {
    if (typeof Swal === 'undefined') {
        alert('Componente SweetAlert mancante. Ricarica la pagina.');
        return;
    }

    const result = await Swal.fire({
        title: 'Aggiorna Stato',
        text: `Sei sicuro di voler impostare lo stato a "${status}"?`,
        icon: 'question',
        showCancelButton: true,
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sì, procedi',
        cancelButtonText: 'Annulla'
    });

    if (!result.isConfirmed) return;

    try {
        // Re-determine BaseURL if needed or assume Client is global?
        // Better: instantiate a temp client or rely on one if global.
        // Let's rely on standard path for APIs.
        const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
        const api = new WorkShiftAPI(baseUrl);

        const res = await api.updateRequestStatus(id, status);
        if (res.success) {
            Swal.fire({
                title: 'Aggiornato',
                text: 'Stato richiesta modificato con successo.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                background: '#0f172a',
                color: '#fff'
            }).then(() => location.reload());
        } else {
            Swal.fire('Errore', 'Errore: ' + (res.error || 'Impossibile aggiornare'), 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Errore', 'Errore di rete', 'error');
    }
};

// Delete Single Request
window.deleteRequest = async function (id) {
    if (typeof Swal === 'undefined') {
        if (confirm("Eliminare la richiesta?")) {
            // Fallback
        } else return;
    }

    const result = await Swal.fire({
        title: 'Elimina Richiesta',
        text: "Questa azione è irreversibile. Sei sicuro?",
        icon: 'warning',
        showCancelButton: true,
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sì, elimina',
        cancelButtonText: 'Annulla'
    });

    if (!result.isConfirmed) return;

    try {
        const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
        const api = new WorkShiftAPI(baseUrl);

        const res = await api.deleteRequest(id);
        if (res.success) {
            Swal.fire({
                title: 'Eliminata',
                text: 'Richiesta rimossa con successo.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                background: '#0f172a',
                color: '#fff'
            }).then(() => location.reload());
        } else {
            Swal.fire('Errore', 'Impossibile eliminare: ' + (res.error || 'Errore sconosciuto'), 'error');
        }
    } catch (e) {
        Swal.fire('Errore', 'Errore di comunicazione: ' + e.message, 'error');
    }
}

// Clear All Requests
window.clearAllRequests = async function () {
    const result = await Swal.fire({
        title: 'Svuota Bacheca',
        text: "Attenzione: Stai per eliminare TUTTE le richieste di ferie. I dati saranno persi per sempre. Confermi?",
        icon: 'warning',
        showCancelButton: true,
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sì, svuota tutto',
        cancelButtonText: 'Annulla'
    });

    if (!result.isConfirmed) return;

    try {
        const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
        const api = new WorkShiftAPI(baseUrl);

        const res = await api.resetRequests();
        if (res.success) {
            Swal.fire({
                title: 'Bacheca Svuotata',
                text: `Eliminate ${res.deleted} richieste.`,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                background: '#0f172a',
                color: '#fff'
            }).then(() => location.reload());
        } else {
            Swal.fire('Errore', 'Impossibile resettare: ' + (res.error || 'Errore sconosciuto'), 'error');
        }
    } catch (e) {
        Swal.fire('Errore', 'Errore di comunicazione: ' + e.message, 'error');
    }
}
