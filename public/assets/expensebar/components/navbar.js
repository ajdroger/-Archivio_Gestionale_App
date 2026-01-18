
class ExpenseNavbar extends HTMLElement {
    constructor() {
        super();
        this.isDark = document.documentElement.classList.contains('dark');
    }

    connectedCallback() {
        this.attachShadow({ mode: 'open' });
        this.currentPage = this.getCurrentPage();
        this.render();
        this.setupEventListeners();
    }

    getCurrentPage() {
        const path = window.location.pathname;
        if (path.includes('analytics')) return 'analytics';
        return 'dashboard';
    }

    render() {
        const isDashboardActive = this.currentPage === 'dashboard';
        const isAnalyticsActive = this.currentPage === 'analytics';

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                }
                
                nav {
                    background: rgba(255, 255, 255, 0.8);
                    backdrop-filter: blur(10px);
                    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                    transition: all 0.3s ease;
                    position: sticky;
                    top: 0;
                    z-index: 50;
                }
                
                .dark nav {
                    background: rgba(3, 7, 18, 0.8);
                    border-bottom: 1px solid rgba(75, 85, 99, 0.3);
                }
                
                .container {
                    max-width: 1280px;
                    margin: 0 auto;
                    padding: 0 1.5rem;
                }
                
                .nav-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    height: 4rem;
                }
                
                .logo {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    font-size: 1.25rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, #6366f1, #4f46e5);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    cursor: pointer;
                }
                
                .logo:hover {
                    opacity: 0.9;
                }
                
                .nav-links {
                    display: flex;
                    align-items: center;
                    gap: 1.5rem;
                }
                
                .nav-link {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    color: #6b7280;
                    text-decoration: none;
                    transition: all 0.2s ease;
                    font-weight: 500;
                    position: relative;
                }
                
                .dark .nav-link {
                    color: #9ca3af;
                }
                
                .nav-link:hover {
                    background: rgba(99, 102, 241, 0.1);
                    color: #6366f1;
                }
                
                .dark .nav-link:hover {
                    background: rgba(99, 102, 241, 0.2);
                    color: #818cf8;
                }
                
                .nav-link.active {
                    background: rgba(99, 102, 241, 0.15);
                    color: #6366f1;
                }
                
                .dark .nav-link.active {
                    background: rgba(99, 102, 241, 0.25);
                    color: #818cf8;
                }
                
                .nav-link.active::after {
                    content: '';
                    position: absolute;
                    bottom: -1px;
                    left: 1rem;
                    right: 1rem;
                    height: 2px;
                    background: linear-gradient(135deg, #6366f1, #4f46e5);
                    border-radius: 2px;
                }
                
                .theme-toggle {
                    background: rgba(99, 102, 241, 0.1);
                    border: none;
                    border-radius: 0.5rem;
                    padding: 0.5rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .theme-toggle:hover {
                    background: rgba(99, 102, 241, 0.2);
                    transform: scale(1.05);
                }
                
                .sun-icon, .moon-icon {
                    width: 1.25rem;
                    height: 1.25rem;
                    color: #6366f1;
                }
                
                .dark .sun-icon {
                    display: block;
                }
                
                .dark .moon-icon {
                    display: none;
                }
                
                .sun-icon {
                    display: none;
                }
                
                .moon-icon {
                    display: block;
                }
                
                @media (max-width: 640px) {
                    .nav-link span {
                        display: none;
                    }
                    
                    .nav-links {
                        gap: 0.5rem;
                    }
                }
            </style>
            
            <nav>
                <div class="container">
                    <div class="nav-content">
                        <div class="logo" id="logo">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            ExpenseBar
                        </div>
                        <div class="nav-links">
                            <a href="index.html" class="nav-link ${isDashboardActive ? 'active' : ''}" data-page="dashboard">
                                <i data-feather="home" class="w-5 h-5"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="analytics.html" class="nav-link ${isAnalyticsActive ? 'active' : ''}" data-page="analytics">
                                <i data-feather="bar-chart-2" class="w-5 h-5"></i>
                                <span>Analytics</span>
                            </a>
                            <button class="theme-toggle" id="themeToggle" title="Toggle theme">
                                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="5"></circle>
                                    <line x1="12" y1="1" x2="12" y2="3"></line>
                                    <line x1="12" y1="21" x2="12" y2="23"></line>
                                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                    <line x1="1" y1="12" x2="3" y2="12"></line>
                                    <line x1="21" y1="12" x2="23" y2="12"></line>
                                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                </svg>
                                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
        `;
    }

    setupEventListeners() {
        const themeToggle = this.shadowRoot.getElementById('themeToggle');
        const logo = this.shadowRoot.getElementById('logo');
        
        // Theme toggle
        themeToggle.addEventListener('click', () => {
            this.isDark = !this.isDark;
            document.documentElement.classList.toggle('dark', this.isDark);
            
            // Save preference
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            
            // Dispatch custom event for script.js
            document.dispatchEvent(new CustomEvent('theme-toggle'));
            
            // Re-render to update icons
            this.render();
            this.setupEventListeners();
        });
        
        // Logo click - navigate home
        logo.addEventListener('click', () => {
            window.location.href = 'index.html';
        });
        
        // Smooth scroll for anchor links
        const anchorLinks = this.shadowRoot.querySelectorAll('a[href^="#"]');
        anchorLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Load saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light' && this.isDark) {
            document.documentElement.classList.remove('dark');
            this.isDark = false;
            this.render();
            this.setupEventListeners();
        }
    }
}

customElements.define('expense-navbar', ExpenseNavbar);
