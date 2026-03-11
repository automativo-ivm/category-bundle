import * as React from 'react';
import * as ReactDOM from 'react-dom';
import PropertyForm from './property-form';

const CONTAINER_ID = 'flagbit_category_properties';
const CATEGORY_EDIT_URL_PATTERN = /\/enrich\/product-category-tree\/(\d+)\/edit/;

let currentCategoryId: string | null = null;
let saveFn: (() => Promise<void>) | null = null;
let injectionAttempts = 0;
const MAX_INJECTION_ATTEMPTS = 50;

function getCategoryCodeFromPage(): string | null {
    // CE7 renders the code as a readonly TextInput
    const inputs = document.querySelectorAll<HTMLInputElement>('input[readonly]');
    for (let i = 0; i < inputs.length; i++) {
        const input = inputs[i];
        // The code field has name containing "code"
        if (input.name && input.name.toLowerCase().includes('code')) {
            return input.value;
        }
    }

    // Fallback: look for readonly input within a Field that has a "Code" label
    const fields = document.querySelectorAll('[class*="Field"]');
    for (let i = 0; i < fields.length; i++) {
        const field = fields[i];
        const label = field.querySelector('label');
        const input = field.querySelector<HTMLInputElement>('input[readonly]');
        if (label && input && input.value) {
            const labelText = label.textContent?.toLowerCase() || '';
            if (labelText.includes('code') || labelText.includes('codice') || labelText.includes('código')) {
                return input.value;
            }
        }
    }

    return null;
}

function findPropertiesTabContent(): HTMLElement | null {
    // Strategy: find the readonly code input and walk up the DOM to the FormContainer.
    // In CE7 production builds, styled-components class names are hashed (no "FormContainer"
    // in the class), so we can't rely on class-based selectors. Instead, we use the
    // structural layout: input → TextInputContainer → FieldContainer → FormContainer.
    // The FormContainer has 4+ direct children (SectionTitle, Field, SectionTitle, Fields...).
    const codeInput = document.querySelector<HTMLInputElement>('input[name="code"][readonly]');
    if (codeInput) {
        console.log('[Flagbit] Found code input:', codeInput.value);
        let el: HTMLElement | null = codeInput;
        for (let depth = 0; depth < 10 && el && el !== document.body; depth++) {
            el = el.parentElement;
            if (el && el.tagName === 'DIV' && el.children.length >= 4) {
                console.log('[Flagbit] Found FormContainer at depth', depth, 'with', el.children.length, 'children');
                return el;
            }
        }
        console.log('[Flagbit] Could not find FormContainer by walking up from code input');
    } else {
        console.log('[Flagbit] Code input not found (input[name="code"][readonly])');
    }

    // Fallback: class-based selectors (works in development builds where displayName is preserved)
    const formContainers = document.querySelectorAll('div[class*="FormContainer"], div[class*="formcontainer"]');
    if (formContainers.length > 0) {
        return formContainers[0] as HTMLElement;
    }

    return null;
}

function injectPropertyForm() {
    if (document.getElementById(CONTAINER_ID)) {
        return;
    }

    const formContainer = findPropertiesTabContent();
    if (!formContainer) {
        injectionAttempts++;
        if (injectionAttempts < MAX_INJECTION_ATTEMPTS) {
            setTimeout(() => injectPropertyForm(), 200);
        }
        return;
    }

    const categoryCode = getCategoryCodeFromPage();
    if (!categoryCode) {
        injectionAttempts++;
        if (injectionAttempts < MAX_INJECTION_ATTEMPTS) {
            setTimeout(() => injectPropertyForm(), 200);
        }
        return;
    }

    injectionAttempts = 0;

    const container = document.createElement('div');
    container.id = CONTAINER_ID;
    container.style.marginTop = '20px';
    formContainer.appendChild(container);

    const onSaveRef = (fn: () => Promise<void>) => {
        saveFn = fn;
    };

    ReactDOM.render(
        <PropertyForm categoryCode={categoryCode} onSaveRef={onSaveRef} />,
        container
    );
}

function cleanup() {
    const container = document.getElementById(CONTAINER_ID);
    if (container) {
        ReactDOM.unmountComponentAtNode(container);
        container.remove();
    }
    saveFn = null;
    currentCategoryId = null;
    injectionAttempts = 0;
}

function isOnCategoryEditPage(): RegExpMatchArray | null {
    const hash = window.location.hash || '';
    return hash.match(CATEGORY_EDIT_URL_PATTERN) ||
        window.location.href.match(CATEGORY_EDIT_URL_PATTERN);
}

function interceptSaveButton() {
    // Intercept clicks on the primary save button (level="primary")
    document.addEventListener('click', async (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        // Akeneo uses styled-components buttons with specific class patterns
        const button = target.closest('button');
        if (!button) {
            return;
        }

        if (!isOnCategoryEditPage()) {
            return;
        }

        // Check if this is a primary/save button - Akeneo's Button level="primary" renders blue
        const isPrimaryButton = button.getAttribute('level') === 'primary' ||
            button.classList.contains('AknButton--apply') ||
            (button.querySelector && button.closest('[class*="PageHeader-Actions"], [class*="Actions"]'));

        if (!isPrimaryButton) {
            return;
        }

        if (saveFn) {
            try {
                await saveFn();
            } catch (e) {
                console.error('Failed to save Flagbit category properties:', e);
            }
        }
    }, true);
}

function init() {
    console.log('[Flagbit] category-edit-injector init() called');
    interceptSaveButton();

    let lastHash = '';

    const checkPage = () => {
        const currentHash = window.location.hash || window.location.href;
        if (currentHash === lastHash) {
            return;
        }
        lastHash = currentHash;

        const match = isOnCategoryEditPage();
        if (match) {
            console.log('[Flagbit] Detected category edit page, hash:', currentHash);
            const categoryId = match[1];
            if (categoryId !== currentCategoryId) {
                cleanup();
                currentCategoryId = categoryId;
                injectionAttempts = 0;
            }
            setTimeout(() => injectPropertyForm(), 500);
        } else {
            if (currentCategoryId) {
                cleanup();
            }
        }
    };

    // Check periodically and on hash changes
    window.addEventListener('hashchange', checkPage);
    setInterval(checkPage, 1000);

    // Also observe DOM changes for SPA navigation
    const observer = new MutationObserver(() => {
        if (isOnCategoryEditPage() && !document.getElementById(CONTAINER_ID)) {
            setTimeout(() => injectPropertyForm(), 300);
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    // Initial check
    checkPage();
}

export default init;
