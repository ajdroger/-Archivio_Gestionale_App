
class TaskManager {
    constructor() {
        this.tasks = [];
        this.currentFilter = 'all';
        this.storageKey = 'taskflow_tasks';
        this.init();
    }
async init() {
        await this.loadTasks();
        this.bindEvents();
        this.initParticles();
        this.animateStats();
    }

    bindEvents() {
        const form = document.getElementById('task-form');
        const input = document.getElementById('task-input');
        const clearBtn = document.getElementById('clear-completed');
        const sampleBtn = document.getElementById('add-sample-tasks');
        const filterButtons = document.querySelectorAll('.filter-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (input.value.trim()) {
                await this.addTask(input.value.trim());
                input.value = '';
            }
        });

        clearBtn.addEventListener('click', () => this.confirmClearCompleted());
        sampleBtn.addEventListener('click', () => this.addSampleTasks());
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.currentFilter = btn.dataset.filter;
                this.renderTasks();
            });
        });
}
    async loadTasks() {
        try {
            // Carica da localStorage invece che da API
            const stored = localStorage.getItem(this.storageKey);
            this.tasks = stored ? JSON.parse(stored) : [];
            this.renderTasks();
            this.updateStats();
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nel caricamento delle attività', 'error');
            this.tasks = [];
        }
    }
    async addTask(text) {
        const task = {
            text: text,
            completed: false,
            created_at: new Date().toISOString(),
            id: Date.now() + Math.random().toString(36).substr(2, 9) // ID più robusto
        };

        try {
            // Salva in localStorage invece che chiamare API
            this.tasks.unshift(task);
            localStorage.setItem(this.storageKey, JSON.stringify(this.tasks));
            this.renderTasks();
            this.updateStats();
            this.showNotification('Attività aggiunta con successo!', 'success');
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nell\'aggiunta dell\'attività', 'error');
        }
    }
    async toggleTask(id, completed) {
        try {
            // Aggiorna localStorage invece che chiamare API
            const taskIndex = this.tasks.findIndex(task => task.id == id);
            if (taskIndex !== -1) {
                this.tasks[taskIndex].completed = completed;
                localStorage.setItem(this.storageKey, JSON.stringify(this.tasks));
                
                if (completed) {
                    this.celebrateCompletion();
                }
                
                this.renderTasks();
                this.updateStats();
            }
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nell\'aggiornamento dell\'attività', 'error');
        }
    }
    async deleteTask(id) {
        try {
            // Rimuovi da localStorage invece che chiamare API
            this.tasks = this.tasks.filter(task => task.id != id);
            localStorage.setItem(this.storageKey, JSON.stringify(this.tasks));
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
            // Aggiorna localStorage invece che chiamare API
            const taskIndex = this.tasks.findIndex(task => task.id == id);
            if (taskIndex !== -1) {
                this.tasks[taskIndex].text = newText;
                localStorage.setItem(this.storageKey, JSON.stringify(this.tasks));
                this.renderTasks();
                this.updateStats();
                this.showNotification('Attività modificata con successo', 'success');
            }
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nella modifica dell\'attività', 'error');
        }
    }
renderTasks() {
        const container = document.getElementById('tasks-container');
        container.innerHTML = '';

        let filteredTasks = this.tasks;
        if (this.currentFilter === 'active') {
            filteredTasks = this.tasks.filter(task => !task.completed);
        } else if (this.currentFilter === 'completed') {
            filteredTasks = this.tasks.filter(task => task.completed);
        }

        filteredTasks.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

        if (filteredTasks.length === 0) {
            const emptyMessage = {
                all: { title: 'Nessuna attività', desc: 'Aggiungi la tua prima attività per iniziare!' },
                active: { title: 'Nessuna attività da fare', desc: 'Tutte le attività sono completate!' },
                completed: { title: 'Nessuna attività completata', desc: 'Completa qualche attività per vederla qui!' }
            };
            
            const msg = emptyMessage[this.currentFilter];
            
            container.innerHTML = `
                <div class="text-center py-16">
                    <div class="w-24 h-24 mx-auto mb-6 bg-violet-500/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i data-feather="check-square" class="w-12 h-12 text-violet-300"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-violet-100 mb-2">${msg.title}</h3>
                    <p class="text-violet-200/70">${msg.desc}</p>
                </div>
            `;
            feather.replace();
            return;
        }

        filteredTasks.forEach((task, index) => {
            const taskElement = document.createElement('div');
            taskElement.className = 'task-item bg-white/5 backdrop-blur-sm rounded-2xl p-6 border border-violet-300/20 hover:border-violet-400/40 transition-all duration-300';
            taskElement.setAttribute('data-id', task.id);
            
            taskElement.innerHTML = `
                <div class="flex items-center gap-4">
                    <input 
                        type="checkbox" 
                        class="custom-checkbox" 
                        ${task.completed ? 'checked' : ''} 
                        onchange="taskManager.toggleTask('${task.id}', this.checked)"
                    >
                    <div class="flex-grow ${task.completed ? 'opacity-50' : ''}">
                        <div class="task-text text-white font-medium text-lg ${task.completed ? 'line-through' : ''}">
                            ${this.escapeHtml(task.text)}
                        </div>
                        <div class="text-violet-200/70 text-sm mt-1">
                            ${this.formatDate(task.created_at)} ${task.completed ? ' • Completata' : ''}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            onclick="taskManager.startEdit('${task.id}')"
                            class="p-3 bg-violet-500/20 hover:bg-violet-500/40 text-violet-100 rounded-xl transition-all duration-300 hover:scale-110 shadow-md"
                        >
                            <i data-feather="edit-2" class="w-5 h-5"></i>
                        </button>
                        <button 
                            onclick="taskManager.confirmDelete('${task.id}')"
                            class="p-3 bg-red-500/20 hover:bg-red-500/40 text-red-100 rounded-xl transition-all duration-300 hover:scale-110 shadow-md"
                        >
                            <i data-feather="trash" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <div class="edit-form mt-4 hidden">
                    <div class="flex gap-3">
                        <input type="text" class="edit-input w-full px-4 py-3 bg-white/5 border-2 border-violet-300/30 rounded-xl text-white focus:outline-none focus:border-violet-400 transition-all duration-300" value="${this.escapeHtml(task.text)}">
                        <button onclick="taskManager.saveEdit('${task.id}')" class="px-5 py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-all duration-300 font-medium">Salva</button>
                        <button onclick="taskManager.cancelEdit()" class="px-5 py-3 bg-slate-500 hover:bg-slate-600 text-white rounded-xl transition-all duration-300 font-medium">Annulla</button>
                    </div>
                </div>
            `;
            
            container.appendChild(taskElement);
        });

        feather.replace();
    }
startEdit(id) {
        const taskElement = document.querySelector(`[data-id="${id}"]`);
        const taskText = taskElement.querySelector('.task-text');
        const editForm = taskElement.querySelector('.edit-form');
        
        taskText.classList.add('hidden');
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
        const modal = document.querySelector('confirm-modal');
        modal.show('Elimina Attività', 'Sei sicuro di voler eliminare questa attività?', 'danger', async () => {
            await this.deleteTask(id);
        });
    }
    confirmClearCompleted() {
        const completedCount = this.tasks.filter(task => task.completed).length;
        if (completedCount === 0) {
            this.showNotification('Non ci sono attività completate da eliminare', 'info');
            return;
        }

        const modal = document.querySelector('confirm-modal');
        modal.show('Elimina Completate', `Sei sicuro di voler eliminare ${completedCount} attività completate?`, 'danger', async () => {
            try {
                // Rimuovi da localStorage invece che chiamare API
                this.tasks = this.tasks.filter(task => !task.completed);
                localStorage.setItem(this.storageKey, JSON.stringify(this.tasks));
                this.renderTasks();
                this.updateStats();
                this.showNotification(`${completedCount} attività completate eliminate!`, 'success');
            } catch (error) {
                console.error('Errore:', error);
                this.showNotification('Errore nella cancellazione delle attività completate', 'error');
            }
        });
    }
    async addSampleTasks() {
        const sampleTasks = [
            'Preparare la presentazione per il meeting',
            'Completare la revisione del codice',
            'Fare la spesa della settimana',
            'Prenotare il volo per le vacanze',
            'Studiare per l\'esame di certificazione',
            'Organizzare la festa di compleanno',
            'Aggiornare il portfolio online',
            'Fare allenamento in palestra',
            'Scrivere un nuovo post per il blog',
            'Pulire la casa e organizzare la stanza degli ospiti',
            'Controllare le email e rispondere ai clienti',
            'Preparare il pranzo della domenica'
        ];

        const shuffled = sampleTasks.sort(() => 0.5 - Math.random());
        const selected = shuffled.slice(0, 6);

        try {
            // Aggiungi direttamente a localStorage
            const newTasks = selected.map(task => ({
                text: task,
                completed: Math.random() > 0.6,
                created_at: new Date(Date.now() - Math.random() * 7 * 24 * 60 * 60 * 1000).toISOString(), // Date casuali negli ultimi 7 giorni
                id: Date.now() + Math.random().toString(36).substr(2, 9)
            }));

            this.tasks = [...newTasks, ...this.tasks];
            localStorage.setItem(this.storageKey, JSON.stringify(this.tasks));
            this.renderTasks();
            this.updateStats();
            this.showNotification('Attività di esempio aggiunte con successo!', 'success');
        } catch (error) {
            console.error('Errore:', error);
            this.showNotification('Errore nell\'aggiunta di alcune attività', 'error');
        }
    }
updateStats() {
        const total = this.tasks.length;
        const completed = this.tasks.filter(task => task.completed).length;
        const active = total - completed;

        document.querySelector('stats-card[label="Totali"]').setAttribute('count', total);
        document.querySelector('stats-card[label="Completate"]').setAttribute('count', completed);
        document.querySelector('stats-card[label="Da Fare"]').setAttribute('count', active);
    }

    animateStats() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const cards = document.querySelectorAll('stats-card');
                    cards.forEach((card, index) => {
                        setTimeout(() => {
                            card.style.transform = 'translateY(-8px)';
                        }, index * 200);
                    });
                }
            });
        });
        
        const header = document.querySelector('header');
        if (header) observer.observe(header);
    }

    celebrateCompletion() {
        this.createConfetti();
        this.showNotification('Incredibile! Hai completato un\'attività! 🎉', 'success');
    }

    createConfetti() {
        const colors = ['#a78bfa', '#f0abfc', '#34d399', '#fb7185', '#60a5fa'];
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 0.5 + 's';
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 3000);
            }, i * 50);
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const colors = {
            success: 'from-emerald-500 to-teal-500',
            error: 'from-red-500 to-rose-500',
            info: 'from-violet-500 to-fuchsia-500'
        };
        
        notification.className = `fixed top-6 right-6 bg-gradient-to-r ${colors[type]} text-white px-6 py-4 rounded-2xl shadow-2xl z-50 transform translate-x-full transition-transform duration-500 backdrop-blur-sm border border-white/20`;
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i data-feather="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium">${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        feather.replace();
        
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
            if (diffHours === 0) {
                const diffMinutes = Math.floor(diffTime / (1000 * 60));
                return `${diffMinutes} min fa`;
            }
            return `${diffHours} ore fa`;
        } else if (diffDays === 1) {
            return 'Ieri';
        } else if (diffDays < 7) {
            return `${diffDays} giorni fa`;
        } else {
            return date.toLocaleDateString('it-IT');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    initParticles() {
        if (typeof particlesJS !== 'undefined') {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 80, density: { enable: true, value_area: 800 } },
                    color: { value: '#a78bfa' },
                    shape: { type: 'circle' },
                    opacity: { value: 0.3, random: true },
                    size: { value: 3, random: true },
                    move: {
                        enable: true,
                        speed: 1,
                        direction: 'none',
                        out_mode: 'out'
                    }
                },
                interactivity: {
                    events: {
                        onhover: { enable: true, mode: 'repulse' },
                        onclick: { enable: true, mode: 'push' }
                    }
                },
                retina_detect: true
            });
        }
    }
}

// Initialize TaskManager
const taskManager = new TaskManager();