class TaskFlowNav extends HTMLElement {
    connectedCallback() {
        this.attachShadow({ mode: 'open' });
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    position: sticky;
                    top: 0;
                    z-index: 50;
                }
                
                nav {
                    background: rgba(255, 255, 255, 0.08);
                    backdrop-filter: blur(20px);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                    padding: 1rem 2rem;
                }
                
                .nav-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .logo {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    font-size: 1.5rem;
                    font-weight: bold;
                    color: white;
                    text-decoration: none;
                }
                
                .logo svg {
                    width: 32px;
                    height: 32px;
                    color: #a78bfa;
                }
                
                .nav-links {
                    display: flex;
                    gap: 2rem;
                    align-items: center;
                }
                
                .nav-link {
                    color: rgba(255, 255, 255, 0.8);
                    text-decoration: none;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                
                .nav-link:hover {
                    color: white;
                    transform: translateY(-2px);
                }
                
                .nav-link svg {
                    width: 20px;
                    height: 20px;
                }
                
                @media (max-width: 640px) {
                    nav {
                        padding: 1rem;
                    }
                    
                    .nav-links {
                        gap: 1rem;
                    }
                    
                    .nav-link span {
                        display: none;
                    }
                }
            </style>
            
            <nav>
                <div class="nav-container">
                    <a href="/" class="logo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                        </svg>
                        <span>TaskFlow Pro</span>
                    </a>
                    
                    <div class="nav-links">
                        <a href="#tasks-section" class="nav-link">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                            </svg>
                            <span>Attività</span>
                        </a>
                        
                        <a href="about.html" class="nav-link">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>Info</span>
                        </a>
                    </div>
                </div>
            </nav>
        `;
    }
}

customElements.define('taskflow-nav', TaskFlowNav);