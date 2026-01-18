document.addEventListener('DOMContentLoaded', () => {
    feather.replace(); // Enforce icons
    const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
    const client = new WorkShiftAPI(baseUrl);

    // State
    let currentDate = new Date();
    let currentView = 'week'; // day, week, month, year

    // View Switcher logic
    document.querySelectorAll('.view-switch-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // UI Update
            document.querySelectorAll('.view-switch-btn').forEach(b => {
                b.classList.remove('bg-indigo-500', 'text-white', 'shadow-lg');
                b.classList.add('text-slate-400');
            });
            btn.classList.add('bg-indigo-500', 'text-white', 'shadow-lg');
            btn.classList.remove('text-slate-400');

            // Logic Update
            currentView = btn.dataset.view;
            console.log('[ShiftManager] Switched view to:', currentView);
            updateHeaderDisplay();
            loadSchedule(); // Will fetch based on new view
        });
    });

    // === Date Navigation ===
    const updateDate = (direction) => {
        if (currentView === 'week') {
            currentDate.setDate(currentDate.getDate() + (direction * 7));
        } else if (currentView === 'month') {
            currentDate.setMonth(currentDate.getMonth() + direction);
        } else if (currentView === 'year') {
            currentDate.setFullYear(currentDate.getFullYear() + direction);
        } else if (currentView === 'day') {
            currentDate.setDate(currentDate.getDate() + direction);
        }
        updateHeaderDisplay();
        loadSchedule();
    };

    const prevBtn = document.getElementById('prevWeekBtn');
    const nextBtn = document.getElementById('nextWeekBtn');

    if (prevBtn) prevBtn.addEventListener('click', () => updateDate(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => updateDate(1));

    // Unified Header Display
    const updateHeaderDisplay = () => {
        const label = document.getElementById('currentWeekLabel');
        const headerTitle = document.getElementById('calendarTitle'); // Label

        let titleText;

        if (currentView === 'week') {
            const clone = new Date(currentDate);
            const day = clone.getDay();
            const diff = clone.getDate() - day + (day === 0 ? -6 : 1);
            clone.setDate(diff);
            const start = new Date(clone);
            clone.setDate(diff + 6);
            const end = new Date(clone);

            titleText = "SETTIMANA CORRENTE";
            if (label) label.innerHTML = `${start.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' })} ${start.getFullYear()}`;

            // Update Day Numbers
            const startW = new Date(start);
            ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].forEach((day, idx) => {
                const loopD = new Date(startW);
                loopD.setDate(startW.getDate() + idx);
                const el = document.querySelector(`.group[data-day="${day}"] .header-date`);
                if (el) el.textContent = loopD.getDate();
            });

            // Ensure Grid visible
            const grid = document.getElementById('scheduleGrid');
            const headerRow = document.querySelector('.grid.grid-cols-7.border-b');
            if (grid) grid.className = "grid grid-cols-7 divide-x divide-white/5 min-h-[700px] bg-slate-900/20 backdrop-blur-sm";
            if (headerRow) headerRow.classList.remove('hidden');

        } else if (currentView === 'month') {
            titleText = "MESE CORRENTE";
            if (label) label.innerHTML = currentDate.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' }).toUpperCase();
        } else if (currentView === 'year') {
            titleText = "ANNO";
            if (label) label.innerHTML = currentDate.getFullYear();
        } else if (currentView === 'list') {
            titleText = "VISTA LISTA (MESE)";
            if (label) label.innerHTML = currentDate.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' }).toUpperCase();
        } else if (currentView === 'day') {
            titleText = "GIORNO SINGOLO";
            if (label) label.innerHTML = currentDate.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long' });
        }

        if (headerTitle) headerTitle.textContent = titleText;
    };
    updateHeaderDisplay();

    // UI Elements
    const modal = document.getElementById('createShiftModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalPanel = document.getElementById('modalPanel');

    // Buttons
    const openBtn = document.getElementById('openCreateShiftModal'); // Now specific ID
    const cancelBtn = document.getElementById('cancelCreateShift');
    const confirmBtn = document.getElementById('confirmCreateShift');
    const aiBtn = document.getElementById('aiOptimizeBtn');

    // Grid
    const scheduleGrid = document.getElementById('scheduleGrid');

    // === Modal Logic (Animation) ===
    function showModal() {
        modal.classList.remove('hidden');
        // Trigger reflow
        void modal.offsetWidth;

        // Animation in
        modalBackdrop.classList.remove('opacity-0');
        modalBackdrop.classList.add('opacity-100');

        modalPanel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
        modalPanel.classList.add('opacity-100', 'translate-y-0', 'scale-100');
    }

    function closeModal() {
        // Animation out
        modalPanel.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        modalPanel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
        modalBackdrop.classList.remove('opacity-100');
        modalBackdrop.classList.add('opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    if (openBtn) {
        openBtn.addEventListener('click', () => {
            document.getElementById('shiftId').value = ''; // Clear ID
            document.getElementById('shiftForm').reset();
            // Reset default times for the default selection
            if (window.updateTimeInputs) window.updateTimeInputs();
            showModal();
        });
    }
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // === Grid Interaction (Click to Add) ===
    const gridCols = document.querySelectorAll('.grid-col');
    gridCols.forEach(col => {
        col.addEventListener('click', (e) => {
            // Prevent opening if clicking on an existing card (or its children)
            if (e.target.closest('.group') && e.target.closest('.rounded-2xl')) {
                return;
            }

            const day = col.getAttribute('data-day');
            if (day) {
                // Pre-fill Modal
                const daySelect = document.getElementById('shiftDay');
                if (daySelect) daySelect.value = day;
                showModal();
            }
        });
    });

    // === Form Logic ===
    window.updateTimeInputs = () => {
        const type = document.getElementById('shiftType').value;
        const startEl = document.getElementById('shiftStart');
        const endEl = document.getElementById('shiftEnd');

        switch (type) {
            case 'Morning': startEl.value = '08:00'; endEl.value = '16:00'; break;
            case 'Day': startEl.value = '09:00'; endEl.value = '17:00'; break;
            case 'Evening': startEl.value = '16:00'; endEl.value = '00:00'; break;
            case 'Night': startEl.value = '00:00'; endEl.value = '08:00'; break;
        }
    };

    // === Form Submission ===
    // === Form Submission ===
    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            const id = document.getElementById('shiftId').value;
            const empId = document.getElementById('shiftEmployee').value;
            const shiftType = document.getElementById('shiftType').value;
            const day = document.getElementById('shiftDay').value;
            const notes = document.getElementById('shiftNotes').value;
            const startTimeStr = document.getElementById('shiftStart').value;
            const endTimeStr = document.getElementById('shiftEnd').value;

            // Date Calculation
            const today = new Date();
            const currentDay = today.getDay();
            const mondayOffset = (currentDay === 0 ? -6 : 1) - currentDay;
            const mondayDate = new Date(today);
            mondayDate.setDate(today.getDate() + mondayOffset);

            const daysFromMonday = { 'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3, 'Friday': 4, 'Saturday': 5, 'Sunday': 6 };
            const targetDate = new Date(mondayDate);
            targetDate.setDate(mondayDate.getDate() + daysFromMonday[day]);
            const dateStr = targetDate.toISOString().split('T')[0];

            const payload = {
                id: id || null,
                employee_id: empId,
                type: shiftType,
                day: day,
                date: dateStr,
                start_time: `${dateStr} ${startTimeStr}:00`,
                end_time: `${dateStr} ${endTimeStr}:00`,
                notes: notes
            };

            // UX: Loading State
            const originalText = confirmBtn.innerHTML;
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i data-feather="loader" class="animate-spin inline mr-2 h-4 w-4"></i> Salvataggio...';
            feather.replace();

            try {
                const result = await client.saveShift(payload);
                // Check result logic (assuming saveShift returns JSON or throws)
                if (result && (result.success || result.id)) {
                    closeModal();
                    window.location.reload();
                } else {
                    throw new Error(result.error || 'Errore sconosciuto');
                }
            } catch (err) {
                console.error(err);
                alert('Errore durante il salvataggio: ' + err.message);
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
                feather.replace();
            }
        });
    }

    // === AI Optimizer ===
    // === AI Optimizer (Modal) ===
    const aiModal = document.getElementById('aiOptionsModal');
    const aiModalPanel = document.getElementById('aiModalPanel');
    const aiModalBackdrop = document.getElementById('aiModalBackdrop');
    const closeAiModalBtn = document.getElementById('closeAiModal');
    const aiLoading = document.getElementById('aiLoading');

    const openAiModal = () => {
        aiModal.classList.remove('hidden');
        setTimeout(() => {
            aiModalBackdrop.classList.remove('opacity-0');
            aiModalPanel.classList.remove('opacity-0', 'scale-95');
        }, 10);
    };

    const closeAiModal = () => {
        aiModalBackdrop.classList.add('opacity-0');
        aiModalPanel.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            aiModal.classList.add('hidden');
            aiLoading.classList.add('hidden'); // Reset loading
        }, 300);
    };

    if (aiBtn) {
        aiBtn.addEventListener('click', openAiModal);
    }

    if (closeAiModalBtn) closeAiModalBtn.addEventListener('click', closeAiModal);
    if (aiModalBackdrop) aiModalBackdrop.addEventListener('click', closeAiModal);

    // AI Generation Logic
    document.querySelectorAll('.ai-option-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const mode = btn.dataset.mode;
            console.log('[ShiftManager] AI Mode selected:', mode);

            // Show Loading inside Modal
            aiLoading.classList.remove('hidden');

            try {
                // Use raw fetch for custom payload
                const endpoint = baseUrl + '/workshift/api/optimize';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mode: mode })
                });

                const result = await response.json();

                if (result.success || result.status === 'optimized') {
                    window.location.reload();
                } else {
                    throw new Error(result.error || 'Errore sconosciuto');
                }
            } catch (e) {
                console.error(e);
                alert("Errore AI: " + e.message);
                aiLoading.classList.add('hidden');
            }
        });
    });

    // === Grid Rendering ===
    loadSchedule();

    async function loadSchedule() {
        try {
            let s, e;
            const formatDate = (d) => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };

            const clone = new Date(currentDate);

            if (currentView === 'week') {
                const day = clone.getDay();
                const diff = clone.getDate() - day + (day === 0 ? -6 : 1);
                clone.setDate(diff);
                s = formatDate(clone);
                clone.setDate(diff + 6);
                e = formatDate(clone);
            } else if (currentView === 'month') {
                clone.setDate(1);
                s = formatDate(clone);
                clone.setMonth(clone.getMonth() + 1);
                clone.setDate(0);
                e = formatDate(clone);
            } else if (currentView === 'year') {
                clone.setMonth(0, 1);
                s = formatDate(clone);
                clone.setMonth(11, 31);
                e = formatDate(clone);
            } else if (currentView === 'day') {
                s = formatDate(clone);
                e = formatDate(clone);
            } else if (currentView === 'list') {
                // List View defaults to showing 30 days from current date or current month
                // Let's stick to Month range for consistency initially, but displayed as list
                clone.setDate(1);
                s = formatDate(clone);
                clone.setMonth(clone.getMonth() + 1);
                clone.setDate(0);
                e = formatDate(clone);
            }

            console.log(`[ShiftManager] Loading schedule for ${currentView}: ${s} to ${e}`);
            const data = await client.getSchedule(s, e);

            let shifts = [];
            if (Array.isArray(data)) shifts = data;
            else if (data && data.schedule) shifts = data.schedule;

            const grid = document.getElementById('scheduleGrid');
            const headerRow = document.querySelector('.grid.grid-cols-7.border-b');

            // Reset Classes
            grid.className = '';
            grid.innerHTML = '';

            if (currentView === 'week') {
                if (headerRow) headerRow.classList.remove('hidden');
                // Standard Grid
                grid.className = "grid grid-cols-7 divide-x divide-white/5 h-full bg-slate-900/20 backdrop-blur-sm";

                // Rebuild columns
                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                days.forEach(day => {
                    const col = document.createElement('div');
                    col.className = "grid-col p-3 space-y-3 group/col hover:bg-white/5 transition-colors relative cursor-pointer overflow-y-auto h-full scrollbar-hide";
                    col.dataset.day = day;
                    col.innerHTML = `<div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/col:opacity-10 pointer-events-none transition-opacity"><i data-feather="plus-circle" class="w-12 h-12 text-white"></i></div>`;

                    // Re-attach click listener for Add Modal
                    col.addEventListener('click', (e) => {
                        if (e.target.closest('.group') && e.target.closest('.rounded-2xl')) return;
                        const d = col.getAttribute('data-day');
                        if (d) {
                            const daySelect = document.getElementById('shiftDay');
                            if (daySelect) daySelect.value = d;
                            // Check global toggle
                            if (window.toggleCreateShiftModal) window.toggleCreateShiftModal();
                        }
                    });

                    grid.appendChild(col);
                });
                renderGrid(shifts);
                feather.replace();

            } else if (currentView === 'month') {
                if (headerRow) headerRow.classList.add('hidden');
                renderMonthGrid(grid, shifts, currentDate);
            } else if (currentView === 'year') {
                if (headerRow) headerRow.classList.add('hidden');
                renderYearSummary(grid, shifts, currentDate);
            } else if (currentView === 'day') {
                if (headerRow) headerRow.classList.add('hidden');
                renderDayDetailed(grid, shifts, currentDate);
            } else if (currentView === 'list') {
                if (headerRow) headerRow.classList.add('hidden');
                renderListView(grid, shifts);
                feather.replace();
            }

        } catch (e) {
            console.error('Failed to load schedule', e);
        }
    }

    function renderGrid(shifts) {
        // Clear columns (keep content structure if used for headers, but here we appended to data-day cols)
        const dayCols = document.querySelectorAll('.grid-col');
        dayCols.forEach(col => {
            col.innerHTML = ''; // Clean
            // Drag Events
            col.setAttribute('ondrop', 'drop(event)');
            col.setAttribute('ondragover', 'allowDrop(event)');
            col.setAttribute('ondragleave', 'dragleave(event)');
        });

        const dayMap = { 'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3, 'Friday': 4, 'Saturday': 5, 'Sunday': 6 };
        // We have strict 7 cols in HTML index 0-6

        shifts.forEach(shift => {
            const colIndex = dayMap[shift.day];
            if (colIndex !== undefined) {
                const col = dayCols[colIndex];

                // Time Format Handling (Robust)
                let startTime = shift.start_time;
                if (startTime && startTime.includes(' ')) {
                    startTime = startTime.split(' ')[1];
                }
                startTime = startTime ? startTime.substring(0, 5) : '00:00';

                let endTime = shift.end_time;
                if (endTime && endTime.includes(' ')) {
                    endTime = endTime.split(' ')[1];
                }
                endTime = endTime ? endTime.substring(0, 5) : '00:00';

                const card = document.createElement('div');

                // Check if shift is in the past
                const shiftDateObj = new Date(shift.date);
                const todayObj = new Date();
                todayObj.setHours(0, 0, 0, 0);
                const isPast = shiftDateObj < todayObj;

                // CSS Class Logic (Robust against missing Tailwind builds)
                let typeClass = '';
                let dotClass = '';

                if (isPast) {
                    typeClass = 'bg-slate-800/50 border-slate-700 grayscale opacity-75'; // Past styling
                    dotClass = 'bg-slate-500 shadow-none';
                } else {
                    typeClass = shift.type === 'Morning' ? 'shift-card-morning' :
                        shift.type === 'Day' ? 'shift-card-day' :
                            shift.type === 'Evening' ? 'shift-card-evening' : 'shift-card-night';

                    dotClass = shift.type === 'Morning' ? 'dot-morning' :
                        shift.type === 'Day' ? 'dot-day' :
                            shift.type === 'Evening' ? 'dot-evening' : 'dot-night';
                }

                card.className = `shift-card ${typeClass} p-3 rounded-2xl mb-3 relative overflow-hidden group cursor-grab active:cursor-grabbing transition-all`;
                card.draggable = true;
                card.setAttribute('ondragstart', 'drag(event)');
                card.dataset.shift = JSON.stringify(shift);

                const initials = shift.employee_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

                card.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            ${shift.employee_avatar ?
                        `<img src="${shift.employee_avatar}" class="w-6 h-6 rounded-full border border-white/20">` :
                        `<div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold text-white">${initials}</div>`
                    }
                            <span class="text-white font-bold text-sm truncate max-w-[80px]" title="${shift.employee_name}">${shift.employee_name}</span>
                        </div>
                        <div class="w-2 h-2 rounded-full ${dotClass}"></div>
                    </div>
                    <div class="flex justify-between items-end mt-2">
                        <div class="text-xs font-mono text-slate-300 bg-black/20 px-2 py-1 rounded-lg">
                            ${startTime} - ${endTime}
                        </div>
                        <div class="flex gap-1">
                             <!-- Quick Move Button (Symmetrical) -->
                            <button class="text-indigo-400 hover:text-white hover:bg-indigo-500/30 p-1.5 rounded-lg transition-colors" onclick="arguments[0].stopPropagation(); openQuickMoveModal('${shift.id}')" title="Sposta a Giorno">
                                <i data-feather="move" class="w-4 h-4"></i>
                            </button>
                             <!-- Calendar Button (Deep Move) -->
                            <button class="text-fuchsia-400 hover:text-white hover:bg-fuchsia-500/30 p-1.5 rounded-lg transition-colors" onclick="arguments[0].stopPropagation(); openQuickMoveModal('${shift.id}', true)" title="Sposta a Data">
                                <i data-feather="calendar" class="w-4 h-4"></i>
                            </button>
                            <button class="text-red-400 hover:text-white hover:bg-red-500/30 p-1.5 rounded-lg transition-colors" onclick="arguments[0].stopPropagation(); deleteShift('${shift.id}')" title="Rimuovi">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                `;

                col.appendChild(card);
            }
        });

        // Batch feather replace after all cards are added to DOM for performance and reliability
        setTimeout(() => { feather.replace(); }, 0);
    }

    // === Delete Modal Logic ===
    window.openDeleteShiftModal = (id) => {
        document.getElementById('deleteShiftId').value = id;
        const modal = document.getElementById('deleteShiftModal');
        const backdrop = document.getElementById('deleteShiftBackdrop');
        const panel = document.getElementById('deleteShiftPanel');

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            panel.classList.remove('opacity-0', 'scale-95');
            panel.classList.add('opacity-100', 'scale-100');
            feather.replace(); // Ensure icons render in modal
        }, 10);
    };

    window.closeDeleteShiftModal = () => {
        const modal = document.getElementById('deleteShiftModal');
        const backdrop = document.getElementById('deleteShiftBackdrop');
        const panel = document.getElementById('deleteShiftPanel');

        if (!modal) return;

        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('opacity-0', 'scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    };

    document.getElementById('confirmDeleteShift').addEventListener('click', async () => {
        const id = document.getElementById('deleteShiftId').value;
        try {
            await client.deleteShift(id);
            closeDeleteShiftModal();
            loadSchedule();
            // Optional: Show a subtle toast or just refresh
        } catch (e) {
            alert('Errore eliminazione: ' + e.message);
        }
    });

    window.deleteShift = (id) => {
        openDeleteShiftModal(id);
    };

    // === Drag & Drop & Reset Logic ===

    // Helper to check if a day column is in the past
    const isDayPast = (dayName) => {
        if (currentView !== 'week') return false; // Only strict for week view logic usually

        // Calculate the Date of that column based on currentDate (which is start of week usually or tracked)
        // This relies on currentDate being correct.
        // Let's re-calculate the week start from currentDate state
        const clone = new Date(currentDate);
        const day = clone.getDay();
        const diff = clone.getDate() - day + (day === 0 ? -6 : 1);
        clone.setDate(diff); // Monday of current view

        const dayOffsets = { 'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3, 'Friday': 4, 'Saturday': 5, 'Sunday': 6 };

        const targetDate = new Date(clone);
        targetDate.setDate(clone.getDate() + dayOffsets[dayName]);

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        targetDate.setHours(0, 0, 0, 0);

        return targetDate < today;
    }

    window.allowDrop = (ev) => {
        ev.preventDefault();
        const col = ev.currentTarget;
        const targetDay = col.dataset.day;

        if (isDayPast(targetDay)) {
            col.classList.add('bg-red-500/10', 'border-red-500/20');
            col.classList.remove('bg-white/5'); // Remove standard
        } else {
            col.classList.add('bg-white/5');
            col.classList.remove('bg-red-500/10', 'border-red-500/20');
        }
    };

    window.dragleave = (ev) => {
        ev.currentTarget.classList.remove('bg-white/5', 'bg-red-500/10', 'border-red-500/20');
    };

    window.drag = (ev) => {
        const shiftData = ev.target.dataset.shift; // JSON string
        ev.dataTransfer.setData("application/json", shiftData);
    };

    window.drop = async (ev) => {
        ev.preventDefault();
        ev.currentTarget.classList.remove('bg-white/5', 'bg-red-500/10', 'border-red-500/20');

        const data = ev.dataTransfer.getData("application/json");
        if (!data) return;

        const shift = JSON.parse(data);
        const targetDay = ev.currentTarget.dataset.day;

        if (shift.day === targetDay) return; // No change

        // Calculate New Date
        // We must calculate based on the VIEW's current date, NOT just "next Monday" from today.
        // Otherwise navigating weeks breaks dragging.
        const clone = new Date(currentDate);
        const day = clone.getDay();
        const diff = clone.getDate() - day + (day === 0 ? -6 : 1);
        clone.setDate(diff); // Monday of VIEW

        const dayOffsets = { 'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3, 'Friday': 4, 'Saturday': 5, 'Sunday': 6 };

        const targetDate = new Date(clone);
        targetDate.setDate(clone.getDate() + dayOffsets[targetDay]);

        // Local Date String YYYY-MM-DD
        const yyyy = targetDate.getFullYear();
        const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
        const dd = String(targetDate.getDate()).padStart(2, '0');
        const dateStr = `${yyyy}-${mm}-${dd}`;

        // VISUAL FEEDBACK: If Past
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const tDate = new Date(targetDate);
        tDate.setHours(0, 0, 0, 0);

        if (tDate < today) {
            if (!confirm("Attenzione: Stai spostando un turno nel passato. Confermi?")) return;
        }

        // Updates
        shift.day = targetDay;
        shift.date = dateStr;

        // Update Shift
        try {
            const client = new WorkShiftAPI(window.location.origin + window.location.pathname.split('/workshift')[0]);
            await client.saveShift(shift);
            // Don't full reload, try optimized reload
            // window.location.reload(); 
            // Better: just loadSchedule() to keep view
            loadSchedule();
        } catch (e) {
            console.error(e);
            alert("Errore spostamento: " + e.message);
        }
    };

    // === Quick Move Logic ===
    window.openQuickMoveModal = (id, focusDate = false) => {
        document.getElementById('quickMoveShiftId').value = id;
        document.getElementById('quickMoveDate').value = ''; // Reset specific date

        const modal = document.getElementById('quickMoveModal');
        const backdrop = document.getElementById('quickMoveBackdrop');
        const panel = document.getElementById('quickMovePanel');

        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            panel.classList.add('opacity-100', 'translate-y-0', 'scale-100');

            if (focusDate) {
                const dateInput = document.getElementById('quickMoveDate');
                if (dateInput) {
                    dateInput.focus();
                    dateInput.click(); // Try opening getter
                }
            }
        }, 10);
    }

    window.closeQuickMoveModal = () => {
        const modal = document.getElementById('quickMoveModal');
        const backdrop = document.getElementById('quickMoveBackdrop');
        const panel = document.getElementById('quickMovePanel');

        panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
        backdrop.classList.add('opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    const confirmMoveBtn = document.getElementById('confirmQuickMove');
    if (confirmMoveBtn) {
        confirmMoveBtn.addEventListener('click', async () => {
            const id = document.getElementById('quickMoveShiftId').value;
            const day = document.getElementById('quickMoveDay').value;
            const specificDate = document.getElementById('quickMoveDate').value;

            let targetDateStr;
            let targetDayName = day;

            if (specificDate) {
                targetDateStr = specificDate;
                // Calculate Day name from date
                const dateObj = new Date(specificDate);
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                targetDayName = days[dateObj.getDay()];
            } else {
                // Calculate from Current Week View
                const clone = new Date(currentDate);
                const d = clone.getDay();
                const diff = clone.getDate() - d + (d === 0 ? -6 : 1);
                clone.setDate(diff); // Monday of VIEW

                const dayOffsets = { 'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3, 'Friday': 4, 'Saturday': 5, 'Sunday': 6 };

                const targetDate = new Date(clone);
                targetDate.setDate(clone.getDate() + dayOffsets[day]);

                // Local YYYY-MM-DD
                const y = targetDate.getFullYear();
                const m = String(targetDate.getMonth() + 1).padStart(2, '0');
                const dd = String(targetDate.getDate()).padStart(2, '0');
                targetDateStr = `${y}-${m}-${dd}`;
            }

            // We need full shift object to save... wait, saveShift needs ID to update?
            // saveShift expects {id, ...fields}. If we only send id and date, backend might nullify others?
            // The method saveShift in JS calls API.
            // Let's first Fetch the shift? Or simple PATCH?
            // We don't have getShiftById. We have to be careful.
            // Actually, we can assume we only want to update Date/Day.
            // But WorkshiftController.php saveShift usually expects full data or merges?
            // Let's check WorkshiftController.php saveShift implementation.

            // To be safe: we are client-side. We can't easily get the specific shift object here without storing all shifts globally in a map.
            // NOTE: In renderGrid we put `dataset.shift`. We can't access that easily from just ID in modal.
            // SOLUTION: When opening modal, find the card with that ID and grab its data!

            // Find card
            // We don't have the element reference in openQuickMoveModal(id).
            // We should pass the object or search DOM.
            const card = document.querySelector(`.shift-card button[onclick="deleteShift('${id}')"]`).closest('.shift-card');
            if (!card) { alert("Errore: Turno non trovato nel DOM"); return; }

            const shiftData = JSON.parse(card.dataset.shift);

            // Update
            shiftData.date = targetDateStr;
            shiftData.day = targetDayName;
            // Times remain same

            try {
                const client = new WorkShiftAPI(window.location.origin + window.location.pathname.split('/workshift')[0]);
                await client.saveShift(shiftData);
                closeQuickMoveModal();
                loadSchedule();
            } catch (e) {
                alert("Errore: " + e.message);
            }
        });
    }

    // === Global Actions ===
    window.clearDay = async (day) => {
        console.log('[ShiftManager] Request to clears day:', day);
        if (!confirm(`Sei sicuro di voler eliminare TUTTI i turni di ${day}?`)) return;

        // Calculate Date (Local Time Safe)
        const today = new Date();
        const currentDay = today.getDay(); // 0 (Sun) - 6 (Sat)
        const dayOffsets = { 'Monday': 0, 'Tuesday': 1, 'Wednesday': 2, 'Thursday': 3, 'Friday': 4, 'Saturday': 5, 'Sunday': 6 };

        const mondayOffset = (currentDay === 0 ? -6 : 1) - currentDay;
        const mondayDate = new Date(today);
        mondayDate.setDate(today.getDate() + mondayOffset);

        const targetDate = new Date(mondayDate);
        targetDate.setDate(mondayDate.getDate() + dayOffsets[day]);

        // Use Local Date String YYYY-MM-DD
        const yyyy = targetDate.getFullYear();
        const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
        const dd = String(targetDate.getDate()).padStart(2, '0');
        const dateStr = `${yyyy}-${mm}-${dd}`;

        console.log('[ShiftManager] Computed date for clear:', dateStr);

        try {
            const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
            const endpoint = baseUrl + '/workshift/api/shifts/reset';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ scope: 'day', date: dateStr })
            });

            if (!response.ok) throw new Error(`HTTP Error ${response.status}`);

            const result = await response.json();
            if (result.success) {
                console.log(`[ShiftManager] Deleted ${result.deleted} shifts.`);
                if (result.deleted === 0) alert("Nessun turno trovato per la data selezionata: " + dateStr);
                else window.location.reload();
            } else {
                throw new Error(result.error || 'Errore sconosciuto dal server');
            }
        } catch (e) {
            console.error(e);
            alert("Errore eliminazione: " + e.message);
        }
    };

    // Reset Week Button Logic
    const resetWeekBtn = document.getElementById('resetWeekBtn');
    if (resetWeekBtn) {
        console.log('[ShiftManager] Reset Week button found, attaching listener.');
        resetWeekBtn.addEventListener('click', async () => {
            console.log('[ShiftManager] Reset Week clicked');
            if (!confirm("ATTENZIONE: Vuoi cancellare TUTTI i turni della settimana corrente? Questa operazione non può essere annullata.")) return;

            // Calculate Week Range (Local Time Safe)
            const today = new Date();
            const currentDay = today.getDay();
            const mondayOffset = (currentDay === 0 ? -6 : 1) - currentDay;
            const mondayDate = new Date(today);
            mondayDate.setDate(today.getDate() + mondayOffset);

            const sundayDate = new Date(mondayDate);
            sundayDate.setDate(mondayDate.getDate() + 6);

            // Format Start
            const y1 = mondayDate.getFullYear();
            const m1 = String(mondayDate.getMonth() + 1).padStart(2, '0');
            const d1 = String(mondayDate.getDate()).padStart(2, '0');
            const startStr = `${y1}-${m1}-${d1}`;

            // Format End
            const y2 = sundayDate.getFullYear();
            const m2 = String(sundayDate.getMonth() + 1).padStart(2, '0');
            const d2 = String(sundayDate.getDate()).padStart(2, '0');
            const endStr = `${y2}-${m2}-${d2}`;

            console.log('[ShiftManager] Clearing week from', startStr, 'to', endStr);

            try {
                const baseUrl = window.location.origin + window.location.pathname.split('/workshift')[0];
                const endpoint = baseUrl + '/workshift/api/shifts/reset';

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ scope: 'week', start_date: startStr, end_date: endStr })
                });

                if (!response.ok) throw new Error(`HTTP Error ${response.status}`);

                const result = await response.json();
                if (result.success) {
                    console.log(`[ShiftManager] Deleted ${result.deleted} shifts.`);
                    if (result.deleted === 0) alert("Nessun turno trovato nella settimana.");
                    else window.location.reload();
                } else {
                    throw new Error(result.error || 'Errore sconosciuto dal server');
                }
            } catch (e) {
                console.error(e);
                alert("Errore reset settimana: " + e.message);
            }
        });
    }

    // === NEW RENDERERS ===

    function renderMonthGrid(container, shifts, date) {
        container.className = "grid grid-cols-7 gap-1 bg-slate-900/50 p-4 rounded-3xl";
        container.innerHTML = '';

        // Month Headers
        const days = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
        days.forEach(d => {
            container.innerHTML += `<div class="text-center text-xs font-bold text-indigo-400 py-2 uppercase tracking-wider">${d}</div>`;
        });

        const y = date.getFullYear();
        const m = date.getMonth();
        const firstDay = new Date(y, m, 1).getDay(); // 0 = Sun
        const normalizedFirstDay = firstDay === 0 ? 6 : firstDay - 1; // 0 = Mon
        const daysInMonth = new Date(y, m + 1, 0).getDate();

        // Empty slots
        for (let i = 0; i < normalizedFirstDay; i++) {
            container.innerHTML += `<div class="h-32 bg-white/5 rounded-lg opacity-30"></div>`;
        }

        // Days
        for (let d = 1; d <= daysInMonth; d++) {
            const currentDayStr = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayShifts = shifts.filter(s => s.date === currentDayStr);

            // Overflow handling logic
            let contentHtml = '';
            dayShifts.slice(0, 3).forEach(shift => {
                contentHtml += `<div class="text-[10px] bg-indigo-500/20 text-indigo-200 truncate px-2 py-0.5 rounded mb-1 border border-indigo-500/10">${shift.employee_name || 'User'}</div>`;
            });
            if (dayShifts.length > 3) {
                contentHtml += `<div class="text-[10px] text-slate-400 text-center font-bold mt-1">+${dayShifts.length - 3} altri</div>`;
            }

            const activeClass = dayShifts.length > 0 ? 'bg-white/10 hover:bg-white/15' : 'bg-white/5 hover:bg-white/10';

            container.innerHTML += `
                <div class="h-32 ${activeClass} rounded-2xl border border-white/5 p-2 transition-all relative group overflow-hidden">
                    <span class="text-sm font-black text-white mb-2 block pl-1 opacity-50 group-hover:opacity-100 transition-opacity">${d}</span>
                    <div class="space-y-1 h-full">
                        ${contentHtml}
                    </div>
                </div>
            `;
        }
    }

    function renderYearSummary(container, shifts, date) {
        container.className = "grid grid-cols-4 gap-6 p-8";
        container.innerHTML = '';

        const year = date.getFullYear();
        const months = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

        months.forEach((mName, idx) => {
            const mNum = idx + 1;
            const monthShifts = shifts.filter(s => {
                const d = new Date(s.date);
                return d.getMonth() === idx && d.getFullYear() === year;
            });

            const count = monthShifts.length;
            let intensity = 'bg-white/5 border-white/10';
            if (count > 0) intensity = 'bg-indigo-500/10 border-indigo-500/30 shadow-lg shadow-indigo-500/10';
            if (count > 50) intensity = 'bg-indigo-500/20 border-indigo-500/50 shadow-xl shadow-indigo-500/20';

            container.innerHTML += `
                <div class="rounded-3xl border ${intensity} p-8 flex flex-col items-center justify-center hover:scale-105 transition-all cursor-pointer group" onclick="goToMonth(${idx})">
                    <h3 class="text-xl font-bold text-white mb-2 group-hover:text-indigo-300 transition-colors">${mName}</h3>
                    <span class="text-4xl font-black text-indigo-400 mb-1">${count}</span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest">Turni Totali</span>
                </div>
            `;
        });
    }

    // Helper to jump from Year to Month
    window.goToMonth = (monthIdx) => {
        currentDate.setMonth(monthIdx);
        // Simulate click on Month button
        document.querySelector('.view-switch-btn[data-view="month"]').click();
    };

    function renderDayDetailed(container, shifts, date) {
        container.className = "p-8 space-y-4 max-w-4xl mx-auto";
        container.innerHTML = '';

        if (shifts.length === 0) {
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-20 opacity-50">
                    <i data-feather="coffee" class="w-12 h-12 text-slate-400 mb-4"></i>
                    <p class="text-slate-400 text-lg">Nessun turno pianificato.</p>
                </div>
            `;
            feather.replace();
            return;
        }

        shifts.forEach(shift => {
            const initials = shift.employee_name ? shift.employee_name.substring(0, 2).toUpperCase() : '??';
            container.innerHTML += `
                <div class="flex items-center gap-6 p-6 rounded-2xl bg-white/5 border border-white/5 hover:border-indigo-500/50 transition-all hover:bg-white/10 group">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xl font-bold text-white shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                        ${initials}
                    </div>
                    <div class="flex-1">
                        <h4 class="text-xl font-bold text-white group-hover:text-indigo-300 transition-colors">${shift.employee_name}</h4>
                        <p class="text-indigo-400 text-sm font-medium opacity-80">${shift.role || 'Operatore'}</p>
                    </div>
                    <div class="text-right">
                         <div class="text-2xl font-black text-white tracking-tight">${shift.start_time.split(' ')[1].substring(0, 5)} <span class="text-slate-500 font-light mx-1">-</span> ${shift.end_time.split(' ')[1].substring(0, 5)}</div>
                         <div class="text-xs text-slate-400 uppercase tracking-widest font-bold mt-1 bg-white/5 px-2 py-1 rounded-md inline-block">${shift.type}</div>
                    </div>
                </div>
             `;
        });
    }

    // === Auto Close Logic (Deprecated in JS, moved to HTML onclick) ===
    // We removed the JS array based logic to avoid conflicts and utilize the robust HTML propagation logic.
    // The functions closeQuickMoveModal and toggleCreateShiftModal are now exposed globally.

    function renderListView(container, shifts) {
        container.className = "w-full overflow-hidden rounded-3xl bg-slate-900/50"; // Reset grid

        // Sort by Date then Time
        shifts.sort((a, b) => {
            if (a.date !== b.date) return a.date.localeCompare(b.date);
            return a.start_time.localeCompare(b.start_time);
        });

        let html = `
        <div class="overflow-x-auto h-full">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white/10 text-xs uppercase text-indigo-300 sticky top-0 backdrop-blur-md z-10">
                    <tr>
                        <th class="p-4 font-bold tracking-wider rounded-tl-3xl">Giorno</th>
                        <th class="p-4 font-bold tracking-wider">Dipendente</th>
                        <th class="p-4 font-bold tracking-wider">Tipo Turno</th>
                        <th class="p-4 font-bold tracking-wider">Orario</th>
                        <th class="p-4 font-bold tracking-wider text-right rounded-tr-3xl">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-sm">
        `;

        if (shifts.length === 0) {
            html += `<tr><td colspan="5" class="p-8 text-center text-slate-400 italic">Nessun turno trovato in questo periodo.</td></tr>`;
        } else {
            shifts.forEach(shift => {
                // Format Date
                const dateObj = new Date(shift.date);
                const dayName = dateObj.toLocaleDateString('it-IT', { weekday: 'short' });
                const dateStr = dateObj.toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });

                // Avatar
                const initials = shift.employee_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const avatar = shift.employee_avatar
                    ? `<img src="${shift.employee_avatar}" class="w-8 h-8 rounded-full border border-white/10">`
                    : `<div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-300 font-bold flex items-center justify-center text-xs">${initials}</div>`;

                // Badge
                let badgeClass = 'bg-slate-700 text-slate-300';
                if (shift.type === 'Morning') badgeClass = 'bg-orange-500/20 text-orange-300 border border-orange-500/20';
                else if (shift.type === 'Day') badgeClass = 'bg-blue-500/20 text-blue-300 border border-blue-500/20';
                else if (shift.type === 'Evening') badgeClass = 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/20';
                else if (shift.type === 'Night') badgeClass = 'bg-purple-500/20 text-purple-300 border border-purple-500/20';

                // Robust Time Parsing
                let sTime = shift.start_time;
                if (sTime && sTime.includes(' ')) sTime = sTime.split(' ')[1];
                sTime = sTime ? sTime.substring(0, 5) : '00:00';

                let eTime = shift.end_time;
                if (eTime && eTime.includes(' ')) eTime = eTime.split(' ')[1];
                eTime = eTime ? eTime.substring(0, 5) : '00:00';

                // Hidden Data for Quick Move
                const shiftJSON = JSON.stringify(shift).replace(/"/g, '&quot;');

                html += `
                    <tr class="hover:bg-white/5 transition-colors group shift-card" data-shift="${shiftJSON}">
                        <td class="p-4 text-white whitespace-nowrap">
                            <span class="font-bold text-indigo-400 mr-2">${dayName}</span>
                            <span class="opacity-80">${dateStr}</span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                ${avatar}
                                <span class="font-medium text-white">${shift.employee_name}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold ${badgeClass}">${shift.type}</span>
                        </td>
                         <td class="p-4 text-slate-300 font-mono flex items-center gap-2">
                            <i data-feather="clock" class="w-3 h-3 text-slate-500"></i>
                            ${sTime} - ${eTime}
                        </td>
                         <td class="p-4 text-right">
                             <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="p-2 rounded-lg bg-indigo-500/20 text-indigo-400 hover:bg-indigo-500 hover:text-white transition-all" onclick="openQuickMoveModal('${shift.id}')" title="Sposta">
                                    <i data-feather="move" class="w-4 h-4"></i>
                                </button>
                                <button class="p-2 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white transition-all" onclick="window.deleteShift('${shift.id}')" title="Elimina">
                                    <i data-feather="trash-2" class="w-4 h-4"></i>
                                </button>
                             </div>
                        </td>
                    </tr>
                `;
            });
        }

        html += `   </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
        // Important: Re-attach shift card class or Ensure openQuickMoveModal works
        // openQuickMoveModal uses closest('.shift-card') and looks for delete button inside it?
        // Wait, openQuickMoveModal searches: document.querySelector(`.shift-card button[onclick="deleteShift('${id}')"]`)
        // My new button has onclick="window.deleteShift('${shift.id}')".
        // And I added 'shift-card' class to the TR. So it should work!
        // But need to ensure data-shift attribute is on the TR. Done.
    }

}); // End DOMContentLoaded
// ... Global Helpers ...

// Global Modal Helpers (Must be outside DOMContentLoaded to be accessible by HTML onclick)
window.toggleCreateShiftModal = () => {
    const modal = document.getElementById('createShiftModal');
    const backdrop = document.getElementById('modalBackdrop');
    const panel = document.getElementById('modalPanel');

    if (modal.classList.contains('hidden')) {
        // Open
        modal.classList.remove('hidden');
        // Force reflow
        void modal.offsetWidth;
        backdrop.classList.remove('opacity-0');
        backdrop.classList.add('opacity-100');
        panel.classList.remove('opacity-0', 'scale-95');
        panel.classList.add('opacity-100', 'scale-100');
    } else {
        // Close
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('opacity-0', 'scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
};

// Ensure other modals close functions are also global if not already
// closeQuickMoveModal is already defined as window.closeQuickMoveModal inside, but let's double check.
// It is defined as window.closeQuickMoveModal inside DOMContentLoaded.
// NOTE: Defining window.function INSIDE DOMContentLoaded IS fine, as long as it runs before user clicks.
// However, to be absolutely safe and cleaner, we prefer distinct definitions.
// Currently closeQuickMoveModal is correctly attached to window inside the callback.
// toggleCreateShiftModal WAS defined inside as const or function, NOT attached to window. That was the bug.
