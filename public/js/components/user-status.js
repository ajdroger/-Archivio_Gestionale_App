class UserStatusBar extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
    }

    connectedCallback() {
        this.role = this.getAttribute('role') || 'GUEST';
        this.user = this.getAttribute('user') || 'Ospite';
        this.render();
        this.startClock();
    }

    startClock() {
        const updateTime = () => {
            const now = new Date();
            const dateStr = now.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const timeStr = now.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const timeEl = this.shadowRoot.getElementById('live-time');
            if (timeEl) {
                timeEl.textContent = `${dateStr} ${timeStr}`;
            }
        };

        updateTime(); // Initial call
        setInterval(updateTime, 1000);
    }

    render() {
        const roleColor = this.role.toUpperCase() === 'ADMIN' ? '#ef4444' : '#3b82f6'; // Red for Admin, Blue for others

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: inline-block;
                    font-family: 'Inter', system-ui, -apple-system, sans-serif;
                }
                .status-container {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 6px 12px;
                    background: rgba(0, 0, 0, 0.4);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 9999px;
                    font-size: 0.75rem;
                    color: white;
                    backdrop-filter: blur(4px);
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                }
                
                .role-badge {
                    background-color: ${roleColor}20; /* 20% opacity */
                    color: ${roleColor};
                    border: 1px solid ${roleColor}60;
                    padding: 2px 8px;
                    border-radius: 4px;
                    font-weight: 800;
                    letter-spacing: 0.05em;
                    text-transform: uppercase;
                    font-size: 0.7rem;
                }
                
                .user-info {
                    color: #d1d5db;
                    font-weight: 500;
                }
                
                .divider {
                    width: 1px;
                    height: 12px;
                    background-color: rgba(255, 255, 255, 0.2);
                }
                
                .live-time {
                    font-feature-settings: "tnum";
                    font-variant-numeric: tabular-nums;
                    color: #9ca3af;
                    font-family: monospace;
                    letter-spacing: -0.02em;
                }
                
                .status-indicator {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    color: #10b981; /* Emerald 500 */
                    font-weight: 600;
                    font-size: 0.7rem;
                    letter-spacing: 0.05em;
                }
                
                .status-dot {
                    width: 6px;
                    height: 6px;
                    background-color: #10b981;
                    border-radius: 50%;
                    box-shadow: 0 0 8px #10b981;
                    animation: pulse 2s infinite;
                }
                
                @keyframes pulse {
                    0% { opacity: 1; transform: scale(1); }
                    50% { opacity: 0.5; transform: scale(1.2); }
                    100% { opacity: 1; transform: scale(1); }
                }
            </style>
            
            <div class="status-container">
                <span class="role-badge">${this.role.toUpperCase()}</span>
                <span class="user-info">${this.user}</span>
                <div class="divider"></div>
                <span id="live-time" class="live-time">--/--/---- --:--:--</span>
                <div class="divider"></div>
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    OPERATIVE
                </div>
            </div>
        `;
    }
}

customElements.define('user-status-bar', UserStatusBar);
