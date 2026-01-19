class ExpenseFooter extends HTMLElement {
    connectedCallback() {
        this.attachShadow({ mode: 'open' });
        this.render();
    }

    render() {
        const year = new Date().getFullYear();

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    margin-top: 4rem;
                }
                
                footer {
                    background: rgba(255, 255, 255, 0.8);
                    backdrop-filter: blur(10px);
                    border-top: 1px solid rgba(0, 0, 0, 0.1);
                    padding: 2rem 0;
                }
                
                .dark footer {
                    background: rgba(3, 7, 18, 0.8);
                    border-top: 1px solid rgba(75, 85, 99, 0.3);
                }
                
                .container {
                    max-width: 1280px;
                    margin: 0 auto;
                    padding: 0 1.5rem;
                }
                
                .footer-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 1rem;
                }
                
                .footer-text {
                    color: #6b7280;
                    font-size: 0.875rem;
                }
                
                .dark .footer-text {
                    color: #9ca3af;
                }
                
                .footer-links {
                    display: flex;
                    gap: 1.5rem;
                }
                
                .footer-link {
                    color: #6b7280;
                    text-decoration: none;
                    font-size: 0.875rem;
                    transition: color 0.2s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.25rem;
                }
                
                .dark .footer-link {
                    color: #9ca3af;
                }
                
                .footer-link:hover {
                    color: #6366f1;
                }
                
                .export-btn {
                    background: linear-gradient(135deg, #6366f1, #4f46e5);
                    color: white;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    transition: all 0.2s ease;
                    border: none;
                    cursor: pointer;
                }
                
                .export-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
                }
                
                @media (max-width: 640px) {
                    .footer-content {
                        flex-direction: column;
                        text-align: center;
                    }
                    
                    .footer-links {
                        flex-direction: column;
                        gap: 0.75rem;
                    }
                }
            </style>
            
            <footer>
                <div class="container">
                    <div class="footer-content">
                        <div class="footer-text">
                            © ${year} ExpenseBar Dashboard. Built with Chart.js & TailwindCSS.
                        </div>
                        <div class="footer-links">
                            <a href="${window.MCAG_BASE_URL || ''}/api/docs" class="footer-link">
                                <i data-feather="file-text" class="w-4 h-4"></i>
                                API Docs
                            </a>
                            <a href="${window.MCAG_BASE_URL || ''}/taskflow" class="footer-link">
                                <i data-feather="check-square" class="w-4 h-4"></i>
                                Taskflow
                            </a>
                            <button class="export-btn" onclick="alert('Export not configured in this demo.')">
                                <i data-feather="download" class="w-4 h-4"></i>
                                Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </footer>
        `;
    }
}

customElements.define('expense-footer', ExpenseFooter);