/**
 * JubahPanda landing page behaviour.
 *
 * Deliberately small: the language switch is server-side, and the FAQ uses
 * native <details>, so this file only handles the mobile menu, the sticky
 * header shadow, and the scroll reveal.
 */

/* Mobile navigation ------------------------------------------------------- */

const toggle = document.querySelector('[data-menu-toggle]');
const panel = document.querySelector('[data-menu-panel]');

if (toggle && panel) {
    const setMenu = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('overflow-hidden', open);
    };

    toggle.addEventListener('click', () => {
        setMenu(panel.hidden);
    });

    // Close after tapping a link, so the anchor jump is actually visible.
    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setMenu(false);
            toggle.focus();
        }
    });
}

/* Sticky header shadow ---------------------------------------------------- */

const header = document.querySelector('[data-header]');

if (header) {
    const updateHeader = () => {
        header.classList.toggle('shadow-lg', window.scrollY > 8);
        header.classList.toggle('shadow-brand-900/5', window.scrollY > 8);
        header.classList.toggle('bg-white/80', window.scrollY > 8);
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });
}

/* Scroll reveal ----------------------------------------------------------- */

const revealables = document.querySelectorAll('.reveal');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

if (revealables.length && !prefersReducedMotion.matches && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.1 }
    );

    revealables.forEach((el) => observer.observe(el));
} else {
    revealables.forEach((el) => el.classList.add('is-visible'));
}

/* Registration form -------------------------------------------------------- */

// Land keyboard and screen-reader users on the error summary after a failed submit.
document.querySelector('[data-error-summary]')?.focus();

// Cash-on-delivery address: shown only for COD. Without JS it stays visible.
const addressBlock = document.querySelector('[data-delivery-address]');
const deliveryRadios = document.querySelectorAll('[data-delivery-radio]');

if (addressBlock && deliveryRadios.length) {
    const syncAddress = () => {
        const cod = document.querySelector('[data-delivery-radio][value="cod"]');
        addressBlock.hidden = !(cod && cod.checked);
    };

    deliveryRadios.forEach((radio) => radio.addEventListener('change', syncAddress));
    syncAddress();
}

// Stop double submits. The partial unique index is the real guard; this just avoids a
// confusing duplicate-matric error after an impatient second tap.
document.querySelectorAll('form[data-submit-once]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
        });
    });
});

// Coming back with the browser Back button restores the page from cache with the button
// still disabled.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll('form[data-submit-once] button[type="submit"]').forEach((button) => {
            button.disabled = false;
        });
    }
});
