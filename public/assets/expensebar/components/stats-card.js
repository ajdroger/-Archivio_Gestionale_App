class ExpenseStatsCard extends HTMLElement {
    static get observedAttributes() {
        return ['title', 'value', 'icon', 'color'];
    }

    connectedCallback() {
        this.attachShadow({ mode: 'open' });
        this.render();
    }

    attributeChangedCallback() {
        this.render();
    }

    getColorClass(color) {
        const colors = {
            primary: { bg: 'primary-100 dark:bg-primary-900/30', text: 'text-primary-600 dark:text-primary-400' },
            green: { bg: 'green-100 dark:bg-green-900/30', text: 'text-green-600 dark:text-green-400' },
            purple: { bg: 'purple-100 dark:bg-purple-900/30', text: 'text-purple-600 dark:text-purple-400' },
            red: { bg: 'red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400' },
            yellow: { bg: 'yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-600 dark:text-yellow-400' }
        };
        return colors[color] || colors.primary;
    }

    render() {
        const title = this.getAttribute('title') || 'Stat';
        const value = this.getAttribute('value') || '0';
        const icon = this.getAttribute('icon') || 'dollar-sign';
        const color = this.getAttribute('color') || 'primary';
        const colorClass = this.getColorClass(color);

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                }
                
                .card {
                    background: rgba(255, 255, 255, 0.8);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(0, 0, 0, 0.1);
                    border-radius: 1rem;
                    padding: 1.5rem;
                    transition: all 0.3s ease;
                }
                
                .dark .card {
                    background: rgba(31, 41, 55, 0.5);
                    border: 1px solid rgba(75, 85, 99, 0.3);
                }
                
                .card:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                }
                
                .card-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 0.75rem;
                }
                
                .card-title {
                    font-size: 0.875rem;
                    color: #6b7280;
                    font-weight: 500;
                }
                
                .dark .card-title {
                    color: #9ca3af;
                }
                
                .card-icon {
                    width: 2.5rem;
                    height: 2.5rem;
                    border-radius: 0.5rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .card-value {
                    font-size: 1.875rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, #6366f1, #4f46e5);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                
                .dark .card-value {
                    background: linear-gradient(135deg, #818cf8, #6366f1);
                    -webkit-background-clip: text;
                    background-clip: text;
                }
                
                .change-indicator {
                    display: flex;
                    align-items: center;
                    gap: 0.25rem;
                    font-size: 0.75rem;
                    margin-top: 0.5rem;
                }
                
                .change-positive {
                    color: #10b981;
                }
                
                .change-negative {
                    color: #ef4444;
                }
            </style>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">${title}</h3>
                    <div class="card-icon ${colorClass.bg} ${colorClass.text}">
                        <i data-feather="${icon}" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="card-value">${value}</div>
                <div class="change-indicator change-positive">
                    <i data-feather="trending-up" class="w-3 h-3"></i>
                    <span>+12.5%</span>
                </div>
            </div>
        `;
    }
}

customElements.define('expense-stats-card', ExpenseStatsCard);