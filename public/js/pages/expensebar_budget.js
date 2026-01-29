/**
 * ExpenseBar Genius Budget System
 * Handles real-time budget tracking, visualization, and updates.
 */

document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = window.MCAG_API_URL || '/expensebar/api';
    const api = {
        getStatus: `${baseUrl}/budget/status`,
        saveBudget: `${baseUrl}/budget/save`
    };

    const elements = {
        overviewContainer: document.getElementById('budgetOverview'),
        controlCenter: document.getElementById('budgetControls'),
        totalLimit: document.getElementById('totalLimit'),
        totalSpent: document.getElementById('totalSpent'),
        totalRemaining: document.getElementById('totalRemaining'),
        healthIndicator: document.getElementById('healthIndicator')
    };

    // State
    let currentData = null;

    // formatting
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
    };

    // Initialize
    async function init() {
        await loadData();
    }

    async function loadData() {
        try {
            const response = await fetch(api.getStatus);
            const data = await response.json();
            currentData = data;
            render(data);
        } catch (error) {
            console.error('Failed to load budget data', error);
            // Silent error or retry logic could go here
        }
    }

    function render(data) {
        // Render Aggregate Stats
        if (elements.totalLimit) elements.totalLimit.textContent = formatCurrency(data.aggregate.limit);
        if (elements.totalSpent) elements.totalSpent.textContent = formatCurrency(data.aggregate.spent);
        if (elements.totalRemaining) elements.totalRemaining.textContent = formatCurrency(data.aggregate.remaining);

        // Update Health Indicator Ring (Custom CSS logic needed for ring)
        if (elements.healthIndicator) {
            const healthColor = data.aggregate.health === 'critical' ? 'text-red-500' :
                (data.aggregate.health === 'warning' ? 'text-yellow-500' : 'text-emerald-500');
            elements.healthIndicator.className = `text-4xl font-bold ${healthColor} transition-colors duration-500`;
            elements.healthIndicator.textContent = `${data.aggregate.percentage}%`;
        }

        renderControls(data.categories);
        renderOverview(data.categories);
    }

    function renderControls(categories) {
        if (!elements.controlCenter) return;

        elements.controlCenter.innerHTML = categories.map(cat => `
            <div class="glass-panel p-4 rounded-xl border border-white/5 bg-gray-900/40 hover:bg-gray-800/40 transition-all group">
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg ${getCategoryColorBg(cat.category)} flex items-center justify-center">
                            <i data-feather="${getCategoryIcon(cat.category)}" class="w-4 h-4 ${getCategoryColorText(cat.category)}"></i>
                        </div>
                        <span class="font-medium text-white">${cat.category}</span>
                    </div>
                    <span class="text-sm font-mono text-emerald-400 update-value" data-cat="${cat.category}">${formatCurrency(cat.limit)}</span>
                </div>
                
                <div class="relative h-2 bg-gray-700 rounded-full mb-4">
                     <div class="absolute top-0 left-0 h-full rounded-full ${getHealthColorBg(cat.health)}" style="width: ${cat.percentage}%"></div>
                </div>

                <div class="flex items-center gap-4">
                    <input type="range" min="0" max="2000" step="50" value="${cat.limit}" 
                        class="w-full h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer range-slider"
                        data-cat="${cat.category}"
                    >
                </div>
            </div>
        `).join('');
        feather.replace();

        // Attach Process Handlers (Delegation)
        // Reset listeners if needed (simplest is just reusing the container)
        // Note: In a real app we'd remove old listeners, but here we can just attach to the container once
        // or check if attached. For simplicity in this script re-run, we attach to inputs directly or using efficient delegation.
    }

    // Delegation Logic
    if (elements.controlCenter) {
        elements.controlCenter.addEventListener('input', (e) => {
            if (e.target.matches('.range-slider')) {
                const cat = e.target.dataset.cat;
                const value = e.target.value;
                const label = elements.controlCenter.querySelector(`.update-value[data-cat='${cat}']`); // Safe usage here
                if (label) {
                    label.textContent = formatCurrency(value);
                }
            }
        });

        elements.controlCenter.addEventListener('change', (e) => {
            if (e.target.matches('.range-slider')) {
                const cat = e.target.dataset.cat;
                const value = e.target.value;
                window.saveBudget(cat, value);
            }
        });
    }

    function renderOverview(categories) {
        if (!elements.overviewContainer) return;

        // Filter valid budgets for overview
        const activeBudgets = categories.filter(c => c.is_set);

        if (activeBudgets.length === 0) {
            elements.overviewContainer.innerHTML = `
                <div class="col-span-full text-center py-12 text-gray-500">
                    <p>Nessun budget impostato. Usa i controlli per iniziare.</p>
                </div>`;
            return;
        }

        elements.overviewContainer.innerHTML = activeBudgets.map(cat => `
            <div class="relative p-6 rounded-2xl bg-gray-900/50 border border-white/5 overflow-hidden">
                <div class="flex justify-between items-start mb-4">
                    <div>
                         <p class="text-gray-400 text-xs uppercase tracking-wider mb-1">${cat.category}</p>
                         <h3 class="text-2xl font-bold text-white">${formatCurrency(cat.remaining)} <span class="text-xs font-normal text-gray-500">rimanenti</span></h3>
                    </div>
                     <div class="radial-progress text-xs ${getHealthColorText(cat.health)}" style="--value:${cat.percentage}; --size:3rem;">${cat.percentage}%</div>
                </div>
                
                <div class="w-full bg-gray-800 rounded-full h-1.5 overflow-hidden">
                    <div class="h-full ${getHealthColorBg(cat.health)} transition-all duration-1000" style="width: ${cat.percentage}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-500">
                    <span>Spesi: ${formatCurrency(cat.spent)}</span>
                    <span>Limit: ${formatCurrency(cat.limit)}</span>
                </div>
            </div>
        `).join('');
    }

    // Helpers
    window.saveBudget = async (category, amount) => {
        try {
            const formData = new FormData();
            formData.append('category', category);
            formData.append('limit', amount);

            // Optimistic Update can be done here, but reloading ensures sync

            await fetch(api.saveBudget, {
                method: 'POST',
                body: formData
            });

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                background: '#1f2937',
                color: '#fff'
            });
            Toast.fire({ icon: 'success', title: 'Budget aggiornato' });

            loadData(); // Reload to sync totals
        } catch (e) {
            console.error(e);
        }
    };

    function getCategoryIcon(cat) {
        const map = {
            'Food & Dining': 'coffee', 'Transportation': 'truck', 'Shopping': 'shopping-bag',
            'Entertainment': 'film', 'Bills & Utilities': 'zap', 'Healthcare': 'heart',
            'Travel': 'map', 'Education': 'book', 'Other': 'box'
        };
        return map[cat] || 'circle';
    }

    function getCategoryColorBg(cat) {
        // Tailwind classes
        return 'bg-gray-800';
    }

    function getCategoryColorText(cat) {
        return 'text-emerald-400';
    }

    function getHealthColorText(health) {
        if (health === 'critical') return 'text-red-500';
        if (health === 'warning') return 'text-yellow-500';
        return 'text-emerald-500';
    }

    function getHealthColorBg(health) {
        if (health === 'critical') return 'bg-red-500';
        if (health === 'warning') return 'bg-yellow-500';
        return 'bg-emerald-500';
    }

    init();
});
