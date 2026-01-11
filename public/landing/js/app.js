document.addEventListener('DOMContentLoaded', () => {
    // Initialize AOS
    AOS.init({ duration: 800, once: true });

    // Cookie Banner Logic
    const cookieBanner = document.getElementById('cookie-banner');
    if (cookieBanner) {
        setTimeout(() => {
            if (!localStorage.getItem('cookieAccepted')) {
                cookieBanner.style.display = 'block';
            }
        }, 1500);
    }

    // Cookie Accept Handlers
    window.acceptCookies = function () {
        localStorage.setItem('cookieAccepted', 'true');
        if (cookieBanner) cookieBanner.style.display = 'none';
    };

    window.closeCookieBanner = function () {
        if (cookieBanner) cookieBanner.style.display = 'none';
    };
});
