(function () {
    const STORAGE_KEY = 'qrpay.offline.queue.v1';
    const MAX_RETRY = 3;
    const RETRYABLE_STATUSES = new Set([408, 425, 429, 500, 502, 503, 504]);
    let flushInProgress = false;
    let currentFlushPromise = null;

    function loadQueue() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return [];
            }

            const parsed = JSON.parse(raw);

            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed;
        } catch (error) {
            console.warn('[OfflineQueue] Failed to parse stored queue', error);
            localStorage.removeItem(STORAGE_KEY);
            return [];
        }
    }

    function saveQueue(queue, meta = {}) {
        if (!Array.isArray(queue) || queue.length === 0) {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
        }

        dispatchQueueEvent(queue, meta);

        if (queue.length > 0) {
            scheduleBackgroundSync();
        }
    }

    function dispatchQueueEvent(queue, meta = {}) {
        try {
            window.dispatchEvent(
                new CustomEvent('offline-queue:updated', {
                    detail: {
                        queue: Array.isArray(queue) ? queue.slice() : [],
                        meta,
                    },
                })
            );
        } catch (error) {
            console.error('[OfflineQueue] Failed to dispatch update event', error);
        }
    }

    function scheduleBackgroundSync() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        navigator.serviceWorker.ready
            .then((registration) => {
                if ('sync' in registration) {
                    return registration.sync.register('qrpay-offline-sync');
                }

                if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({
                        type: 'REGISTER_BACKGROUND_SYNC',
                    });
                }

                return null;
            })
            .catch(() => {
                // Background sync is optional, ignore errors silently.
            });
    }

    function serialiseData(data) {
        if (data === undefined || data === null || data === '') {
            return null;
        }

        if (typeof data === 'string') {
            return { type: 'string', value: data };
        }

        if (typeof URLSearchParams !== 'undefined' && data instanceof URLSearchParams) {
            return { type: 'string', value: data.toString() };
        }

        if (typeof data === 'number' || typeof data === 'boolean') {
            return { type: 'string', value: String(data) };
        }

        if (typeof data === 'object') {
            if (typeof FormData !== 'undefined' && data instanceof FormData) {
                return null;
            }

            if (typeof Blob !== 'undefined' && data instanceof Blob) {
                return null;
            }

            try {
                return { type: 'json', value: JSON.stringify(data) };
            } catch (error) {
                console.warn('[OfflineQueue] Unable to serialise payload', error);
                return null;
            }
        }

        return null;
    }

    function deserialiseData(serialised) {
        if (!serialised) {
            return undefined;
        }

        if (serialised.type === 'json') {
            try {
                return JSON.parse(serialised.value);
            } catch (error) {
                console.warn('[OfflineQueue] Unable to parse payload', error);
                return undefined;
            }
        }

        return serialised.value;
    }

    function cloneHeaders(headers) {
        if (!headers) {
            return {};
        }

        if (typeof headers.toJSON === 'function') {
            return headers.toJSON();
        }

        const plain = {};

        Object.keys(headers || {}).forEach((key) => {
            if (typeof headers[key] === 'undefined') {
                return;
            }

            plain[key] = headers[key];
        });

        return plain;
    }

    function createQueueEntry(config) {
        const serialised = serialiseData(config.data);

        if (config.data !== undefined && serialised === null) {
            return null;
        }

        const method = (config.method || 'post').toUpperCase();
        let absoluteUrl = config.url || '';

        try {
            absoluteUrl = new URL(config.url, config.baseURL || window.location.origin).toString();
        } catch (error) {
            // Keep original URL if resolution fails.
        }

        return {
            id: `${Date.now()}-${Math.random().toString(16).slice(2, 10)}`,
            method,
            url: absoluteUrl,
            data: serialised,
            headers: cloneHeaders(config.headers),
            withCredentials: Boolean(config.withCredentials),
            timeout: typeof config.timeout === 'number' ? config.timeout : undefined,
            createdAt: new Date().toISOString(),
            attempts: 0,
        };
    }

    function shouldQueueRequest(config) {
        if (navigator.onLine) {
            return false;
        }

        if (!config) {
            return false;
        }

        if (config.__skipOfflineQueue || (config.meta && config.meta.skipOfflineQueue)) {
            return false;
        }

        const headers = cloneHeaders(config.headers);

        if (headers['X-Skip-Offline-Queue']) {
            return false;
        }

        const method = (config.method || 'get').toLowerCase();

        if (method === 'get' || method === 'head' || method === 'options') {
            return false;
        }

        if (config.data !== undefined && serialiseData(config.data) === null) {
            return false;
        }

        return true;
    }

    async function sendQueuedRequest(entry) {
        const payload = deserialiseData(entry.data);
        const requestConfig = {
            method: entry.method,
            url: entry.url,
            data: payload,
            headers: Object.assign({}, entry.headers, { 'X-Queued-By': 'qrpay-offline-manager' }),
            withCredentials: entry.withCredentials,
            timeout: entry.timeout,
        };

        if (window.axios) {
            return window.axios(requestConfig);
        }

        const fetchConfig = {
            method: entry.method,
            headers: requestConfig.headers,
            credentials: entry.withCredentials ? 'include' : 'same-origin',
        };

        if (payload !== undefined) {
            if (entry.data && entry.data.type === 'json') {
                fetchConfig.body = entry.data.value;
                if (!fetchConfig.headers || !fetchConfig.headers['Content-Type']) {
                    fetchConfig.headers = Object.assign({}, fetchConfig.headers, {
                        'Content-Type': 'application/json',
                    });
                }
            } else {
                fetchConfig.body = String(payload);
            }
        }

        const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;

        if (controller && typeof entry.timeout === 'number' && entry.timeout > 0) {
            fetchConfig.signal = controller.signal;
            setTimeout(() => controller.abort(), entry.timeout);
        }

        return fetch(entry.url, fetchConfig).then((response) => {
            if (!response.ok && RETRYABLE_STATUSES.has(response.status)) {
                const error = new Error(`Retryable response status: ${response.status}`);
                error.response = { status: response.status, statusText: response.statusText };
                throw error;
            }

            if (!response.ok) {
                const error = new Error(`Request failed with status ${response.status}`);
                error.response = { status: response.status, statusText: response.statusText };
                error.fatal = true;
                throw error;
            }

            return response;
        });
    }

    function formatErrorMessage(error) {
        if (!error) {
            return 'Unknown error';
        }

        if (error.response) {
            return `Status ${error.response.status} ${error.response.statusText || ''}`.trim();
        }

        if (error.message) {
            return error.message;
        }

        return 'Unknown error';
    }

    function formatTimestamp(timestamp) {
        try {
            const date = new Date(timestamp);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
        } catch (error) {
            return '';
        }
    }

    const offlineQueue = {
        enqueue(config) {
            const queue = loadQueue();
            queue.push(config);
            saveQueue(queue, { action: 'enqueue', entryId: config.id });
            return config;
        },
        all() {
            return loadQueue();
        },
        clear() {
            saveQueue([], { action: 'clear' });
        },
        flush(force = false) {
            if (flushInProgress) {
                return currentFlushPromise || Promise.resolve({ processed: 0, remaining: loadQueue().length, failed: 0 });
            }

            const queue = loadQueue();

            if (queue.length === 0) {
                const result = { processed: 0, remaining: 0, failed: 0 };
                dispatchQueueEvent(queue, { action: 'flush', result });
                return Promise.resolve(result);
            }

            if (!navigator.onLine && !force) {
                const result = { processed: 0, remaining: queue.length, failed: 0 };
                dispatchQueueEvent(queue, { action: 'flush', skipped: true, result });
                return Promise.resolve(result);
            }

            flushInProgress = true;
            dispatchQueueEvent(queue, { action: 'flush', state: 'running', total: queue.length });

            currentFlushPromise = (async () => {
                const remaining = [];
                let processed = 0;
                let failed = 0;
                const failures = [];

                for (const entry of queue) {
                    try {
                        await sendQueuedRequest(entry);
                        processed += 1;
                    } catch (error) {
                        const message = formatErrorMessage(error);
                        entry.attempts = (entry.attempts || 0) + 1;
                        entry.lastAttemptAt = new Date().toISOString();
                        entry.lastError = message;

                        const responseStatus = error && error.response ? error.response.status : null;
                        const retryable =
                            !error ||
                            (!error.fatal && (responseStatus === null || RETRYABLE_STATUSES.has(responseStatus)));

                        if (entry.attempts >= MAX_RETRY || !retryable) {
                            failed += 1;
                            failures.push({ id: entry.id, reason: message });
                            continue;
                        }

                        remaining.push(entry);
                    }
                }

                const meta = {
                    action: 'flush',
                    state: 'completed',
                    processed,
                    failed,
                    remaining: remaining.length,
                };

                if (failures.length > 0) {
                    meta.failures = failures;
                }

                if (processed > 0 && remaining.length === 0 && failed === 0) {
                    meta.message = `Processed ${processed} queued request${processed > 1 ? 's' : ''}.`;
                } else if (processed > 0) {
                    meta.message = `Processed ${processed} request${processed > 1 ? 's' : ''}. ${remaining.length} pending.`;
                } else if (failed > 0 && remaining.length === 0) {
                    meta.message = 'Queued requests failed permanently. Please review and resubmit.';
                }

                saveQueue(remaining, meta);

                flushInProgress = false;
                currentFlushPromise = null;

                return {
                    processed,
                    remaining: remaining.length,
                    failed,
                };
            })()
                .catch((error) => {
                    flushInProgress = false;
                    currentFlushPromise = null;
                    const queueAfterFailure = loadQueue();
                    dispatchQueueEvent(queueAfterFailure, {
                        action: 'flush',
                        state: 'error',
                        message: formatErrorMessage(error),
                    });
                    throw error;
                });

            return currentFlushPromise;
        },
    };

    window.qrpayOfflineQueue = offlineQueue;

    function setupAxiosInterceptor() {
        if (!window.axios || window.axios.__qrpayOfflineQueueConfigured) {
            return;
        }

        window.axios.__qrpayOfflineQueueConfigured = true;

        window.axios.interceptors.request.use(
            (config) => {
                if (!shouldQueueRequest(config)) {
                    return config;
                }

                const entry = createQueueEntry(config);

                if (!entry) {
                    return config;
                }

                offlineQueue.enqueue(entry);

                const cancelMessage = 'Request queued while offline';

                if (typeof window.axios.CancelToken !== 'undefined') {
                    return Promise.reject(new window.axios.Cancel(cancelMessage));
                }

                const cancelError = new Error(cancelMessage);
                cancelError.__CANCEL__ = true;
                return Promise.reject(cancelError);
            },
            (error) => Promise.reject(error)
        );
    }

    function updateBanner(eventDetail) {
        const banner = document.getElementById('offline-status-banner');

        if (!banner) {
            return;
        }

        const queue = eventDetail && Array.isArray(eventDetail.queue) ? eventDetail.queue : loadQueue();
        const meta = eventDetail ? eventDetail.meta || {} : {};
        const statusTextElement = document.getElementById('offline-status-text');
        const countElement = document.getElementById('offline-queue-count');
        const listElement = document.getElementById('offline-queue-list');
        const feedbackElement = document.getElementById('offline-queue-feedback');
        const retryButton = document.getElementById('offline-queue-retry');

        const isOffline = !navigator.onLine;
        const shouldShow = isOffline || queue.length > 0 || flushInProgress;

        if (!shouldShow) {
            banner.setAttribute('hidden', 'hidden');
            return;
        }

        banner.removeAttribute('hidden');

        if (statusTextElement) {
            if (isOffline) {
                statusTextElement.textContent = 'You are offline. Requests will be queued safely.';
            } else if (flushInProgress) {
                statusTextElement.textContent = 'Replaying queued requests…';
            } else {
                statusTextElement.textContent = 'Online. Monitoring queued requests.';
            }
        }

        if (countElement) {
            countElement.textContent = queue.length.toString();
        }

        if (listElement) {
            listElement.innerHTML = '';

            queue.forEach((entry) => {
                const item = document.createElement('li');
                item.className = 'offline-banner__item';
                const subtitleParts = [];

                if (entry.attempts) {
                    subtitleParts.push(`${entry.attempts} attempt${entry.attempts > 1 ? 's' : ''}`);
                }

                if (entry.lastError) {
                    subtitleParts.push(entry.lastError);
                }

                const subtitle = subtitleParts.length > 0 ? subtitleParts.join(' • ') : `Queued ${formatTimestamp(entry.createdAt)}`;

                item.innerHTML = `
                    <div class="offline-banner__item-title">${entry.method} ${entry.url}</div>
                    <div class="offline-banner__item-subtitle">${subtitle}</div>
                `;

                listElement.appendChild(item);
            });

            if (queue.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'offline-banner__item';
                empty.textContent = 'Queue is clear.';
                listElement.appendChild(empty);
            }
        }

        if (feedbackElement) {
            feedbackElement.textContent = meta && meta.message ? meta.message : '';
        }

        if (retryButton) {
            retryButton.disabled = flushInProgress || queue.length === 0;
        }

        banner.classList.toggle('is-offline', isOffline);
    }

    function setupRetryButton() {
        const retryButton = document.getElementById('offline-queue-retry');

        if (!retryButton) {
            return;
        }

        retryButton.addEventListener('click', () => {
            retryButton.disabled = true;

            offlineQueue
                .flush(true)
                .then((result) => {
                    dispatchQueueEvent(loadQueue(), {
                        action: 'manual-retry',
                        message: `Processed ${result.processed} request${result.processed === 1 ? '' : 's'}. ${result.remaining} still pending.`,
                    });
                })
                .catch((error) => {
                    dispatchQueueEvent(loadQueue(), {
                        action: 'manual-retry',
                        state: 'error',
                        message: formatErrorMessage(error),
                    });
                });
        });
    }

    function setupNetworkListeners() {
        window.addEventListener('online', () => {
            updateBanner();
            offlineQueue.flush().catch(() => {
                // Errors are emitted via queue events.
            });
        });

        window.addEventListener('offline', () => {
            updateBanner();
        });
    }

    function setupServiceWorkerMessaging() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        navigator.serviceWorker.addEventListener('message', (event) => {
            if (!event.data || !event.data.type) {
                return;
            }

            if (event.data.type === 'OFFLINE_QUEUE_SYNC') {
                offlineQueue.flush().catch(() => {
                    // Errors propagated via queue events.
                });
            }
        });
    }

    window.addEventListener('offline-queue:updated', (event) => {
        updateBanner(event.detail);
    });

    document.addEventListener('DOMContentLoaded', () => {
        setupAxiosInterceptor();
        setupRetryButton();
        setupNetworkListeners();
        setupServiceWorkerMessaging();
        updateBanner();

        if (navigator.onLine) {
            offlineQueue.flush().catch(() => {
                // ignore initial errors
            });
        }
    });
})();
