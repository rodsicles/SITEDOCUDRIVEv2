/**
 * Prevents duplicate submissions and refresh spam (429/425-style client issues).
 */
export function createRequestGuard(options = {}) {
    const cooldownMs = options.cooldownMs ?? 2000;
    const maxRetries = options.maxRetries ?? 2;
    const retryDelayMs = options.retryDelayMs ?? 1500;

    let inFlight = false;
    let lastCompletedAt = 0;
    const pendingKeys = new Set();

    function canProceed(key = 'default') {
        if (inFlight) {
            return false;
        }
        if (pendingKeys.has(key)) {
            return false;
        }
        if (Date.now() - lastCompletedAt < cooldownMs) {
            return false;
        }

        return true;
    }

    function lock(key = 'default') {
        inFlight = true;
        pendingKeys.add(key);
    }

    function unlock(key = 'default') {
        inFlight = false;
        pendingKeys.delete(key);
        lastCompletedAt = Date.now();
    }

    async function guardedFetch(url, init = {}, key = url) {
        if (!canProceed(key)) {
            return { skipped: true, response: null };
        }

        lock(key);
        let attempt = 0;

        try {
            while (attempt <= maxRetries) {
                try {
                    const response = await fetch(url, {
                        ...init,
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(init.headers || {}),
                        },
                        credentials: init.credentials ?? 'same-origin',
                    });

                    if (response.status === 429 || response.status === 425) {
                        if (attempt < maxRetries) {
                            await sleep(retryDelayMs * (attempt + 1));
                            attempt++;
                            continue;
                        }
                    }

                    return { skipped: false, response };
                } catch (err) {
                    if (attempt < maxRetries) {
                        await sleep(retryDelayMs * (attempt + 1));
                        attempt++;
                        continue;
                    }
                    throw err;
                }
            }
        } finally {
            unlock(key);
        }

        return { skipped: false, response: null };
    }

    function guardForm(form, key = form.action || 'form') {
        if (!form || form.dataset.guardBound === '1') {
            return;
        }
        form.dataset.guardBound = '1';

        form.addEventListener('submit', function (e) {
            if (!canProceed(key)) {
                e.preventDefault();
                return;
            }
            lock(key);
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                if (!btn.dataset.originalHtml) {
                    btn.dataset.originalHtml = btn.innerHTML;
                }
                btn.innerHTML = '<span class="loading"></span> Processing...';
            }
        });
    }

    function guardButton(button, onClick, key = 'button') {
        if (!button || button.dataset.guardBound === '1') {
            return;
        }
        button.dataset.guardBound = '1';
        button.addEventListener('click', async function (e) {
            e.preventDefault();
            if (!canProceed(key)) {
                return;
            }
            lock(key);
            button.disabled = true;
            try {
                await onClick(e);
            } finally {
                unlock(key);
                button.disabled = false;
            }
        });
    }

    return { canProceed, lock, unlock, guardedFetch, guardForm, guardButton };
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function readCooldownMs() {
    const meta = document.querySelector('meta[name="request-guard-cooldown-ms"]');
    if (!meta) {
        return 2500;
    }
    const value = parseInt(meta.getAttribute('content') || '2500', 10);
    return Number.isFinite(value) && value >= 0 ? value : 2500;
}

const defaultGuard = createRequestGuard({ cooldownMs: readCooldownMs() });

if (typeof window !== 'undefined') {
    window.requestGuard = defaultGuard;
    window.createRequestGuard = createRequestGuard;

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[data-request-guard]').forEach((form) => {
            defaultGuard.guardForm(form);
        });
    });
}
