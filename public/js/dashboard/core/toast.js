const icons = {
    success: '<path d="M20 6L9 17l-5-5" />',
    error: '<path d="M6 6l12 12M18 6L6 18" />',
    warning: '<path d="M12 3l10 18H2zM12 9v5M12 18h.01" />',
    info: '<circle cx="12" cy="12" r="9" /><path d="M12 11v5M12 8h.01" />',
};

let toastContainer = null;

const container = () => {
    if (!toastContainer) {
        toastContainer = document.getElementById('toastContainer');
    }

    return toastContainer;
};

const dismiss = (element) => {
    if (!element || element.classList.contains('leaving')) {
        return;
    }

    element.classList.add('leaving');

    window.setTimeout(() => {
        element.remove();
    }, 180);
};

export const toast = (message, type = 'success') => {
    const host = container();

    if (!host) {
        return;
    }

    const tone = icons[type] ? type : 'info';

    while (host.children.length >= 4) {
        host.firstElementChild?.remove();
    }

    const element = document.createElement('div');
    element.className = `dash-toast dash-toast-${tone}`;
    element.setAttribute('role', tone === 'error' ? 'alert' : 'status');
    element.innerHTML = `
        <svg class="dash-toast-icon" viewBox="0 0 24 24" aria-hidden="true">${icons[tone]}</svg>
        <span class="dash-toast-message"></span>
        <button class="dash-toast-close" type="button" aria-label="Dismiss notification">&times;</button>
        <span class="dash-toast-progress" aria-hidden="true"></span>
    `;
    element.querySelector('.dash-toast-message').textContent = message;
    element.querySelector('.dash-toast-close').addEventListener('click', () => dismiss(element));

    host.appendChild(element);

    window.setTimeout(() => dismiss(element), 3600);
};
