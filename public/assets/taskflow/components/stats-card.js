class StatsCard extends HTMLElement {
    constructor() {
        super();
        this.count = 0;
        this.targetCount = 0;
        this.animationDuration = 1000;
        this.animationStarted = false;
    }

    connectedCallback() {
        this.attachShadow({ mode: 'open' });
        this.render();
        this.observe();
    }

    render() {
        const icon = this.getAttribute('icon') || 'activity';
        const color = this.getAttribute('color') || 'violet';
        const label = this.getAttribute('label') || 'Stat';
        const colors = {
            violet: 'from-violet-500 to-fuchsia-500',
            emerald: 'from-emerald-500 to-teal-500',
            amber: 'from-amber-500 to-orange-500',
            red: 'from-red-500 to-rose-500'
        };
        
        const gradient = colors[color] || colors.violet;

        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                }
                
                .stats-card {
                    background: rgba(255, 255, 255, 0.05);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 1.5rem;
                    padding: 1.5rem;
                    text-align: center;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }
                
                .stats-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
                    transition: left 0.5s;
                }
                
                .stats-card:hover::before {
                    left: 100%;
                }
                
                .stats-card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                }
                
                .icon-wrapper {
                    width: 60px;
                    height: 60px;
                    margin: 0 auto 1rem;
                    background: linear-gradient(45deg, ${gradient});
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
                }
                
                .icon-wrapper svg {
                    width: 28px;
                    height: 28px;
                    color: white;
                }
                
                .count {
                    font-size: 2.5rem;
                    font-weight: bold;
                    color: white;
                    margin: 0.5rem 0;
                    line-height: 1;
                }
                
                .label {
                    font-size: 1rem;
                    color: rgba(255, 255, 255, 0.7);
                    font-weight: 500;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .stats-card {
                    animation: fadeInUp 0.6s ease forwards;
                }
            </style>
            
            <div class="stats-card">
                <div class="icon-wrapper">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="${this.getIconPath(icon)}"></path>
                    </svg>
                </div>
                <div class="count">${this.count}</div>
                <div class="label">${label}</div>
            </div>
        `;
    }

    getIconPath(iconName) {
        const icons = {
            target: 'M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8.009 8.009 0 0 1-8 8Zm3-8a3 3 0 1 1-3-3 3 3 0 0 1 3 3Z',
            'check-circle': 'M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3',
            clock: 'M12 22a10 10 0 1 1 10-10 10.011 10.011 0 0 1-10 10Zm1-10V6h-2v8h6v-2Z',
            activity: 'M22 12h-4l-3 9L9 3l-3 9H2'
        };
        return icons[iconName] || icons.activity;
    }

    observe() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.animationStarted) {
                    this.animationStarted = true;
                    this.animateCount();
                }
            });
        });
        observer.observe(this);
    }

    animateCount() {
        const target = parseInt(this.getAttribute('count')) || 0;
        this.targetCount = target;
        
        const startTime = performance.now();
        const startCount = this.count;

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / this.animationDuration, 1);
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            
            this.count = Math.floor(startCount + (target - startCount) * easeOutQuart);
            this.shadowRoot.querySelector('.count').textContent = this.count;

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                this.count = target;
                this.shadowRoot.querySelector('.count').textContent = this.count;
            }
        };
        
        requestAnimationFrame(animate);
    }

    static get observedAttributes() {
        return ['count'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === 'count' && oldValue !== newValue) {
            this.animateCount();
        }
    }
}

customElements.define('stats-card', StatsCard);