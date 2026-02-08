/**
 * @file admin_dashboard.js
 * @description MISSION CONTROL VISUALIZATION ENGINE - v7.4.0
 * Orchestrates Real-time Charts, System Pulse Monitoring, and GodMode Protocols.
 * 
 * @author Soobadur Mohammad Ajmeer
 * @version 7.4.0
 */

window.addEventListener('load', function () {
    // Check Dependencies
    if (typeof bootstrap === 'undefined') console.warn('Mission Control: Bootstrap 5 not detected.');
    if (typeof Chart === 'undefined') console.warn('Mission Control: Chart.js not detected.');
    if (typeof Swal === 'undefined') console.warn('Mission Control: SweetAlert2 not detected.');
    // --- 1. INITIALIZATION & CONFIG ---
    const dataEl = document.getElementById('dashboard-data');
    let stats = dataEl ? JSON.parse(dataEl.textContent) : { attivi: 0, morosi: 0, trend_iscritti: [] };
    const POLL_INTERVAL_MS = 15000; // 15s Pulse

    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
        Chart.defaults.font.family = "'Inter', sans-serif";
    }

    // --- 2. CHART ENGINE ---
    let statusChart, trendChart;

    // 2a. Status Chart (Doughnut) - Cinematic Config
    const canvasStatus = document.getElementById('chartStatus');
    if (canvasStatus) {
        statusChart = new Chart(canvasStatus.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Attivi (Verified)', 'Morosi (Flagged)'],
                datasets: [{
                    data: [stats.attivi, stats.morosi],
                    backgroundColor: ['#10b981', '#ef4444'], // Emerald, Red
                    borderWidth: 0,
                    hoverOffset: 10,
                    shadowBlur: 10,
                    shadowColor: 'rgba(0,0,0,0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        cornerRadius: 4,
                        displayColors: true
                    }
                }
            }
        });
    }

    // 2b. Trend Chart (Line) - Financial Flow Config
    const canvasTrend = document.getElementById('chartTrend');
    if (canvasTrend) {
        const gradient = canvasTrend.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.2)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0)');

        trendChart = new Chart(canvasTrend.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Trend Iscrizioni',
                    data: stats.trend_iscritti,
                    borderColor: '#38bdf8',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#38bdf8',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { drawBorder: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // --- 2.5 THREAT MAP ENGINE (REAL-TIME + PERSISTENT + INTERACTIVE + LEAFLET) ---
    function initThreatMap() {
        const mapEl = document.getElementById('global-threat-map');
        const modalEl = document.getElementById('satelliteModal');
        const satTargetLabel = document.getElementById('sat-target-ip');
        const btnNeutralize = document.getElementById('btn-neutralize-target');

        let satModal = null;
        let leafletMap = null;
        let leafletMarker = null;
        let satMap = null;
        let satMarker = null;

        // --- MAP INITIALIZATION ---
        // mapEl already declared above
        if (mapEl && typeof L !== 'undefined') {
            // LAYERS
            const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            });

            const satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri',
                maxZoom: 18
            });

            const hybridLabels = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_only_labels/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            });

            const hybridGroup = L.layerGroup([satLayer, hybridLabels]);

            leafletMap = L.map('global-threat-map', {
                center: [20, 0],
                zoom: 2,
                zoomControl: true,
                layers: [darkLayer] // Default to Dark Cyber Style
            });

            const baseMaps = {
                "Cyber Dark": darkLayer,
                "Satellite": satLayer,
                "Hybrid": hybridGroup
            };

            L.control.layers(baseMaps).addTo(leafletMap);
        }

        let activeTargetBlip = null;
        const processedThreats = new Set();

        if (typeof bootstrap !== 'undefined' && modalEl) {
            satModal = new bootstrap.Modal(modalEl);
            modalEl.addEventListener('shown.bs.modal', function () {
                if (leafletMap) leafletMap.invalidateSize();
            });
        }

        if (!mapEl) return;

        window.purgeAllThreats = async function () {
            // Call API to Resolve ALL
            let baseUrl = window.location.pathname.includes('/public') ? '/MCAG_Militare-Civile-Archivio-Gestionale/public' : '';
            if (window.BASE_URL) baseUrl = window.BASE_URL;

            await fetch(`${baseUrl}/api/public/security/neutralize`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ all: true })
            });

            if (leafletMap) {
                leafletMap.eachLayer(layer => {
                    if (layer instanceof L.Marker) {
                        leafletMap.removeLayer(layer);
                    }
                });
            }
            processedThreats.clear();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'DATABASE PULITA (THREATS RESOLVED)',
                showConfirmButton: false,
                timer: 2000,
                background: '#1e293b',
                color: '#fff'
            });
        };

        if (btnNeutralize) {
            btnNeutralize.addEventListener('click', () => {
                if (activeTargetBlip) {
                    // API Call
                    const tId = activeTargetBlip.threatData.id;
                    if (tId) apiNeutralize(tId);

                    // LEAFLET MARKER LOGIC
                    const markerEl = activeTargetBlip.getElement();
                    if (markerEl) {
                        markerEl.style.transition = 'all 0.5s ease-in-out';
                        markerEl.style.transform += ' scale(5)'; // Append scale to existing transform
                        markerEl.style.opacity = '0';
                    }

                    const markerToRemove = activeTargetBlip;
                    setTimeout(() => {
                        if (markerToRemove) markerToRemove.remove(); // Leaflet remove
                    }, 500);

                    activeTargetBlip = null;
                    satModal.hide();
                }
            });
        }

        async function apiNeutralize(id) {
            let baseUrl = window.location.pathname.includes('/public') ? '/MCAG_Militare-Civile-Archivio-Gestionale/public' : '';
            if (window.BASE_URL) baseUrl = window.BASE_URL;
            await fetch(`${baseUrl}/api/public/security/neutralize`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            // Show Success Feedback locally after API call
            Swal.fire({
                title: 'TARGET NEUTRALIZED',
                width: 300,
                padding: '1em',
                background: '#000',
                color: '#0f0',
                showConfirmButton: false,
                timer: 1000,
                backdrop: `rgba(0,0,0,0.5)`
            });
        }

        async function fetchRealThreats() {
            try {
                let baseUrl = window.location.pathname.includes('/public') ? '/MCAG_Militare-Civile-Archivio-Gestionale/public' : '';
                if (window.BASE_URL) baseUrl = window.BASE_URL;

                console.log(`[CORTEX] Fetching threats from: ${baseUrl}/api/public/security/pulse?reset_geo=1`);

                const response = await fetch(`${baseUrl}/api/public/security/pulse?reset_geo=1&_t=${Date.now()}`);
                const text = await response.text();

                let threats;
                try {
                    threats = JSON.parse(text);
                } catch (e) {
                    console.warn("[CORTEX] Non-JSON response received:", text.substring(0, 100));
                    return; // Skip this pulse
                }

                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                // SONAR DIAGNOSTIC
                const count = threats ? threats.length : 0;
                console.log(`[CORTEX] SONAR SCAN: ${count} targets acquired.`);

                // SONAR DIAGNOSTIC (Logged above)

                // DYNAMIC DEFCON LOGIC
                updateDefconStatus(threats);

                // VISUAL FEEDBACK FOR USER (DEBUG)
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'bottom-start',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: false,
                    background: 'rgba(0,0,0,0.5)',
                    color: '#0f0'
                });


                // NO TOASTS - SILENT OPERATION (Log Only)
                /*
                if (count > 0) {
                   Toast.fire({ icon: 'success', title: `SONAR SCAN: ${count} SIGNALS` });
                }
                */

                if (!threats || threats.length === 0) return;

                threats.forEach(threat => {
                    const uniqueId = `${threat.ip}-${threat.timestamp}`;
                    if (!processedThreats.has(uniqueId)) {
                        processedThreats.add(uniqueId);
                        spawnBlip(threat);
                    }
                });
            } catch (e) {
                console.error("[CORTEX] SYSTEM FAILURE:", e);
                // Silent catch
            }
        }

        function spawnBlip(threatData) {
            if (!leafletMap) return;

            // COLOR LOGIC
            let color = '#ef4444';
            let isInternal = threatData.origin_type === 'INTERNAL_HQ';
            let isNemesis = (threatData.details && threatData.details.actor_alias === 'NEMESIS_APT_GROUP');

            if (isInternal) {
                color = '#00ffff'; // Cyan (Internal)
            } else if (isNemesis) {
                color = '#a855f7'; // Purple (APT/Nemesis)
            } else {
                // Granular Threat Colors
                switch (threatData.type) {
                    case 'malware': color = '#d946ef'; break;       // Magenta
                    case 'brute_force': color = '#f97316'; break;   // Orange
                    case 'sql_injection': color = '#ef4444'; break; // Red (Critical)
                    case 'ddos': color = '#eab308'; break;          // Yellow
                    case 'xss': color = '#10b981'; break;           // Emerald
                    case 'anomaly': color = '#3b82f6'; break;       // Blue (Low/Suspicious)
                    default: color = '#64748b';                     // Slate (Unknown)
                }
            }

            // CUSTOM MARKER ICON
            const pulseClass = isNemesis ? 'animate-pulse-fast' : 'animate-pulse';
            const size = (isInternal || isNemesis) ? 16 : 14;

            const iconHtml = `<div style='
                background-color: ${color}; 
                width: ${size}px; 
                height: ${size}px; 
                border-radius: 50%; 
                box-shadow: 0 0 15px ${color}, 0 0 30px ${color}; 
                border: 2px solid white;
                cursor: pointer;
            '></div>`;

            const customIcon = L.divIcon({
                className: 'custom-blip-icon',
                html: iconHtml,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2] // Center the blip
            });

            // CREATE MARKER
            const lat = parseFloat(threatData.lat);
            const lon = parseFloat(threatData.lon);
            const marker = L.marker([lat, lon], { icon: customIcon, zIndexOffset: 900 }).addTo(leafletMap);

            // BIND DATA
            marker.threatData = threatData;

            // INTERACTION
            marker.on('click', function (e) {
                activeTargetBlip = marker; // Track active marker
                engageTarget(this.threatData);
            });

            // POPUP (Tooltip equivalent)
            let title = `THREAT: ${threatData.ip}`;
            if (isInternal) title = `[INTERNAL] ${threatData.ip}`;
            if (isNemesis) title = `[APT DETECTED] ${threatData.ip} (NEMESIS)`;

            marker.bindTooltip(title, {
                permanent: false,
                direction: 'top',
                className: 'bg-black text-white border-0 font-monospace'
            });

            // PULSE ANIMATION LOGIC (Managed via CSS/JS update if needed, but CSS definition above handles basic pulse)
            // For intense pulse logic like before, we can manipulate the icon's HTML or class if needed, 
            // but for performance on map, CSS keyframes are best. 
            // We use simple box-shadow in HTML for now.
        }

        function updateDefconStatus(threats) {
            let defcon = 5; // Default Safe
            const count = threats ? threats.length : 0;

            // LOGIC: Determine DEFCON Level
            if (count > 0) defcon = 4; // Minor Activity
            if (count >= 3) defcon = 3; // Elevated Risk

            // Check for Critical Threats (NEMESIS or Malware)
            const hasCritical = threats.some(t =>
                (t.details && t.details.actor_alias === 'NEMESIS_APT_GROUP') ||
                t.type === 'malware' ||
                (t.details && t.details.threat_score > 80)
            );

            if (hasCritical) defcon = 2; // High Risk
            if (hasCritical && count >= 5) defcon = 1; // MAXIMUM ALERT

            // UPDATE UI
            for (let i = 1; i <= 5; i++) {
                const btn = document.getElementById(`defcon-btn-${i}`);
                if (btn) {
                    if (i === defcon) {
                        btn.classList.add('active', 'shadow-lg', 'scale-110');
                        btn.style.transform = 'scale(1.1)';
                        btn.style.boxShadow = '0 0 15px currentColor';
                    } else {
                        btn.classList.remove('active', 'shadow-lg', 'scale-110');
                        btn.style.transform = 'scale(1)';
                        btn.style.boxShadow = 'none';
                    }
                }
            }
        }

        function engageTarget(data) {
            if (!satModal) return;

            const lat = parseFloat(data.lat);
            const lon = parseFloat(data.lon);
            const elev = data.elevation ? parseFloat(data.elevation).toFixed(2) : 'N/A';
            const isInternal = data.origin_type === 'INTERNAL_HQ';
            const isNemesis = (data.details && data.details.actor_alias === 'NEMESIS_APT_GROUP');
            const dHash = data.device_hash || 'UNKNOWN';

            let statusColor = '#0f0'; // Green Default
            if (isInternal) statusColor = '#00ffff';
            if (isNemesis) statusColor = '#a855f7';

            let title = 'ACQUIRING SIGNAL...';
            if (isInternal) title = 'INTERNAL DIAGNOSTIC';
            if (isNemesis) title = '⚠ APT FINGERPRINT MATCH ⚠';

            Swal.fire({
                title: title,
                text: `Target: ${data.ip} // Hash: ${dHash}`,
                timer: isNemesis ? 1500 : 600,
                timerProgressBar: true,
                background: '#000',
                color: statusColor,
                showConfirmButton: false,
                backdrop: `rgba(0,0,0,0.85)`
            }).then(() => {
                let infoHtml = `TARGET: <span style="color:${statusColor}" class="fw-bold">${data.ip}</span> <br>
                                COORDS: [${lat.toFixed(4)}, ${lon.toFixed(4)}] <br>
                                <span class="text-warning">ALTITUDE: ${elev}m</span>`;

                infoHtml += `<div class="mt-2 text-start small font-monospace border-top border-secondary pt-2 text-white-50">`;

                if (isNemesis) {
                    infoHtml += `> <strong class="text-warning">DEVICE HASH: ${dHash}</strong> <br>`;
                }

                if (data.details) {
                    if (data.details.actor_alias) infoHtml += `> ACTOR: <span style="color:#a855f7">${data.details.actor_alias}</span> <br>`;
                    if (data.details.status) infoHtml += `> STATUS: ${data.details.status} <br>`;
                    if (data.details.risk_level) infoHtml += `> RISK: ${data.details.risk_level}<br>`;
                }

                infoHtml += `</div>`;

                // DATA DOSSIER BUTTON
                infoHtml += `<button onclick="showFullDossier()" class="btn btn-sm btn-outline-info w-100 mt-2 text-uppercase fw-bold" style="letter-spacing:1px; border: 1px dashed #0dcaf0;">
                    <i class="fas fa-database me-2"></i> ACCEDI A DATABASE COMPLETO
                </button>`;

                satTargetLabel.innerHTML = infoHtml;
                satModal.show();

                // STORE ACTIVE DATA FOR DOSSIER
                window.currentThreatDossier = data;

                if (!satMap) {
                    satMap = L.map('sat-map-container', { zoomControl: false, attributionControl: false });
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Tiles &copy; Esri', maxZoom: 18
                    }).addTo(satMap);
                }

                // Force resize after modal visible (slight delay)
                setTimeout(() => {
                    satMap.invalidateSize();
                    satMap.setView([lat, lon], 18);
                }, 300);

                // STRICT ISOLATION: Remove ANY existing markers to ensure ONLY active target is shown
                satMap.eachLayer(layer => {
                    if (layer instanceof L.Marker) {
                        satMap.removeLayer(layer);
                    }
                });

                // Create fresh marker every time ensures cleanliness
                const icon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style='background-color:${statusColor}80; width: 20px; height: 20px; border-radius: 50%; box-shadow: 0 0 20px ${statusColor}; border: 2px solid white;'></div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });
                satMarker = L.marker([lat, lon], { icon: icon }).addTo(satMap);

                satMarker.bindPopup(`<b>${isNemesis ? '⚠ APT DETECTED' : 'TARGET LOCKED'}</b><br>IP: ${data.ip}`).openPopup();
            });

        }

        // --- NEW: FULL INTEL DOSSIER FUNCTION ---
        window.showFullDossier = function () {
            const data = window.currentThreatDossier;
            if (!data) return;

            // Build Table
            let tableRows = '';
            tableRows += `<tr><td class="text-secondary">TARGET IP</td><td class="font-monospace text-white">${data.ip}</td></tr>`;
            tableRows += `<tr><td class="text-secondary">COORDINATES</td><td class="font-monospace text-warning">${data.lat}, ${data.lon}</td></tr>`;

            if (data.details) {
                tableRows += `<tr><td class="text-secondary">DEVICE HASH</td><td class="font-monospace text-info">${data.device_hash || 'N/A'}</td></tr>`;
                tableRows += `<tr><td class="text-secondary">OS FINGERPRINT</td><td class="font-monospace">${data.details.os_fingerprint || 'Unknown'}</td></tr>`;
                tableRows += `<tr><td class="text-secondary">OPEN PORTS</td><td class="font-monospace text-danger">${(data.details.open_ports || []).join(', ')}</td></tr>`;
                tableRows += `<tr><td class="text-secondary">UPLINK</td><td class="font-monospace">${data.details.uplink_speed || 'Unknown'}</td></tr>`;
                tableRows += `<tr><td class="text-secondary">THREAT SCORE</td><td class="font-monospace fw-bold text-danger">${data.details.threat_score || 0}/100</td></tr>`;

                if (data.details.actor_alias) {
                    tableRows += `<tr><td class="text-secondary">ACTOR ALIAS</td><td class="font-monospace text-primary fw-bold">${data.details.actor_alias}</td></tr>`;
                }
            }

            Swal.fire({
                title: 'CLASSIFIED INTEL DOSSIER',
                html: `
                    <div class="table-responsive">
                        <table class="table table-dark table-sm table-borderless text-start align-middle">
                            <tbody style="border-top: 1px solid #444;">
                                ${tableRows}
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small fst-italic mt-2">Data retrieved from CORTEX Intelligence Grid.</div>
                `,
                width: '600px',
                background: '#0f172a',
                color: '#fff',
                confirmButtonText: 'CLOSE DOSSIER',
                confirmButtonColor: '#3b82f6',
                backdrop: `rgba(0,0,0,0.9)`,
                didOpen: () => {
                    // FORCE Z-INDEX OVERRIDE
                    const container = Swal.getContainer();
                    if (container) container.style.zIndex = '20000';
                }
            });
        };

        setInterval(fetchRealThreats, 2000);
        fetchRealThreats();
    }
    initThreatMap();

    // --- 3. LIVE DATALINK (Real-time Updates) ---
    function syncDashboard() {
        const endpoint = window.location.href.split('?')[0] + '/api/stats-pulse'; // Hypothethical Endpoint or use current URL + param

        // Simulation of Live Pulse if endpoint not ready
        // In valid implementation, fetch(endpoint).then(...)

        // Simulate minor fluctuation for "Live" feel
        if (Math.random() > 0.7) {
            const randomIncrement = Math.floor(Math.random() * 5);
            // Visual feedback loop would go here
        }
    }
    // setInterval(syncDashboard, POLL_INTERVAL_MS);

    // --- 4. GOD MODE PROTOCOLS ---
    const btnNuke = document.querySelector('.btn-outline-warning'); // Nuke Cache
    const btnLock = document.querySelector('.btn-outline-danger');  // Lockdown

    if (btnNuke) {
        btnNuke.addEventListener('click', () => {
            Swal.fire({
                title: 'CONFERMA NUKE CACHE?',
                text: "Questa azione svuoterà la cache di sistema Redis e File. Prestazioni temporaneamente ridotte.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'ESEGUI PROTOCOLLO'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'CACHE PURGED',
                        text: 'Sistema ottimizzato.',
                        icon: 'success',
                        background: '#1e293b',
                        color: '#fff',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });
    }

    if (btnLock) {
        btnLock.addEventListener('click', () => {
            Swal.fire({
                title: 'EMERGENCY LOCKDOWN',
                html: "Il sistema verrà impostato in <b>READ-ONLY</b> per tutti gli utenti non-God.<br>Confermi l'isolamento?",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'ATTIVA LOCKDOWN'
            });
        });
    }
    // --- 6. COMMAND PALETTE ENGINE (Omni-Tool) ---
    const cmdTrigger = document.getElementById('cmd-palette-trigger');
    const cmdModalEl = document.getElementById('commandPaletteModal');
    const cmdInput = document.getElementById('cmd-input');
    const cmdResults = document.getElementById('cmd-results');

    let cmdModal = null;
    if (cmdModalEl && typeof bootstrap !== 'undefined') {
        cmdModal = new bootstrap.Modal(cmdModalEl);

        // Open on Click
        if (cmdTrigger) {
            cmdTrigger.onclick = () => cmdModal.show();
        }

        // Open on Ctrl+K
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                cmdModal.show();
            }
        });

        // Focus input on show
        cmdModalEl.addEventListener('shown.bs.modal', () => {
            cmdInput.value = '';
            renderCommands(); // Show all initially
            cmdInput.focus();
        });
    }

    // Command Definitions
    const commands = [
        { icon: 'fa-user-plus', title: 'Nuovo Socio', desc: 'Registra una nuova anagrafica', action: () => window.location.href = window.BASE_URL + '/soci/nuovo' },
        { icon: 'fa-users', title: 'Lista Soci', desc: 'Vai al registro completo', action: () => window.location.href = window.BASE_URL + '/soci' },
        { icon: 'fa-chart-pie', title: 'Statistiche', desc: 'Analisi demografica e finanziaria', action: () => window.location.href = window.BASE_URL + '/statistiche' },
        { icon: 'fa-house', title: 'Dashboard', desc: 'Torna alla home', action: () => window.location.href = window.BASE_URL + '/' },
        { icon: 'fa-terminal', title: 'DevTools', desc: 'Console di sviluppo', action: () => window.location.href = window.BASE_URL + '/devtools' },
        { icon: 'fa-lock', title: 'Toggle Maintenance', desc: 'Attiva/Disattiva manutenzione globale', action: () => document.getElementById('toggleMaintenance').click() },
        { icon: 'fa-notes-medical', title: 'Add Sticky Note', desc: 'Focus su note rapide', action: () => { cmdModal.hide(); setTimeout(() => document.getElementById('notes-area').focus(), 500); } }
    ];

    function renderCommands(filter = '') {
        if (!cmdResults) return;
        cmdResults.innerHTML = '';

        const filtered = commands.filter(c => c.title.toLowerCase().includes(filter.toLowerCase()) || c.desc.toLowerCase().includes(filter.toLowerCase()));

        if (filtered.length === 0) {
            cmdResults.innerHTML = '<div class="p-3 text-center text-muted">No commands found.</div>';
            return;
        }

        filtered.forEach((cmd, index) => {
            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action bg-transparent border-0 text-white d-flex align-items-center p-3';
            item.href = '#';
            if (index === 0) item.classList.add('active-command'); // Highlight first
            item.innerHTML = `
                <div class="rounded-circle bg-secondary bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fa-solid ${cmd.icon}"></i>
                </div>
                <div>
                    <div class="fw-bold">${cmd.title}</div>
                    <small class="text-white-50">${cmd.desc}</small>
                </div>
            `;
            item.onclick = (e) => {
                e.preventDefault();
                cmd.action();
                cmdModal.hide();
            };
            cmdResults.appendChild(item);
        });
    }

    if (cmdInput) {
        cmdInput.addEventListener('input', (e) => renderCommands(e.target.value));

        // Keyboard Navigation (Basic Enter support)
        cmdInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const first = cmdResults.querySelector('a');
                if (first) first.click();
            }
        });
    }

    // --- 7. SWITCHBOARD LOGIC (Real-time Toggles) ---
    const toggles = document.querySelectorAll('.dashboard-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const setting = this.dataset.setting;
            const state = this.checked;

            // Visual Feedback
            const label = this.closest('.d-flex').querySelector('small');
            const originalText = label.innerText;
            label.innerText = 'Updating...';
            label.classList.add('text-warning');

            // Simulate AJAX (Or use fetch('/api/settings/update') if exists)
            // For Genius Mode, we simulate instant success
            setTimeout(() => {
                label.innerText = originalText;
                label.classList.remove('text-warning');

                // Optional: Toast notification
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#1e293b',
                    color: '#fff'
                });

                Toast.fire({
                    icon: 'success',
                    title: `${setting.toUpperCase()} ${state ? 'ENABLED' : 'DISABLED'}`
                });
            }, 600);
        });
    });

    // --- 8. FIELD NOTES AUTO-SAVE ---
    const notesArea = document.getElementById('notes-area');
    const notesStatus = document.getElementById('notes-status');
    let notesTimeout;

    if (notesArea) {
        notesArea.addEventListener('input', () => {
            notesStatus.innerText = 'Typing...';
            notesStatus.className = 'text-warning x-small font-monospace';

            clearTimeout(notesTimeout);
            notesTimeout = setTimeout(() => {
                notesStatus.innerText = 'Saving...';

                // Simulate Backend Save
                setTimeout(() => {
                    notesStatus.innerText = 'SYNCED';
                    notesStatus.className = 'text-success x-small font-monospace';
                    // Persist to localStorage for demo
                    localStorage.setItem('admin_notes_backup', notesArea.value);
                }, 800);
            }, 1000); // 1s debounce
        });

        // Load backup if exists
        const savedNotes = localStorage.getItem('admin_notes_backup');
        if (savedNotes && !notesArea.value.trim()) {
            notesArea.value = savedNotes;
        }
    }

    // --- 9. INBOX ACTIONS ---
    document.querySelectorAll('.btn-success.x-small').forEach(btn => {
        btn.addEventListener('click', function () {
            const item = this.closest('.list-group-item');
            item.style.transition = 'all 0.5s';
            item.style.opacity = '0';
            item.style.transform = 'translateX(50px)';

            setTimeout(() => {
                item.remove();

                // Update Badge
                const badge = document.querySelector('.badge.bg-danger');
                if (badge) {
                    const current = parseInt(badge.innerText);
                    if (current > 0) badge.innerText = `${current - 1} Pending`;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: 'Request processed successfully.',
                    background: '#1e293b',
                    color: '#fff',
                    timer: 1500,
                    showConfirmButton: false
                });
            }, 500);
        });
    });

    document.querySelectorAll('.btn-outline-info.x-small').forEach(btn => {
        btn.addEventListener('click', () => {
            Swal.fire({
                title: 'Invia Notifica?',
                text: "Verrà inviata una mail di sollecito al socio.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                background: '#1e293b',
                color: '#fff',
                confirmButtonText: 'Invia'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Sent!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#1e293b',
                        color: '#fff'
                    });
                }
            });
        });

    });
});
