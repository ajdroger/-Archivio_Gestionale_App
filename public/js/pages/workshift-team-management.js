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

    // Main Page Filter Logic (Team Management)
    const filterBtns = document.querySelectorAll('.tab-btn[data-filter]');
    const employeeCards = document.querySelectorAll('.employee-card');

    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.getAttribute('data-filter');

                // UI Update
                filterBtns.forEach(b => {
                    b.classList.remove('bg-indigo-600', 'text-white', 'shadow-lg');
                    b.classList.add('text-slate-400');
                });
                btn.classList.remove('text-slate-400');
                btn.classList.add('bg-indigo-600', 'text-white', 'shadow-lg');

                // Filter Logic
                employeeCards.forEach(card => {
                    const role = (card.getAttribute('data-role') || '').toLowerCase();
                    const dept = (card.getAttribute('data-department') || '').toLowerCase();
                    let show = false;

                    switch (filter) {
                        case 'all':
                            show = true;
                            break;
                        case 'management':
                            show = role.includes('manager') || role.includes('dirett') || role.includes('coordinatore') || role.includes('capo');
                            break;
                        case 'hr_admin':
                            show = dept.includes('amministrazione') || dept.includes('risorse umane') || dept.includes('security') || role.includes('admin');
                            break;
                        case 'operations':
                            // Show if NOT management AND NOT hr_admin
                            const isMgmt = role.includes('manager') || role.includes('dirett') || role.includes('coordinatore') || role.includes('capo');
                            const isHr = dept.includes('amministrazione') || dept.includes('risorse umane') || dept.includes('security') || role.includes('admin');
                            show = !isMgmt && !isHr;
                            break;
                    }

                    if (show) {
                        card.classList.remove('hidden');
                        card.parentElement && card.parentElement.classList.remove('hidden'); // Ensure parent grid item is visible if needed
                    } else {
                        card.classList.add('hidden');
                    }
                });
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

    // Validation
    if (!data.name || !data.role) {
        Swal.fire('Errore', 'Compila almeno Nome e Ruolo.', 'warning');
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
    fetch(`${window.WorkShiftBaseUrl}/workshift/api/employees/save`, {
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
    id, name, role, email, department, authGrade,
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
        document.getElementById('newEmployeeModal').querySelector('form').appendChild(idInput);
    }
    idInput.value = id;

    // Mapping
    const setVal = (id, val) => { if (document.getElementById(id)) document.getElementById(id).value = val || ''; }

    setVal('employeeName', name);
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

// ... (delete logic remains same)

// ... (fiscal code calculation remains same)

window.generateEmployeeCode = function () {
    // Robust Generation: EMP-[TimestampBase36]-[RandomBase36]
    // Uniqueness virtually guaranteed for this scale
    const timestamp = Date.now().toString(36).toUpperCase();
    const random = Math.random().toString(36).substr(2, 4).toUpperCase();
    const code = `EMP-${timestamp}-${random}`;

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
                                    <div class="font-bold text-white text-sm">${candidate.name}</div>
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
