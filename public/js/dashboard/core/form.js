import { qs, qsa } from './dom.js?v=4';

const errorElement = (input) => {
    const field = input.closest('.dash-field');

    if (!field) {
        return null;
    }

    let element = field.querySelector('.dash-field-error');

    if (!element) {
        element = document.createElement('p');
        element.className = 'dash-field-error';
        field.appendChild(element);
    }

    return element;
};

export const clearFormErrors = (form) => {
    if (!form) {
        return;
    }

    qsa('.dash-field-error', form).forEach((element) => {
        element.textContent = '';
        element.hidden = true;
    });

    qsa('[aria-invalid="true"]', form).forEach((input) => {
        input.removeAttribute('aria-invalid');
    });
};

const baseField = (key) => key.split('.')[0];

export const applyFormErrors = (form, error) => {
    clearFormErrors(form);

    const errors = error?.payload?.errors;

    if (!errors || typeof errors !== 'object') {
        return false;
    }

    let applied = false;

    Object.entries(errors).forEach(([key, messages]) => {
        const field = baseField(key);
        const input = qs(`[name="${field}"]`, form) ?? qs(`#${field}`, form);

        if (!input) {
            return;
        }

        const element = errorElement(input);
        const message = Array.isArray(messages) ? messages[0] : messages;

        if (element) {
            element.textContent = message;
            element.hidden = false;
            applied = true;
        }

        input.setAttribute('aria-invalid', 'true');
    });

    return applied;
};

export const setButtonLoading = (button, loading, loadingLabel = null) => {
    if (!button) {
        return;
    }

    if (loading) {
        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = button.textContent.trim();
        }

        button.disabled = true;
        button.classList.add('is-loading');
        button.innerHTML = `<span class="dash-button-spinner" aria-hidden="true"></span><span>${loadingLabel ?? button.dataset.originalLabel}</span>`;

        return;
    }

    button.disabled = false;
    button.classList.remove('is-loading');

    if (button.dataset.originalLabel) {
        button.textContent = button.dataset.originalLabel;
        delete button.dataset.originalLabel;
    }
};
