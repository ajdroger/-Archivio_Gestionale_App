
class TaskManager {
    constructor() {
        this.tasks = [];
        this.currentFilter = 'all';
        this.pendingDeleteId = null;
        this.pendingAction = null; // 'delete' or 'clear'
        this.init();
    }

    get API_URL() {
        return (window.MCAG_BASE_URL || '') + '/taskflow/api/tasks';
    }

    async init() {
        await this.loadTasks();
        this.bindEvents();
        this.bindModalEvents();
        this.initParticles();
        this.animateStats();
    }

    bindEvents() {
        const form = document.getElementById('task-form');
        const input = document.getElementById('task-input');
        const clearBtn = document.getElementById('clear-completed');
        const sampleBtn = document.getElementById('add-sample-tasks');
        const filterButtons = document.querySelectorAll('.filter-btn');

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (input.value.trim()) {
                    await this.addTask(input.value.trim());
                    input.value = '';
                }
            });
        }

        if (clearBtn) clearBtn.addEventListener('click', () => this.confirmClearCompleted());
        if (sampleBtn) sampleBtn.addEventListener('click', () => this.addSampleTasks());

        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.currentFilter = btn.dataset.filter;
                this.renderTasks();
            });
        });
    }

    bindModalEvents() {
        const confirmBtn = document.getElementById('confirm-delete-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (this.pendingAction === 'delete' && this.pendingDeleteId) {
                    this.deleteTask(this.pendingDeleteId);
                } else if (this.pendingAction === 'clear') {
                    this.executeClearCompleted();
                }
                this.closeDeleteModal();
            });
        }
    }

    async loadTasks() {
        try {
            const response = await fetch(this.API_URL);
            if (!response.ok) throw new Error('Network response was not ok');
            this.tasks = await response.json();
            this.renderTasks();
            this.updateStats();
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nel caricamento delle attività', 'error');
            this.tasks = [];
        }
    }

    async addTask(text) {
        try {
            // Get CSRF Token from Meta Tags
            const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
            const csrfValue = document.querySelector('meta[name="csrf-value"]')?.getAttribute('content');

            const headers = { 'Content-Type': 'application/json' };
            if (csrfName && csrfValue) {
                // Determine keys from window global or defaults
                // Slim CSRF often expects keys like 'csrf_name' and 'csrf_value' in the body 
                // OR custom headers. Let's try headers assuming middleware checks X-CSRF-Token or similar?
                // Actually Slim-CSRF usually checks POST body.
                // Let's send in body for safety, OR headers. 
                // Standard Slim practice often requires them in body for POST.
            }
            // Wait, standard practice is adding them to the payload.

            const payload = {
                text: text
            };

            if (csrfName && csrfValue) {
                payload.csrf_name = csrfName;
                payload.csrf_value = csrfValue;
            }

            const response = await fetch(this.API_URL, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload)
            });
            const data = await response.json();

            if (response.ok) {
                // Refresh list or add locally
                this.loadTasks(); // Safer to reload to be in sync
                this.showNotification('Attività aggiunta con successo!', 'success');
            } else {
                throw new Error(data.error || 'Server Error');
            }
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nell\'aggiunta dell\'attività', 'error');
        }
    }

    async toggleTask(id, completed) {
        try {
            const payload = { id: id, completed: completed };
            const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
            const csrfValue = document.querySelector('meta[name="csrf-value"]')?.getAttribute('content');
            if (csrfName && csrfValue) {
                payload.csrf_name = csrfName;
                payload.csrf_value = csrfValue;
            }

            await fetch(this.API_URL, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            // Optimistic update locally
            const task = this.tasks.find(t => t.id == id);
            if (task) task.completed = completed;

            if (completed) this.celebrateCompletion();
            this.renderTasks();
            this.updateStats();
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nell\'aggiornamento dell\'attività', 'error');
            this.loadTasks(); // Revert on error
        }
    }

    async deleteTask(id) {
        try {
            const payload = { id: id };
            const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
            const csrfValue = document.querySelector('meta[name="csrf-value"]')?.getAttribute('content');
            if (csrfName && csrfValue) {
                payload.csrf_name = csrfName;
                payload.csrf_value = csrfValue;
            }

            await fetch(this.API_URL, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            this.tasks = this.tasks.filter(task => task.id != id);
            this.renderTasks();
            this.updateStats();
            this.showNotification('Attività eliminata con successo', 'info');
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nella cancellazione dell\'attività', 'error');
        }
    }

    async editTask(id, newText) {
        try {
            const payload = { id: id, text: newText };
            const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
            const csrfValue = document.querySelector('meta[name="csrf-value"]')?.getAttribute('content');
            if (csrfName && csrfValue) {
                payload.csrf_name = csrfName;
                payload.csrf_value = csrfValue;
            }

            await fetch(this.API_URL, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const task = this.tasks.find(t => t.id == id);
            if (task) task.text = newText;

            this.renderTasks();
            this.updateStats();
            this.showNotification('Attività modificata con successo', 'success');
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nella modifica dell\'attività', 'error');
        }
    }

    renderTasks() {
        const container = document.getElementById('tasks-container');
        if (!container) return;
        container.innerHTML = '';

        let filteredTasks = this.tasks;
        if (this.currentFilter === 'active') {
            filteredTasks = this.tasks.filter(task => !task.completed);
        } else if (this.currentFilter === 'completed') {
            filteredTasks = this.tasks.filter(task => task.completed);
        }

        // Sort: pending first, then by date desc
        filteredTasks.sort((a, b) => {
            if (a.completed === b.completed) {
                return new Date(b.created_at) - new Date(a.created_at);
            }
            return a.completed ? 1 : -1;
        });

        if (filteredTasks.length === 0) {
            const emptyMessage = {
                all: { title: 'Nessuna attività', desc: 'Aggiungi la tua prima attività per iniziare!' },
                active: { title: 'Nessuna attività da fare', desc: 'Tutte le attività sono completate!' },
                completed: { title: 'Nessuna attività completata', desc: 'Completa qualche attività per vederla qui!' }
            };
            const msg = emptyMessage[this.currentFilter] || emptyMessage['all'];

            container.innerHTML = `
                <div class="text-center py-16">
                    <div class="w-24 h-24 mx-auto mb-6 bg-violet-500/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i data-feather="check-square" class="w-12 h-12 text-violet-300"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-violet-100 mb-2">${msg.title}</h3>
                    <p class="text-violet-200/70">${msg.desc}</p>
                </div>
            `;
            if (typeof feather !== 'undefined') feather.replace();
            return;
        }

        filteredTasks.forEach((task, index) => {
            const taskElement = document.createElement('div');
            // Ensure ID is passed as string for dataset
            const taskId = String(task.id);
            taskElement.className = 'task-item bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-white/5 hover:border-violet-400/40 transition-all duration-300 mb-4';
            taskElement.setAttribute('data-id', taskId);

            const isChecked = task.completed == 1 || task.completed === true ? 'checked' : '';
            const opacityClass = (task.completed == 1 || task.completed === true) ? 'opacity-50' : '';
            const strikeClass = (task.completed == 1 || task.completed === true) ? 'line-through decoration-violet-400' : '';

            taskElement.innerHTML = `
                <div class="flex items-center gap-4">
                    <input 
                        type="checkbox" 
                        class="custom-checkbox w-6 h-6 rounded-lg border-2 border-violet-300/50 text-violet-500 focus:ring-violet-500 bg-transparent cursor-pointer" 
                        ${isChecked} 
                        onchange="taskManager.toggleTask('${taskId}', this.checked)"
                    >
                    <div class="flex-grow ${opacityClass}">
                        <div class="task-text text-white font-medium text-lg ${strikeClass}">
                            ${this.escapeHtml(task.text)}
                        </div>
                        <div class="text-violet-200/50 text-sm mt-1">
                            ${this.formatDate(task.created_at)}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            onclick="taskManager.startEdit('${taskId}')"
                            class="p-2 bg-violet-500/10 hover:bg-violet-500/20 text-violet-300 rounded-xl transition-all duration-300 hover:scale-110 shadow-md"
                        >
                            <i data-feather="edit-2" class="w-5 h-5"></i>
                        </button>
                        <button 
                            onclick="taskManager.confirmDelete('${taskId}')"
                            class="p-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl transition-all duration-300 hover:scale-110 shadow-md"
                        >
                            <i data-feather="trash" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <div class="edit-form mt-4 hidden">
                    <div class="flex gap-3">
                        <input type="text" class="edit-input w-full px-4 py-3 bg-white/10 border-2 border-violet-400/30 rounded-xl text-white focus:outline-none focus:border-violet-400 transition-all duration-300" value="${this.escapeHtml(task.text)}">
                        <button onclick="taskManager.saveEdit('${taskId}')" class="px-5 py-3 bg-violet-500 hover:bg-violet-600 text-white rounded-xl transition-all duration-300 font-bold">Salva</button>
                        <button onclick="taskManager.cancelEdit()" class="px-5 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-300 font-medium">Annulla</button>
                    </div>
                </div>
            `;

            container.appendChild(taskElement);
        });

        if (typeof feather !== 'undefined') feather.replace();
    }

    startEdit(id) {
        const taskElement = document.querySelector(`[data-id="${id}"]`);
        if (!taskElement) return;
        const taskText = taskElement.querySelector('.task-text');
        const editForm = taskElement.querySelector('.edit-form');

        // Hide text container or manipulate visibility
        // Actually, let's just show form below it as per layout
        editForm.classList.remove('hidden');
        editForm.querySelector('.edit-input').focus();
    }

    async saveEdit(id) {
        const taskElement = document.querySelector(`[data-id="${id}"]`);
        const newText = taskElement.querySelector('.edit-input').value.trim();

        if (newText) {
            await this.editTask(id, newText);
        }
    }

    cancelEdit() {
        this.renderTasks();
    }

    confirmDelete(id) {
        this.pendingDeleteId = id;
        this.pendingAction = 'delete';
        this.openDeleteModal('Elimina Attività', 'Sei sicuro di voler eliminare questa attività? L\'azione non può essere annullata.');
    }

    confirmClearCompleted() {
        this.pendingAction = 'clear';
        this.openDeleteModal('Elimina Completate', 'Sei sicuro di voler eliminare tutte le attività completate?');
    }

    executeClearCompleted() {
        const payload = { action: 'clearCompleted' };
        const csrfName = document.querySelector('meta[name="csrf-name"]')?.getAttribute('content');
        const csrfValue = document.querySelector('meta[name="csrf-value"]')?.getAttribute('content');
        if (csrfName && csrfValue) {
            payload.csrf_name = csrfName;
            payload.csrf_value = csrfValue;
        }

        fetch(this.API_URL, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(() => this.loadTasks());
    }

    openDeleteModal(title, message) {
        const modal = document.getElementById('delete-modal');
        const backdrop = document.getElementById('delete-modal-backdrop');
        const panel = document.getElementById('delete-modal-panel');
        const titleEl = document.getElementById('modal-title');
        const msgEl = modal.querySelector('p');

        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;

        // CRITICAL: Move to body to avoid Z-Index stacking context issues with main content
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        modal.classList.remove('hidden');

        // Animation
        setTimeout(() => {
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            panel.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
            panel.classList.add('opacity-100', 'scale-100');
        }, 10);

        // Re-init feather icons in modal in case they weren't parsed
        if (typeof feather !== 'undefined') feather.replace();
    }

    closeDeleteModal() {
        const modal = document.getElementById('delete-modal');
        const backdrop = document.getElementById('delete-modal-backdrop');
        const panel = document.getElementById('delete-modal-panel');

        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0', 'pointer-events-none');

        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('opacity-0', 'scale-95', 'pointer-events-none');

        setTimeout(() => {
            modal.classList.add('hidden');
            this.pendingDeleteId = null;
            this.pendingAction = null;
        }, 300);
    }

    async addSampleTasks() {
        const sampleTasks = ['Meeting Progetto Alpha', 'Revisione KPI Mensili', 'Pranzo con il Team'];
        for (const t of sampleTasks) {
            await this.addTask(t);
        }
    }

    updateStats() {
        const total = this.tasks.length;
        const completed = this.tasks.filter(task => task.completed == 1 || task.completed === true).length;
        const active = total - completed;

        const setVal = (label, val) => {
            const el = document.querySelector(`stats-card[label="${label}"]`);
            if (el) el.setAttribute('count', val);
        };

        setVal('Totali', total);
        setVal('Completate', completed);
        setVal('Da Fare', active);
    }

    animateStats() {
        // Keep existing if needed or simplify
    }

    celebrateCompletion() {
        this.createConfetti();
        this.showNotification('Ottimo lavoro! 🎉', 'success');
    }

    createConfetti() {
        const colors = ['#a78bfa', '#fbbf24', '#34d399']; // Violet theme
        for (let i = 0; i < 30; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.position = 'fixed';
                confetti.style.top = '-10px';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.zIndex = '9999';
                document.body.appendChild(confetti);

                const animation = confetti.animate([
                    { transform: 'translateY(0) rotate(0)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ], {
                    duration: 2000 + Math.random() * 1000,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                });

                animation.onfinish = () => confetti.remove();
            }, i * 50);
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const colors = {
            success: 'from-emerald-500 to-teal-600',
            error: 'from-rose-500 to-red-600',
            info: 'from-violet-500 to-purple-600'
        };

        notification.className = `fixed top-6 right-6 bg-gradient-to-r ${colors[type] || colors.info} text-white px-6 py-4 rounded-xl shadow-2xl z-50 transform translate-x-full transition-transform duration-500 backdrop-blur-sm border border-white/20 font-medium flex items-center gap-3`;
        notification.innerHTML = `<span>${message}</span>`;

        document.body.appendChild(notification);

        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });

        setTimeout(() => {
            notification.style.transform = 'translateX(120%)';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('it-IT', { day: 'numeric', month: 'long' });
    }

    initParticles() {
        if (typeof particlesJS !== 'undefined') {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 30 },
                    color: { value: '#a78bfa' },
                    shape: { type: 'circle' },
                    opacity: { value: 0.1 },
                    size: { value: 3 },
                    move: { enable: true, speed: 0.3 }
                }
            });
        }
    }
}

// Global instance
window.taskManager = new TaskManager();