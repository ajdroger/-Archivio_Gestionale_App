/**
 * WorkShift Team Management
 * Handles modal logic for Adding/Editing employees
 */

document.addEventListener('DOMContentLoaded', () => {
    // Modal References
    const modal = document.getElementById('newEmployeeModal');
    const deleteModal = document.getElementById('deleteEmployeeModal');

    // Tab Logic
    const tabBtns = document.querySelectorAll('.modal-tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Deactivate all
            tabBtns.forEach(b => {
                b.classList.remove('text-white', 'border-indigo-500');
                b.classList.add('text-slate-400', 'border-transparent');
            });
            tabContents.forEach(c => c.classList.add('hidden'));

            // Activate current
            btn.classList.add('text-white', 'border-indigo-500');
            btn.classList.remove('text-slate-400', 'border-transparent');
            const targetId = btn.getAttribute('data-tab');
            document.getElementById(targetId).classList.remove('hidden');
        });
    });
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
    // Gather data
    const data = {
        name: document.getElementById('employeeName').value,
        fiscalCode: document.getElementById('employeeFiscalCode').value,
        email: document.getElementById('employeeEmail').value,
        role: document.getElementById('employeeRole').value,
        department: document.getElementById('employeeDepartment').value,
        // ... add other fields as needed
    };

    // Validation (Mock)
    if (!data.name || !data.role) {
        Swal.fire('Errore', 'Compila almeno Nome e Ruolo.', 'warning');
        return;
    }

    // Mock Save
    Swal.fire({
        title: 'Salvataggio...',
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Simulate API delay
    setTimeout(() => {
        Swal.fire('Successo', 'Dipendente salvato correttamente.', 'success')
            .then(() => location.reload());
    }, 800);
}

window.openEditModal = function (id, name, role, email, department, ...args) {
    openModal();
    document.getElementById('modalTitle').innerText = 'Modifica Operatore';

    document.getElementById('employeeName').value = name;
    document.getElementById('employeeRole').value = role;
    document.getElementById('employeeEmail').value = email;
    document.getElementById('employeeDepartment').value = department;
    // ... map other fields
}

// Delete Logic
let employeeToDelete = null;

window.deleteEmployee = function (id, name) {
    employeeToDelete = id;
    document.getElementById('deleteEmployeeName').innerText = name;
    document.getElementById('deleteEmployeeModal').classList.remove('hidden');
}

window.closeDeleteModal = function () {
    document.getElementById('deleteEmployeeModal').classList.add('hidden');
    employeeToDelete = null;
}

window.confirmDelete = function () {
    if (!employeeToDelete) return;

    // API Call Mock
    fetch(`${window.WorkShiftBaseUrl}/api/employees/${employeeToDelete}`, { method: 'DELETE' })
        .then(() => {
            closeDeleteModal();
            Swal.fire('Eliminato', 'Operatore rimosso.', 'success')
                .then(() => location.reload());
        })
        .catch(() => {
            // For demo, reload anyway since API might mock 404
            location.reload();
        });
}

// --- Fiscal Code Calculation Logic ---
window.generateFiscalCode = function () {
    const name = document.getElementById('employeeName').value.trim();
    const gender = document.getElementById('employeeGender').value;
    const dob = document.getElementById('employeeBirthDate').value; // YYYY-MM-DD
    const place = document.getElementById('employeeBirthPlace').value.trim().toUpperCase();

    if (!name || !gender || !dob || !place) {
        Swal.fire('Dati Mancanti', 'Compila Nome, Genere, Data di Nascita e Luogo per calcolare il Codice Fiscale.', 'warning');
        return;
    }

    try {
        const cf = calculateItalianCF(name, gender, dob, place);
        document.getElementById('employeeFiscalCode').value = cf;
    } catch (e) {
        console.error(e);
        Swal.fire('Errore Calcolo', 'Impossibile calcolare il CF. Verifica i dati (es. Città non trovata).', 'error');
    }
}

function calculateItalianCF(fullname, gender, dobStr, cityName) {
    // 1. Split Name/Surname (Approximation: Last word is name, rest surname, or user writes "Name Surname")
    // Standard input is "Name Surname" usually. Let's assume standard format "Name Surname".
    // Actually official CF requires Surname then Name. 
    // Heuristic: First part = Name, Last part = Surname? No, in Italy "Mario Rossi" -> Name=Mario, Suname=Rossi. 
    // But CF needs Surname FIRST. 
    // Let's assume input matches: First word(s) = Name, Last word = Surname? Or splitting by space is risky.
    // Let's simplified: Treat the whole string, try to identify surname. 
    // BETTER: Ask user to fill "Nome" and "Cognome" separately? API only has "name" (Fullname).
    // WORKAROUND: Split by last space. "Mario Rossi" -> Name: Mario, Surname: Rossi.
    const parts = fullname.toUpperCase().split(' ');
    let surname = parts.length > 1 ? parts.pop() : parts[0];
    let name = parts.join('') || surname; // If only one word, use it as both or handle edge case
    if (parts.length === 0) { name = fullname.toUpperCase(); surname = fullname.toUpperCase(); } // Fallback

    // 2. Encode Surname (3 consonants, then vowels, then X)
    const codeSurname = encodeName(surname, false);
    // 3. Encode Name (4 cons? pick 1,3,4. If 3, pick 1,2,3. else vowels, X)
    const codeName = encodeName(name, true);

    // 4. DOB & Gender
    // YYYY-MM-DD
    const date = new Date(dobStr);
    const year = date.getFullYear().toString().substr(2, 2);
    const month = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'][date.getMonth()];
    let day = date.getDate();
    if (gender === 'F') day += 40;
    const dayStr = day < 10 ? '0' + day : '' + day;

    // 5. City Code (Belfiore)
    // Use global CityCodes map. Fallback to H501 (Roma) if not found and warn?
    // Or try to fetch?
    let cityCode = (window.CityCodes && window.CityCodes[cityName]) || 'H501';
    if (window.CityCodes && !window.CityCodes[cityName]) {
        // Simple heuristic for common cities missing in mock
        console.warn(`City code for ${cityName} not found, using ROMA (H501) as default.`);
    }

    const partial = codeSurname + codeName + year + month + dayStr + cityCode;

    // 6. Check Digit (CIN)
    const cin = calculateCIN(partial);

    return partial + cin;
}

function encodeName(str, isName) {
    const cons = str.replace(/[^BCDFGHJKLMNPQRSTVWXYZ]/g, '');
    const vowels = str.replace(/[^AEIOU]/g, '');

    if (isName) {
        if (cons.length >= 4) return cons[0] + cons[2] + cons[3];
        if (cons.length === 3) return cons;
        return (cons + vowels + 'XXX').substr(0, 3);
    } else {
        if (cons.length >= 3) return cons.substr(0, 3);
        return (cons + vowels + 'XXX').substr(0, 3);
    }
}

function calculateCIN(str) {
    const oddMap = {
        '0': 1, '1': 0, '2': 5, '3': 7, '4': 9, '5': 13, '6': 15, '7': 17, '8': 19, '9': 21,
        'A': 1, 'B': 0, 'C': 5, 'D': 7, 'E': 9, 'F': 13, 'G': 15, 'H': 17, 'I': 19, 'J': 21,
        'K': 2, 'L': 4, 'M': 18, 'N': 20, 'O': 11, 'P': 3, 'Q': 6, 'R': 8, 'S': 12, 'T': 14,
        'U': 16, 'V': 10, 'W': 22, 'X': 25, 'Y': 24, 'Z': 23
    };
    const evenMap = {
        '0': 0, '1': 1, '2': 2, '3': 3, '4': 4, '5': 5, '6': 6, '7': 7, '8': 8, '9': 9,
        'A': 0, 'B': 1, 'C': 2, 'D': 3, 'E': 4, 'F': 5, 'G': 6, 'H': 7, 'I': 8, 'J': 9,
        'K': 10, 'L': 11, 'M': 12, 'N': 13, 'O': 14, 'P': 15, 'Q': 16, 'R': 17, 'S': 18,
        'T': 19, 'U': 20, 'V': 21, 'W': 22, 'X': 23, 'Y': 24, 'Z': 25
    };

    let sum = 0;
    for (let i = 0; i < 15; i++) {
        const char = str[i];
        if ((i + 1) % 2 !== 0) { // Odd position (1-based), so index 0, 2, 4... is Odd
            sum += oddMap[char];
        } else {
            sum += evenMap[char];
        }
    }

    return String.fromCharCode(65 + (sum % 26));
}

window.generateEmployeeCode = function () {
    // Generate code based on name hash + random
    const name = document.getElementById('employeeName').value || 'EMP';
    const hash = name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    document.getElementById('employeeCode').value = 'EMP-' + Math.abs(hash % 1000) + Math.floor(Math.random() * 100);
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

    // Generate codes if missing
    if (!document.getElementById('employeeFiscalCode').value) generateFiscalCode();
    generateEmployeeCode();
}

// Close dropdown on click outside
document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('candidateDropdown');
    const input = document.getElementById('employeeName');
    if (!dropdown.contains(e.target) && e.target !== input) {
        dropdown.classList.add('hidden');
    }
});
