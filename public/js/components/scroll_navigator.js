/**
 * Professional Scroll Navigator Component
 * Inject a "Back to Top" button with circular progress indicator.
 * Usage: Automatically initializes when included.
 */
(function() {
    'use strict';

    // HTML Template for the component
    const SCROLL_NAV_HTML = `
        <div id="scrollNavigator" class="scroll-navigator-container">
            <div class="scroll-fab" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <svg class="progress-ring" width="64" height="64">
                    <circle class="progress-ring__circle-bg" stroke-width="2" fill="transparent" r="28" cx="32" cy="32"/>
                    <circle class="progress-ring__circle" stroke-width="2" fill="transparent" r="28" cx="32" cy="32"/>
                </svg>
                <i class="fa-solid fa-arrow-up scroll-icon"></i>
            </div>
        </div>
    `;

    // Inject CSS if not already present (failsafe, though we link it in HTML)
    // We assume CSS is loaded via <link> for better performance, but this is the JS logic.

    function initScrollNavigator() {
        if (document.getElementById('scrollNavigator')) return; // Already initialized

        // Inject HTML
        document.body.insertAdjacentHTML('beforeend', SCROLL_NAV_HTML);

        const container = document.getElementById('scrollNavigator');
        const circle = container.querySelector('.progress-ring__circle');
        
        // Progress Ring Configuration
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;

        circle.style.strokeDasharray = `${circumference} ${circumference}`;
        circle.style.strokeDashoffset = circumference;

        function setProgress(percent) {
            const offset = circumference - percent / 100 * circumference;
            circle.style.strokeDashoffset = offset;
        }

        // Scroll Handler
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            
            // Toggle Visibility (Show after 300px)
            if (scrollTop > 300) {
                container.classList.add('visible');
            } else {
                container.classList.remove('visible');
            }

            // Update Progress
            if (docHeight > 0) {
                const scrollPercent = (scrollTop / docHeight) * 100;
                setProgress(scrollPercent);
            }
        });
    }

    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollNavigator);
    } else {
        initScrollNavigator();
    }

})();
