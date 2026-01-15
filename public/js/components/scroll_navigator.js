/**
 * Professional Scroll Navigator Component
 * Inject a "Back to Top" button with circular progress indicator.
 * Usage: Automatically initializes when included.
 */
(function () {
    'use strict';

    // HTML Template for the component
    // HTML Template for the component - Stacked Vertical Navigation
    const SCROLL_NAV_HTML = `
        <div id="scrollNavigator" class="scroll-navigator-container">
            <!-- Top / Progress (Main) -->
            <div class="scroll-fab main-fab" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Torna su">
                <svg class="progress-ring" width="54" height="54">
                    <circle class="progress-ring__circle-bg" stroke-width="2" fill="transparent" r="24" cx="27" cy="27"/>
                    <circle class="progress-ring__circle" stroke-width="2" fill="transparent" r="24" cx="27" cy="27"/>
                </svg>
                <i class="fa-solid fa-arrow-up scroll-icon"></i>
            </div>
            
            <!-- Page Up -->
            <div class="scroll-fab mini-fab" onclick="window.scrollBy({top: -window.innerHeight * 0.8, behavior: 'smooth'})" title="Pagina Su">
                <i class="fa-solid fa-chevron-up text-white small"></i>
            </div>

            <!-- Page Down -->
            <div class="scroll-fab mini-fab" onclick="window.scrollBy({top: window.innerHeight * 0.8, behavior: 'smooth'})" title="Pagina Giu">
                <i class="fa-solid fa-chevron-down text-white small"></i>
            </div>

            <!-- Bottom -->
            <div class="scroll-fab mini-fab" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" title="Vai in fondo">
                <i class="fa-solid fa-arrow-down text-white small"></i>
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

            // Toggle Visibility (Show after 100px)
            if (scrollTop > 100) {
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
