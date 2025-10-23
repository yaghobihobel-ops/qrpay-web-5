@props([
    'section',
    'label' => __('Learning resources'),
    'icon' => 'las la-graduation-cap',
    'variant' => 'outline-info',
    'language' => null,
    'size' => 'sm',
])

<div class="help-center-launcher" data-help-section="{{ $section }}" data-help-language="{{ $language ?? app()->getLocale() }}">
    <button type="button" class="btn btn-{{ $variant }} btn-{{ $size }} help-center-trigger" aria-label="{{ $label }}">
        <i class="{{ $icon }}"></i>
    </button>
</div>

@once
    @push('css')
        <style>
            .help-center-launcher {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-left: .5rem;
            }

            .help-center-launcher .help-center-trigger {
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.5rem;
                height: 2.5rem;
                padding: 0;
            }

            #helpCenterModal .modal-body {
                max-height: 60vh;
                overflow-y: auto;
            }

            #helpCenterModal .help-center-title {
                font-weight: 600;
                font-size: 1.25rem;
            }

            #helpCenterModal .help-center-content h1,
            #helpCenterModal .help-center-content h2,
            #helpCenterModal .help-center-content h3 {
                margin-top: 1.5rem;
                font-size: 1.05rem;
            }

            #helpCenterModal .help-center-content p,
            #helpCenterModal .help-center-content ul {
                margin-bottom: 1rem;
            }

            #helpCenterModal .help-center-faq-list button {
                width: 100%;
                text-align: left;
                border: none;
                background: transparent;
                padding: .5rem 0;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                font-weight: 500;
            }

            #helpCenterModal .help-center-meta {
                font-size: .8rem;
                color: var(--gray-600, #6c757d);
            }

            #helpCenterModal .help-center-loader {
                display: none;
            }

            #helpCenterModal.loading .help-center-loader {
                display: flex;
                align-items: center;
                gap: .75rem;
            }

            #helpCenterModal.loading .help-center-body,
            #helpCenterModal.loading .help-center-faq-wrapper,
            #helpCenterModal.loading .help-center-error {
                display: none;
            }

            #helpCenterModal.error .help-center-error {
                display: block;
            }

            #helpCenterModal.error .help-center-body,
            #helpCenterModal.error .help-center-faq-wrapper,
            #helpCenterModal.error .help-center-loader {
                display: none;
            }
        </style>
    @endpush
@endonce

@once
    <div class="modal fade" id="helpCenterModal" tabindex="-1" aria-labelledby="helpCenterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title help-center-title" id="helpCenterModalLabel">{{ __('Help center') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="help-center-loader">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <span>{{ __('Loading the latest guidance…') }}</span>
                    </div>
                    <div class="alert alert-danger d-none help-center-error" role="alert"></div>
                    <div class="help-center-body">
                        <div class="help-center-meta mb-3"></div>
                        <div class="help-center-content"></div>
                    </div>
                    <div class="help-center-faq-wrapper mt-4">
                        <h6 class="text-uppercase text-muted small">{{ __('Frequently asked questions') }}</h6>
                        <div class="help-center-faq-list"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endonce

@once
    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('helpCenterModal');
                if (!modalElement || typeof bootstrap === 'undefined') {
                    return;
                }

                const modalInstance = new bootstrap.Modal(modalElement);
                const loader = modalElement.querySelector('.help-center-loader');
                const bodyContainer = modalElement.querySelector('.help-center-body');
                const contentContainer = modalElement.querySelector('.help-center-content');
                const metaContainer = modalElement.querySelector('.help-center-meta');
                const errorContainer = modalElement.querySelector('.help-center-error');
                const faqList = modalElement.querySelector('.help-center-faq-list');
                const faqWrapper = modalElement.querySelector('.help-center-faq-wrapper');

                let activeSection = null;
                let activeLanguage = null;
                let activeVersion = null;
                let viewStartedAt = null;

                function csrfToken() {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    return tokenMeta ? tokenMeta.getAttribute('content') : '';
                }

                function setState(state) {
                    modalElement.classList.remove('loading', 'error');
                    if (state === 'loading') {
                        modalElement.classList.add('loading');
                        loader.classList.remove('d-none');
                    } else if (state === 'error') {
                        modalElement.classList.add('error');
                        errorContainer.classList.remove('d-none');
                    } else {
                        loader.classList.add('d-none');
                        errorContainer.classList.add('d-none');
                    }
                }

                function formatDate(value) {
                    if (!value) {
                        return '';
                    }
                    const date = new Date(value);
                    if (Number.isNaN(date.getTime())) {
                        return value;
                    }
                    return date.toLocaleDateString();
                }

                function renderContent(payload) {
                    const { section, content, version, released_at, language, faqs } = payload;
                    modalElement.querySelector('.help-center-title').textContent = section.title || '{{ __('Help center') }}';
                    contentContainer.innerHTML = content;
                    activeVersion = version;
                    activeLanguage = language;

                    const meta = [];
                    if (version) {
                        meta.push(`{{ __('Version') }} ${version}`);
                    }
                    if (released_at) {
                        meta.push(`{{ __('Updated') }} ${formatDate(released_at)}`);
                    }
                    metaContainer.textContent = meta.join(' · ');

                    faqList.innerHTML = '';
                    if (Array.isArray(faqs) && faqs.length) {
                        faqWrapper.classList.remove('d-none');
                        faqs.forEach(faq => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'help-center-faq-item';
                            button.dataset.faqId = faq.id;
                            if (faq.anchor) {
                                button.dataset.anchor = faq.anchor;
                            }
                            button.textContent = faq.question;
                            button.addEventListener('click', function () {
                                if (faq.anchor) {
                                    const anchorTarget = contentContainer.querySelector(`#${CSS.escape(faq.anchor)}`);
                                    if (anchorTarget) {
                                        anchorTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                    }
                                }
                                recordFaqInteraction(faq.id);
                            });
                            faqList.appendChild(button);
                        });
                    } else {
                        faqWrapper.classList.add('d-none');
                    }
                }

                function fetchContent(section, language) {
                    const query = new URLSearchParams();
                    if (language) {
                        query.set('lang', language);
                    }
                    const queryString = query.toString();
                    const url = queryString
                        ? `/help-center/sections/${encodeURIComponent(section)}?${queryString}`
                        : `/help-center/sections/${encodeURIComponent(section)}`;
                    setState('loading');
                    fetch(url, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }).then(response => {
                        if (!response.ok) {
                            throw response;
                        }
                        return response.json();
                    }).then(data => {
                        if (!data || !data.data) {
                            throw new Error('Invalid payload');
                        }
                        setState('ready');
                        renderContent(data.data);
                    }).catch(async (error) => {
                        let message = '{{ __('Unable to load help content. Please try again later.') }}';
                        if (error && typeof error.json === 'function') {
                            try {
                                const payload = await error.json();
                                message = payload.message || message;
                            } catch (e) {}
                        }
                        errorContainer.textContent = message;
                        setState('error');
                    });
                }

                function recordView(duration) {
                    if (!activeSection) {
                        return;
                    }
                    const payload = {
                        duration_seconds: duration,
                    };
                    if (activeVersion) {
                        payload.version = activeVersion;
                    }
                    if (activeLanguage) {
                        payload.language = activeLanguage;
                    }
                    fetch(`/help-center/sections/${encodeURIComponent(activeSection)}/track`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify(payload),
                    }).catch(() => {});
                }

                function recordFaqInteraction(faqId) {
                    if (!activeSection || !faqId) {
                        return;
                    }
                    const payload = { faq_id: faqId };
                    if (activeVersion) {
                        payload.version = activeVersion;
                    }
                    if (activeLanguage) {
                        payload.language = activeLanguage;
                    }
                    fetch(`/help-center/sections/${encodeURIComponent(activeSection)}/faq`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify(payload),
                    }).catch(() => {});
                }

                modalElement.addEventListener('hidden.bs.modal', function () {
                    if (activeSection && viewStartedAt) {
                        const duration = Math.max(1, Math.round((Date.now() - viewStartedAt) / 1000));
                        recordView(duration);
                    }
                    viewStartedAt = null;
                    activeSection = null;
                });

                document.querySelectorAll('.help-center-launcher').forEach(function (launcher) {
                    const trigger = launcher.querySelector('.help-center-trigger');
                    if (!trigger) {
                        return;
                    }
                    trigger.addEventListener('click', function () {
                        activeSection = launcher.dataset.helpSection;
                        activeLanguage = launcher.dataset.helpLanguage;
                        viewStartedAt = Date.now();
                        modalInstance.show();
                        fetchContent(activeSection, activeLanguage);
                    });
                });
            });
        </script>
    @endpush
@endonce
