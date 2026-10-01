/**
 * Shared fetch helper: JSON parsing, session expiry, and compact error dialogs.
 */
(function () {
    const dialog = () => document.getElementById('siteErrorDialog');

    function trapFocus(panel) {
        const focusable = panel.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) return () => {};
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        first.focus();
        function onKey(e) {
            if (e.key !== 'Tab') return;
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
        panel.addEventListener('keydown', onKey);
        return () => panel.removeEventListener('keydown', onKey);
    }

    function closeDialog(restoreFocus) {
        const el = dialog();
        if (!el) return;
        el.hidden = true;
        el.classList.remove('is-open');
        document.body.classList.remove('site-error-open');
        if (restoreFocus && el._restoreFocus) {
            el._restoreFocus.focus();
        }
        if (el._releaseTrap) el._releaseTrap();
    }

    function showErrorDialog(options) {
        const el = dialog();
        if (!el) {
            window.alert(options.message || 'Something went wrong.');
            return;
        }
        el._restoreFocus = document.activeElement;
        el.querySelector('[data-site-error-title]').textContent = options.title || 'Something went wrong';
        el.querySelector('[data-site-error-body]').textContent = options.message || 'Please try again.';
        const ref = el.querySelector('[data-site-error-ref]');
        if (ref) {
            ref.textContent = options.reference ? 'Reference: ' + options.reference : '';
            ref.hidden = !options.reference;
        }
        const primary = el.querySelector('[data-site-error-primary]');
        const secondary = el.querySelector('[data-site-error-secondary]');
        primary.textContent = options.primaryLabel || 'Close';
        primary.onclick = () => {
            closeDialog(true);
            if (typeof options.onPrimary === 'function') options.onPrimary();
        };
        if (options.secondaryLabel) {
            secondary.hidden = false;
            secondary.textContent = options.secondaryLabel;
            secondary.onclick = () => {
                closeDialog(true);
                if (typeof options.onSecondary === 'function') options.onSecondary();
            };
        } else {
            secondary.hidden = true;
        }
        el.hidden = false;
        el.classList.add('is-open');
        document.body.classList.add('site-error-open');
        el._releaseTrap = trapFocus(el.querySelector('.site-error-dialog__panel'));
        el.querySelector('.site-error-dialog__backdrop').onclick = () => closeDialog(true);
    }

    async function parseResponse(response) {
        const type = (response.headers.get('content-type') || '').toLowerCase();
        if (response.status === 401 || response.status === 419) {
            return { ok: false, code: 'session_expired', message: 'Your session expired. Sign in again to continue.' };
        }
        if (response.status === 403) {
            return { ok: false, code: 'forbidden', message: 'You do not have permission to do that.' };
        }
        if (response.status === 429) {
            const retry = response.headers.get('Retry-After');
            return { ok: false, code: 'rate_limit', message: retry ? 'Too many requests. Try again in ' + retry + ' seconds.' : 'Too many requests. Please wait and try again.' };
        }
        if (!type.includes('application/json')) {
            return { ok: false, code: 'invalid_response', message: 'The server returned an unexpected response.' };
        }
        const data = await response.json();
        if (!response.ok) {
            return {
                ok: false,
                code: data.code || 'request_failed',
                message: data.message || 'The request could not be completed.',
                reference: data.reference,
                errors: data.errors,
            };
        }
        return { ok: true, data };
    }

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: Object.assign({
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }, options.headers || {}),
        }, options));

        return parseResponse(response);
    }

    window.SiteRequest = {
        fetchJson,
        showErrorDialog,
        closeDialog,
        handleFailure(result, overrides = {}) {
            if (result.code === 'session_expired') {
                showErrorDialog(Object.assign({
                    title: 'Session expired',
                    message: result.message,
                    primaryLabel: 'Sign in',
                    onPrimary() { window.location.href = '/login'; },
                }, overrides));
                return;
            }
            showErrorDialog(Object.assign({
                title: 'Unable to complete request',
                message: result.message,
                reference: result.reference,
                primaryLabel: overrides.retry ? 'Try again' : 'Close',
                onPrimary: overrides.onRetry,
            }, overrides));
        },
    };
})();
