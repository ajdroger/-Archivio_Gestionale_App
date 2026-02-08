/**
 * WorkShift Team Management
 * Handles modal logic for Adding/Editing employees
 */

document.addEventListener('DOMContentLoaded', () => {
    // Modal References
    const modal = document.getElementById('newEmployeeModal');
    const deleteModal = document.getElementById('deleteEmployeeModal');

    // Tab Logic (Modal)
    const modalTabBtns = document.querySelectorAll('.modal-tab-btn');
    const modalTabContents = document.querySelectorAll('.tab-content');

    modalTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Deactivate all
            modalTabBtns.forEach(b => {
                b.classList.remove('text-white', 'border-indigo-500');
                b.classList.add('text-slate-400', 'border-transparent');
            });
            modalTabContents.forEach(c => c.classList.add('hidden'));

            // Activate current
            btn.classList.add('text-white', 'border-indigo-500');
            btn.classList.remove('text-slate-400', 'border-transparent');
            const targetId = btn.getAttribute('data-tab');
            document.getElementById(targetId).classList.remove('hidden');
        });
    });

    // Main Search Bar Logic (Cerca Team) - Surgical Fix
    const mainSearchInput = document.getElementById('teamSearchInput');

    if (mainSearchInput) {
        // Debounce for performance if list is large, but direct input is fine for < 100 items
        mainSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            // Query selector inside event to ensure freshness (though list is static mostly)
            const cards = document.querySelectorAll('.employee-card');

            cards.forEach(card => {
                // DATA-DRIVEN CHECK (Robust)
                // We added data-name="{{name}}" to template to ensure this works perfectly
                const name = (card.getAttribute('data-name') || card.innerText).toLowerCase();
                const role = (card.getAttribute('data-role') || '').toLowerCase();
                const department = (card.getAttribute('data-department') || '').toLowerCase();

                // 1. Check Search Query
                const matchesSearch = name.includes(query) || role.includes(query) || department.includes(query);

                // 2. Check Active Tab (Context Awareness)
                const activeTab = document.querySelector('.tab-btn[data-filter].bg-indigo-600');
                const activeFilter = activeTab ? activeTab.getAttribute('data-filter') : 'all';

                let matchesTab = false;
                switch (activeFilter) {
                    case 'all':
                        matchesTab = true;
                        break;
                    case 'management':
                        matchesTab = role.includes('manager') || role.includes('dirett') || role.includes('coordinatore') || role.includes('capo');
                        break;
                    case 'hr_admin':
                        matchesTab = department.includes('amministrazione') || department.includes('risorse umane') || department.includes('security') || role.includes('admin');
                        break;
                    case 'operations':
                        const isMgmt = role.includes('manager') || role.includes('dirett') || role.includes('coordinatore') || role.includes('capo');
                        const isHr = department.includes('amministrazione') || department.includes('risorse umane') || department.includes('security') || role.includes('admin');
                        matchesTab = !isMgmt && !isHr;
                        break;
                }

                // Final Visibility Decision
                if (matchesSearch && matchesTab) {
                    card.classList.remove('hidden');
                    // If card is wrapped in a grid col div (which mustache logic implies it isn't directly, but just in case of future layout changes)
                    // The current template has card as the direct grid item, so this is fine. 
                    // However, sometimes grid items are wrappers. Let's check parent.
                    // In the template: <div class="employee-card ..."> IS the grid item.
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    }

    // Main Page Filter Logic (Team Management - Tabs)
    // Updated to re-trigger search input logic on tab click so they stay synced
    const filterBtns = document.querySelectorAll('.tab-btn[data-filter]');

    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // UI Update
                filterBtns.forEach(b => {
                    b.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg');
                    b.classList.add('text-slate-400');
                });
                btn.classList.remove('text-slate-400');
                btn.classList.add('bg-indigo-600', 'text-white', 'shadow-lg');

                // Trigger Search Input Event to re-calc visibility
                if (mainSearchInput) {
                    mainSearchInput.dispatchEvent(new Event('input'));
                } else {
                    // Fallback if no search input exists (should not happen in this version)
                    // But for safety:
                    const filter = btn.getAttribute('data-filter');
                    const employeeCards = document.querySelectorAll('.employee-card');
                    // ... (basic fallback logic omitted as we rely on search bar existence now)
                }
            });
        });
    }
});

// Global functions for inline onclick handlers
window.openModal = function () {
    document.getElementById('newEmployeeModal').classList.remove('hidden');
    document.getElementById('modalTitle').innerText = 'Nuovo Operatore';
    // Clear form
    document.querySelectorAll('#newEmployeeModal input, #newEmployeeModal select, #newEmployeeModal textarea').forEach(input => input.value = '');
}

window.closeModal = function () {
    document.getElementById('newEmployeeModal').classList.add('hidden');
}

window.saveEmployee = function () {
    // Gather data - FULL COVERAGE
    const data = {
        // IDs
        id: document.getElementById('employeeId') ? document.getElementById('employeeId').value : null,
        employee_code: document.getElementById('employeeCode').value,
        fiscal_code: document.getElementById('employeeFiscalCode').value,

        // Identity
        name: document.getElementById('employeeName').value,
        surname: document.getElementById('employeeSurname').value,
        gender: document.getElementById('employeeGender').value,
        birth_date: document.getElementById('employeeBirthDate').value,
        birth_place: document.getElementById('employeeBirthPlace').value,

        // Contact
        email: document.getElementById('employeeEmail').value,
        phone: document.getElementById('employeePhone').value,
        mobile: document.getElementById('employeeMobile').value,
        address: document.getElementById('employeeAddress').value,
        city: document.getElementById('employeeCity').value,
        zip: document.getElementById('employeeZip').value,

        // Work
        role: document.getElementById('employeeRole').value,
        department: document.getElementById('employeeDepartment').value,
        contract_type: document.getElementById('employeeContractType').value,
        contract_start: document.getElementById('employeeContractStart').value,
        // contract_end: not in form yet

        // System / Extra
        auth_grade: document.getElementById('authGrade').value,
        notes: document.getElementById('employeeNotes').value
    };

    // Enhanced Validation
    const missingFields = [];
    if (!data.name) missingFields.push('Nome');
    if (!data.surname) missingFields.push('Cognome');
    if (!data.role) missingFields.push('Ruolo');
    if (!data.department) missingFields.push('Dipartimento');

    // Add more required fields based on business logic if needed
    // if (!data.email) missingFields.push('Email'); 

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

    if (!data.employee_code) {
        generateEmployeeCode();
        data.employee_code = document.getElementById('employeeCode').value;
    }

    Swal.fire({
        title: 'Salvataggio...',
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Real API Call
    fetch(`${window.WorkShiftBaseUrl}/api/employees/save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire('Successo', 'Scheda salvata correttamente.', 'success')
                    .then(() => location.reload());
            } else {
                throw new Error(result.error || 'Errore sconosciuto');
            }
        })
        .catch(error => {
            console.error('Save failed:', error);
            Swal.fire('Errore', 'Impossibile salvare il dipendente. ' + error.message, 'error');
        });
}

// Updated Signature to match Mustache Template explicitly
window.openEditModal = function (
    id, name, surname, role, email, department, authGrade,
    code, fiscalCode, dob, pob, gender,
    address, city, zip, phone, mobile,
    contractType, contractStart, contractEnd, notes, skills
) {
    openModal();
    document.getElementById('modalTitle').innerText = 'Modifica Operatore';

    // Create hidden ID field if not exists
    let idInput = document.getElementById('employeeId');
    if (!idInput) {
        idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.id = 'employeeId';
        // Fix: Append to the modal body container instead of non-existent form
        document.querySelector('#newEmployeeModal .custom-scrollbar').appendChild(idInput);
    }
    idInput.value = id;

    // Mapping
    const setVal = (id, val) => { if (document.getElementById(id)) document.getElementById(id).value = val || ''; }

    setVal('employeeName', name);
    setVal('employeeSurname', surname);
    setVal('employeeRole', role);
    setVal('employeeEmail', email);
    setVal('employeeDepartment', department);
    setVal('authGrade', authGrade);

    setVal('employeeCode', code);
    setVal('employeeFiscalCode', fiscalCode);

    setVal('employeeBirthDate', dob);
    setVal('employeeBirthPlace', pob);
    setVal('employeeGender', gender);

    setVal('employeeAddress', address);
    setVal('employeeCity', city);
    setVal('employeeZip', zip);
    setVal('employeePhone', phone);
    setVal('employeeMobile', mobile);

    setVal('employeeContractType', contractType);
    setVal('employeeContractStart', contractStart);
    // setVal('employeeContractEnd', contractEnd); // If added later

    setVal('employeeNotes', notes); // Assuming notes covres skills too for now or append
    // if (skills) document.getElementById('employeeNotes').value += "\nSKILLS: " + skills;
}

// Delete Employee Logic
let employeeToDeleteId = null;

window.deleteEmployee = function (id, name) {
    employeeToDeleteId = id;
    document.getElementById('deleteEmployeeName').innerText = name;
    document.getElementById('deleteEmployeeModal').classList.remove('hidden');
}

window.closeDeleteModal = function () {
    document.getElementById('deleteEmployeeModal').classList.add('hidden');
    employeeToDeleteId = null;
}

window.confirmDelete = function () {
    if (!employeeToDeleteId) return;

    const btn = document.getElementById('confirmDeleteBtn');
    const originalText = btn.innerText;
    btn.innerText = 'Eliminazione...';
    btn.disabled = true;

    fetch(`${window.WorkShiftBaseUrl}/api/employees/${employeeToDeleteId}`, {
        method: 'DELETE'
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Use Swal if available, else alert
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Eliminato', 'Dipendente rimosso con successo.', 'success')
                        .then(() => location.reload());
                } else {
                    alert('Dipendente eliminato.');
                    location.reload();
                }
            } else {
                throw new Error(data.error || 'Errore durante l\'eliminazione');
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Errore', err.message, 'error');
            } else {
                alert('Errore: ' + err.message);
            }
        })
        .finally(() => {
            btn.innerText = originalText;
            btn.disabled = false;
            closeDeleteModal();
        });
}

// Fiscal Code Logic (Real Calculation via API)
window.generateFiscalCode = function () {
    const name = document.getElementById('employeeName').value.trim();
    const surname = document.getElementById('employeeSurname').value.trim();
    const birthDate = document.getElementById('employeeBirthDate').value;
    const gender = document.getElementById('employeeGender').value;
    const birthPlace = document.getElementById('employeeBirthPlace').value;

    if (!name || !surname || !birthDate || !gender || !birthPlace) {
        Swal.fire({
            title: 'Dati Mancanti',
            text: 'Per calcolare il Codice Fiscale, inserisci: Nome, Cognome, Data di Nascita, Sesso e Luogo di Nascita.',
            icon: 'warning',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    // Use explicit fields
    const nome = name;
    const cognome = surname;

    const btn = document.getElementById('btn-calc-cf'); // Hypothetical button if exists
    if (btn) {
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    }

    Swal.fire({
        title: 'Calcolo in corso...',
        didOpen: () => Swal.showLoading(),
        background: '#0f172a',
        color: '#fff'
    });

    // Construct correct URL: WorkShiftBaseUrl is ".../public/workshift", we need ".../public/soci/calcola-cf"
    const appBaseUrl = window.WorkShiftBaseUrl.replace(/\/workshift$/, '');

    let payload = {
        nome: nome,
        cognome: cognome,
        data_nascita: birthDate,
        sesso: gender,
        luogo: birthPlace
    };

    if (window.CSRF) {
        payload.csrf_name = window.CSRF.name;
        payload.csrf_value = window.CSRF.value;
    }

    fetch(`${appBaseUrl}/soci/calcola-cf`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(data => {
            if (data.cf) {
                document.getElementById('employeeFiscalCode').value = data.cf;
                Swal.close();
                // Swal.fire('Calcolato', 'Codice Fiscale generato correttamente.', 'success'); // Optional feedback
            } else {
                throw new Error(data.error || 'Errore nel calcolo');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Errore', 'Impossibile calcolare il Codice Fiscale: ' + err.message, 'error');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
}

window.generateEmployeeCode = function () {
    // Standard Format: OP-[FiscalCode]
    const cf = document.getElementById('employeeFiscalCode').value.toUpperCase().trim();

    if (!cf || cf.length < 16) {
        // Try to generate CF first if missing
        const generatedCf = generateFiscalCode();
        if (!generatedCf) {
            // Fallback if data missing for CF too
            return;
        }
        // Re-read after generation
        setTimeout(() => {
            const newCf = document.getElementById('employeeFiscalCode').value.toUpperCase().trim();
            if (newCf) {
                const code = `OP-${newCf}`;
                document.getElementById('employeeCode').value = code;
            }
        }, 1000); // Wait for API
        return;
    }

    const code = `OP-${cf}`;
    const input = document.getElementById('employeeCode');
    if (input) input.value = code;
    return code;
}

// Search Logic for New Operator
let searchTimeout = null;

// Expose globally with a UNIQUE name
window.searchCandidatesUnified = function (query) {
    if (searchTimeout) clearTimeout(searchTimeout);

    const dropdown = document.getElementById('candidateDropdown');
    const input = document.getElementById('employeeName');

    // Position dropdown exactly below input (failsafe)
    // dropdown.style.width = input.offsetWidth + 'px'; // Optional: match width

    searchTimeout = setTimeout(() => {
        console.log('Searching for:', query || '[Empty]'); // DEBUG

        fetch(`${window.WorkShiftBaseUrl}/api/candidates?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                dropdown.innerHTML = '';
                if (data.length > 0) {
                    dropdown.classList.remove('hidden');
                    data.forEach(candidate => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-3 hover:bg-white/5 cursor-pointer border-b border-white/5 last:border-0';
                        const icon = candidate.source === 'Team' ? 'users' : 'shield';
                        const color = candidate.source === 'Team' ? 'text-indigo-400' : 'text-emerald-400';

                        item.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-white text-sm">${candidate.name} ${candidate.surname || ''}</div>
                                    <div class="text-xs text-slate-400">${candidate.role || 'N/A'}</div>
                                </div>
                                <div class="flex items-center gap-2 text-xs ${color} font-medium bg-slate-800 px-2 py-1 rounded">
                                    <i data-feather="${icon}" class="w-3 h-3"></i> ${candidate.source}
                                </div>
                            </div>
                        `;
                        item.onclick = (e) => {
                            e.stopPropagation();
                            selectCandidate(candidate);
                        };
                        dropdown.appendChild(item);
                    });
                    if (window.feather) feather.replace();
                } else {
                    dropdown.classList.add('hidden');
                }
            })
            .catch(err => console.error('Search failed', err));
    }, 150);
}

// Attach listeners explicitly when DOM loads
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('employeeName');
    if (nameInput) {
        // Remove inline handlers to be safe (programmatically if possible, but easier to just override)
        nameInput.oninput = (e) => window.searchCandidatesUnified(e.target.value);
        nameInput.onfocus = (e) => window.searchCandidatesUnified(e.target.value);
        // Also trigger on click to be sure
        nameInput.onclick = (e) => window.searchCandidatesUnified(e.target.value);
    }
});

window.selectCandidate = function (candidate) {
    console.log('Selected:', candidate);
    document.getElementById('employeeName').value = candidate.name;
    document.getElementById('employeeSurname').value = candidate.surname || ''; // Use surname if available
    document.getElementById('employeeRole').value = candidate.role;
    if (candidate.email) document.getElementById('employeeEmail').value = candidate.email;

    // Hide dropdown
    document.getElementById('candidateDropdown').classList.add('hidden');
    document.getElementById('candidateDropdown').innerHTML = '';

    // Set Codes if available, else generate
    if (candidate.fiscal_code) {
        document.getElementById('employeeFiscalCode').value = candidate.fiscal_code;
    } else if (!document.getElementById('employeeFiscalCode').value) {
        generateFiscalCode();
    }

    if (candidate.employee_code) {
        document.getElementById('employeeCode').value = candidate.employee_code;
    } else {
        generateEmployeeCode();
    }
}

// Close dropdown on click outside
document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('candidateDropdown');
    const input = document.getElementById('employeeName');
    if (!dropdown.contains(e.target) && e.target !== input) {
        dropdown.classList.add('hidden');
    }
});
