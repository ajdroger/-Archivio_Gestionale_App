/**
 * Cookie Banner Logic
 * Location: public/js/components/cookie_banner.js
 * 
 * Handles GDPR consent storage and banner visibility.
 */

document.addEventListener("DOMContentLoaded", function () {
    if (!localStorage.getItem('cookieConsented')) {
        const banner = document.getElementById('cookie-banner');
        if (banner) banner.style.display = 'block';
    }

    const acceptBtn = document.getElementById('accept-cookies');
    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            localStorage.setItem('cookieConsented', 'true');
            const banner = document.getElementById('cookie-banner');
            if (banner) banner.style.display = 'none';
        });
    }
});
