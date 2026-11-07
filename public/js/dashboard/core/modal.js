const stack = [];

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const focusableElements = (modal) => Array.from(modal.querySelectorAll(focusableSelector))
    .filter((element) => element.offsetParent !== null);

const syncBody = () => {
    document.body.classList.toggle('dash-modal-open', stack.length > 0);
};

const top = () => stack[stack.length - 1] ?? null;

const closeTop = () => {
    const current = top();

    if (current) {
        closeModal(current.id);
    }
};

const handleKeydown = (event) => {
    const current = top();

    if (!current) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        closeTop();

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusable = focusableElements(current.element);

    if (focusable.length === 0) {
        event.preventDefault();

        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

export const openModal = (id) => {
    const modal = document.getElementById(id);

    if (!modal || !modal.hidden) {
        return;
    }

    const previousFocus = document.activeElement;
    modal.hidden = false;

    stack.push({
        id,
        element: modal,
        previousFocus: previousFocus instanceof HTMLElement ? previousFocus : null,
    });

    syncBody();

    const focusable = focusableElements(modal);
    (focusable[0] ?? modal.querySelector('.dash-modal-panel'))?.focus();

    if (stack.length === 1) {
        document.addEventListener('keydown', handleKeydown);
    }
};

export const closeModal = (id) => {
    const index = stack.findIndex((entry) => entry.id === id);

    if (index === -1) {
        const modal = document.getElementById(id);

        if (modal) {
            modal.hidden = true;
        }

        return;
    }

    const [entry] = stack.splice(index, 1);
    entry.element.hidden = true;

    if (entry.previousFocus instanceof HTMLElement && document.body.contains(entry.previousFocus)) {
        entry.previousFocus.focus();
    }

    syncBody();

    if (stack.length === 0) {
        document.removeEventListener('keydown', handleKeydown);
    }
};

export const confirmAction = ({
    title = 'Confirm action',
    message,
    confirmLabel = 'Confirm',
    tone = 'danger',
}) => new Promise((resolve) => {
    const modal = document.getElementById('confirmModal');
    const messageElement = document.getElementById('confirmModalMessage');
    const acceptButton = modal?.querySelector('[data-confirm-accept]');
    const cancelButton = modal?.querySelector('[data-confirm-cancel]');

    if (!modal || !messageElement || !acceptButton || !cancelButton) {
        resolve(window.confirm(message));

        return;
    }

    const close = (result) => {
        closeModal('confirmModal');
        acceptButton.removeEventListener('click', onAccept);
        cancelButton.removeEventListener('click', onCancel);
        modal.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.removeEventListener('click', onCancel);
        });
        resolve(result);
    };

    const onAccept = () => close(true);
    const onCancel = () => close(false);

    messageElement.textContent = message;
    acceptButton.textContent = confirmLabel;
    acceptButton.classList.toggle('dash-button-danger', tone === 'danger');
    acceptButton.classList.toggle('dash-button-primary', tone !== 'danger');
    modal.querySelector('h2').textContent = title;

    acceptButton.addEventListener('click', onAccept);
    cancelButton.addEventListener('click', onCancel);
    modal.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', onCancel);
    });

    openModal('confirmModal');
    cancelButton.focus();
});
