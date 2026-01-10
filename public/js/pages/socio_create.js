document.addEventListener('DOMContentLoaded', function () {
    // Gestione Tab persistenti (opzionale, per migliorare UX se ricarichi pagina)
    /*
    const triggerTabList = [].slice.call(document.querySelectorAll('#dossierTabs button'))
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener('click', function (event) {
            event.preventDefault()
            tabTrigger.show()
        })
    })
    */

    // Gestione Tipo Profilo (Militare vs Civile)
    const tipoProfiloSelect = document.getElementById('tipo_profilo');
    const tabMilitareBtn = document.getElementById('tab-militare');

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

                // Se sono sul tab militare e passo a civile, torno al primo tab
                if (tabMilitareBtn.classList.contains('active')) {
                    const firstTab = new bootstrap.Tab(document.getElementById('tab-anagrafica'));
                    firstTab.show();
                }

                // Opzionale: Pulisci campi militari per evitare invio dati sporchi
                document.querySelectorAll('#panel-militare input').forEach(input => input.value = '');
            }
        }
    }

    if (tipoProfiloSelect) {
        tipoProfiloSelect.addEventListener('change', updateTabsVisibility);
        // Init stato iniziale
        updateTabsVisibility();
    }

    // Calcolo Codice Fiscale
    const btnCalcCF = document.getElementById('btn-calc-cf');
    if (btnCalcCF) {
        btnCalcCF.addEventListener('click', async function () {
            const btn = this;
            const originalHtml = btn.innerHTML;

            // Raccolta Dati
            const nome = document.getElementById('nome').value;
            const cognome = document.getElementById('cognome').value;
            const dataNascita = document.getElementById('data_nascita').value;
            const sesso = document.getElementById('sesso').value;
            const luogo = document.getElementById('luogo_nascita').value;

            // Validazione Client-Side
            if (!nome || !cognome || !dataNascita || !sesso || !luogo) {
                alert('Compilare tutti i campi anagrafici (Nome, Cognome, Data, Sesso, Luogo) per calcolare il CF.');
                return;
            }

            // UI Loading
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                // Preparazione Payload
                const formData = new URLSearchParams();
                formData.append('nome', nome);
                formData.append('cognome', cognome);
                formData.append('data_nascita', dataNascita);
                formData.append('sesso', sesso);
                formData.append('luogo', luogo);

                // CSRF Token (presupponendo campi hidden nel form)
                const csrfName = document.querySelector('input[name="csrf_name"]');
                const csrfValue = document.querySelector('input[name="csrf_value"]');
                if (csrfName && csrfValue) {
                    formData.append('csrf_name', csrfName.value);
                    formData.append('csrf_value', csrfValue.value);
                }

                // Chiamata AJAX (path relativo/assoluto gestito da base_url nel template o rilevamento automatico)
                // NOTA: BASE_URL deve essere definito globalmente o recuperato dal form action
                const form = document.getElementById('dossierForm');
                const formAction = form ? form.getAttribute('action') : '';
                // Trick: usiamo URL relativo se possibile, o ricostruiamo l'endpoint
                // Se l'action è .../soci/salva, l'endpoint CF è .../soci/calcola-cf
                let endpoint = 'calcola-cf'; // Default relativo
                if (formAction) {
                    const baseUrlParts = formAction.split('/');
                    baseUrlParts.pop(); // remove 'salva'
                    endpoint = baseUrlParts.join('/') + '/calcola-cf';
                }

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });

                const data = await res.json();

                if (res.ok && data.cf) {
                    const cfInput = document.getElementById('codice_fiscale');
                    if (cfInput) cfInput.value = data.cf;
                } else {
                    alert('Errore: ' + (data.error || 'Sconosciuto'));
                }
            } catch (e) {
                console.error(e);
                alert('Errore di comunicazione durante il calcolo.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    }
});
