/**
 * Toast Notification System (Restored)
 * Provides a simple JS API to show global notifications.
 */

const Toast = {
    container: null,

    init() {
        if (!document.getElementById('toast-container-global')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container-global';
            this.container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container-global');
        }
    },

    show(message, type = 'info', title = 'Notifica') {
        this.init();

        const id = 'toast-' + Date.now();
        const iconMap = {
            'success': 'fa-check-circle text-success',
            'error': 'fa-circle-xmark text-danger',
            'warning': 'fa-triangle-exclamation text-warning',
            'info': 'fa-circle-info text-info'
        };
        const icon = iconMap[type] || iconMap.info;

        const html = `
            <div id="${id}" class="toast glass-toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fa-solid ${icon} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <small class="text-white-50">Adesso</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" onclick="Toast.remove('${id}')"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        // Create temporary element wrapper
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const toastEl = wrapper.firstElementChild;

        this.container.appendChild(toastEl);

        // Auto remove after 5s
        setTimeout(() => {
            this.remove(id);
        }, 5000);
    },

    remove(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('show');
            el.classList.add('hide');
            setTimeout(() => el.remove(), 500); // Wait for transition
        }
    }
};

// Expose global
window.Toast = Toast;
