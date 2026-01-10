document.addEventListener('DOMContentLoaded', function () {

    // 1. Restore Select Values (Mustache is logic-less, so we use JS)
    document.querySelectorAll('select[data-value]').forEach(select => {
        const value = select.getAttribute('data-value');
        if (value) {
            select.value = value;
        }
    });

    // 2. Profile Type Logic (Copy of Create logic + auto-detect)
    const tipoProfiloSelect = document.getElementById('tipo_profilo');
    const tabMilitareBtn = document.getElementById('tab-militare');

    // Auto-detect profile based on existing data if not explicitly set
    // (This heuristic assumes if 'grado' exists, it's military)
    const initialGrado = document.querySelector('input[name="grado"]').value;
    if (initialGrado && tipoProfiloSelect.value === '') {
        tipoProfiloSelect.value = 'MILITARE';
    } else if (tipoProfiloSelect.value === '') {
        // Default fallbacks
        tipoProfiloSelect.value = 'CIVILE';
    }

    function updateTabsVisibility() {
        const selectedType = tipoProfiloSelect.value;
        const isMilitare = selectedType === 'MILITARE';

        if (tabMilitareBtn) {
            if (isMilitare) {
                tabMilitareBtn.classList.remove('disabled', 'opacity-50');
                tabMilitareBtn.removeAttribute('disabled');
            } else {
                tabMilitareBtn.classList.add('disabled', 'opacity-50');
                tabMilitareBtn.setAttribute('disabled', 'true');

                // If on military tab and switching to civilian, go to first tab
                if (tabMilitareBtn.classList.contains('active')) {
                    const firstTab = new bootstrap.Tab(document.getElementById('tab-anagrafica'));
                    firstTab.show();
                }
            }
        }
    }

    if (tipoProfiloSelect) {
        tipoProfiloSelect.addEventListener('change', function () {
            updateTabsVisibility();
            // Optional warning when switching away from Military
            if (this.value !== 'MILITARE' && initialGrado) {
                if (!confirm('Attenzione: Passando a profilo Civile, i dati militari verranno nascosti e potenzialmente rimossi al salvataggio. Continuare?')) {
                    this.value = 'MILITARE';
                    updateTabsVisibility();
                } else {
                    // Clear fields
                    document.querySelectorAll('#panel-militare input').forEach(input => input.value = '');
                }
            }
        });

        // Init
        updateTabsVisibility();
    }

    // 3. CF Calculation Logic (Reused)
    const btnCalcCF = document.getElementById('btn-calc-cf');
    if (btnCalcCF) {
        btnCalcCF.addEventListener('click', async function () {
            const btn = this;
            const originalHtml = btn.innerHTML;

            const nome = document.getElementById('nome').value;
            const cognome = document.getElementById('cognome').value;
            const dataNascita = document.getElementById('data_nascita').value;
            const sesso = document.getElementById('sesso').value;
            const luogo = document.getElementById('luogo_nascita').value;

            if (!nome || !cognome || !dataNascita || !sesso || !luogo) {
                alert('Compilare tutti i campi anagrafici essenziali per calcolare il CF.');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const formData = new URLSearchParams();
                formData.append('nome', nome);
                formData.append('cognome', cognome);
                formData.append('data_nascita', dataNascita);
                formData.append('sesso', sesso);
                formData.append('luogo', luogo);

                const csrfName = document.querySelector('input[name="csrf_name"]');
                const csrfValue = document.querySelector('input[name="csrf_value"]');
                if (csrfName && csrfValue) {
                    formData.append('csrf_name', csrfName.value);
                    formData.append('csrf_value', csrfValue.value);
                }

                // Endpoint resolution logic
                // Action: .../soci/{cf}/aggiorna -> need .../soci/calcola-cf 
                // We can't rely just on splitting the action because of the parameter.
                // Best bet: use the base_url defined in a meta tag or data attribute, 
                // but since we are in a subfolder structure, let's look at the action.

                // Assuming action ends in /aggiorna
                const form = document.getElementById('dossierForm');
                const formAction = form.getAttribute('action'); // .../soci/CF/aggiorna

                // Construct: .../soci/calcola-cf
                // Split by '/'
                const parts = formAction.split('/');
                // Remove 'aggiorna' (last) and 'CF' (second to last)
                parts.pop();
                parts.pop();
                const endpoint = parts.join('/') + '/calcola-cf';

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                const data = await res.json();

                if (res.ok && data.cf) {
                    document.getElementById('codice_fiscale').value = data.cf;
                } else {
                    alert('Errore: ' + (data.error || 'Sconosciuto'));
                }
            } catch (e) {
                console.error(e);
                alert('Errore di comunicazione.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
});
