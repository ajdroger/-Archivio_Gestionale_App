class ConfirmModal extends HTMLElement {
    constructor() {
        super();
        this.resolve = null;
    }

    connectedCallback() {
        this.attachShadow({ mode: 'open' });
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 1000;
                }
                
                .modal-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    backdrop-filter: blur(5px);
                    animation: fadeIn 0.3s ease;
                }
                
                .modal-content {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) scale(0.9);
                    background: rgba(30, 30, 46, 0.9);
                    backdrop-filter: blur(20px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    border-radius: 2rem;
                    padding: 2.5rem;
                    max-width: 450px;
                    width: 90%;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
                    animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
                }
                
                .modal-header {
                    text-align: center;
                    margin-bottom: 1.5rem;
                }
                
                .modal-icon {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 1rem;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .modal-icon.danger {
                    background: linear-gradient(45deg, #ef4444, #f87171);
                }
                
                .modal-icon.warning {
                    background: linear-gradient(45deg, #f59e0b, #fbbf24);
                }
                
                .modal-icon.info {
                    background: linear-gradient(45deg, #3b82f6, #60a5fa);
                }
                
                .modal-icon svg {
                    width: 40px;
                    height: 40px;
                    color: white;
                }
                
                .modal-title {
                    font-size: 1.75rem;
                    font-weight: bold;
                    color: white;
                    margin-bottom: 0.5rem;
                }
                
                .modal-message {
                    font-size: 1.1rem;
                    color: rgba(255, 255, 255, 0.8);
                    line-height: 1.6;
                }
                
                .modal-buttons {
                    display: flex;
                    gap: 1rem;
                    margin-top: 2rem;
                }
                
                .btn {
                    flex: 1;
                    padding: 1rem 1.5rem;
                    border: none;
                    border-radius: 1rem;
                    font-size: 1rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }
                
                .btn::before {
                    content: '';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    width: 0;
                    height: 0;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 50%;
                    transform: translate(-50%, -50%);
                    transition: width 0.6s, height 0.6s;
                }
                
                .btn:active::before {
                    width: 300px;
                    height: 300px;
                }
                
                .btn-confirm {
                    background: linear-gradient(45deg, #ef4444, #f87171);
                    color: white;
                }
                
                .btn-confirm:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
                }
                
                .btn-cancel {
                    background: rgba(255, 255, 255, 0.1);
                    color: white;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .btn-cancel:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: translateY(-2px);
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideIn {
                    to {
                        transform: translate(-50%, -50%) scale(1);
                    }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                
                @keyframes slideOut {
                    to {
                        transform: translate(-50%, -50%) scale(0.9);
                        opacity: 0;
                    }
                }
            </style>
            
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon" id="modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h2 class="modal-title" id="modal-title">Conferma Azione</h2>
                    <p class="modal-message" id="modal-message">Sei sicuro di voler procedere?</p>
                </div>
                
                <div class="modal-buttons">
                    <button class="btn btn-confirm" id="confirm-btn">Conferma</button>
                    <button class="btn btn-cancel" id="cancel-btn">Annulla</button>
                </div>
            </div>
        `;

        this.overlay = this.shadowRoot.querySelector('.modal-overlay');
        this.content = this.shadowRoot.querySelector('.modal-content');
        this.iconWrapper = this.shadowRoot.getElementById('modal-icon');
        this.titleEl = this.shadowRoot.getElementById('modal-title');
        this.messageEl = this.shadowRoot.getElementById('modal-message');
        this.confirmBtn = this.shadowRoot.getElementById('confirm-btn');
        this.cancelBtn = this.shadowRoot.getElementById('cancel-btn');

        this.bindEvents();
    }

    bindEvents() {
        this.overlay.addEventListener('click', () => this.hide(false));
        this.cancelBtn.addEventListener('click', () => this.hide(false));
        this.confirmBtn.addEventListener('click', () => this.hide(true));
        
        document.addEventListener('keydown', (e) => {
            if (this.style.display !== 'none') {
                if (e.key === 'Escape') this.hide(false);
                if (e.key === 'Enter') this.hide(true);
            }
        });
    }

    show(title, message, type = 'danger', callback) {
        this.style.display = 'block';
        this.callback = callback;
        
        this.iconWrapper.className = `modal-icon ${type}`;
        
        const icons = {
            danger: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>`,
            warning: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
            info: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
        };
        
        this.iconWrapper.innerHTML = icons[type];
        this.titleEl.textContent = title;
        this.messageEl.textContent = message;
    }

    hide(confirmed) {
        if (confirmed && this.callback) {
            this.callback();
        }
        
        this.style.display = 'none';
        this.callback = null;
    }
}

customElements.define('confirm-modal', ConfirmModal);