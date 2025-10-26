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

            #helpCenterModal .help-center-tab-switcher {
                display: inline-flex;
                align-items: center;
                gap: .5rem;
                margin-bottom: 1rem;
            }

            #helpCenterModal .help-center-tab-switcher .help-center-tab {
                border: 1px solid var(--bs-border-color, #dee2e6);
                background-color: #fff;
                color: inherit;
                padding: .35rem .85rem;
                border-radius: 999px;
                font-weight: 500;
                transition: all .2s ease;
            }

            #helpCenterModal .help-center-tab-switcher .help-center-tab.active {
                background-color: var(--bs-primary, #0d6efd);
                border-color: var(--bs-primary, #0d6efd);
                color: #fff;
                box-shadow: 0 0 0 0.1rem rgba(13, 110, 253, 0.15);
            }

            #helpCenterModal .help-center-section {
                display: none;
            }

            #helpCenterModal .help-center-section.active {
                display: block;
            }

            #helpCenterModal .help-center-chat {
                display: flex;
                flex-direction: column;
                min-height: 320px;
            }

            #helpCenterModal .help-center-chat-thread {
                flex: 1 1 auto;
                overflow-y: auto;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: .75rem;
                padding: 1rem;
                background-color: rgba(248, 249, 250, 0.65);
                max-height: 45vh;
            }

            #helpCenterModal .help-center-chat-bubble {
                display: inline-block;
                margin-bottom: .75rem;
                padding: .65rem .85rem;
                border-radius: .95rem;
                font-size: .95rem;
                line-height: 1.45;
                max-width: 80%;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
                word-break: break-word;
            }

            #helpCenterModal .help-center-chat-bubble-user {
                background-color: var(--bs-primary, #0d6efd);
                color: #fff;
                margin-left: auto;
                border-bottom-right-radius: .25rem;
            }

            #helpCenterModal .help-center-chat-bubble-bot {
                background-color: #fff;
                color: inherit;
                border-bottom-left-radius: .25rem;
            }

            #helpCenterModal .help-center-chat-form textarea {
                resize: vertical;
                min-height: 80px;
            }

            #helpCenterModal .help-center-handoff-form {
                border: 1px solid rgba(0, 0, 0, 0.05);
                border-radius: .75rem;
                padding: 1rem;
                background-color: rgba(248, 249, 250, 0.65);
            }

            #helpCenterModal .help-center-chat-alert {
                font-size: .9rem;
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
                    <div class="help-center-tab-switcher" role="tablist">
                        <button type="button" class="help-center-tab active" data-help-tab="articles" aria-pressed="true">
                            <i class="las la-book-reader me-1"></i> {{ __('Knowledge base') }}
                        </button>
                        <button type="button" class="help-center-tab" data-help-tab="chat" aria-pressed="false">
                            <i class="las la-comments me-1"></i> {{ __('Chat with us') }}
                        </button>
                    </div>
                    <div class="help-center-section active" data-help-section-view="articles">
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
                    <div class="help-center-section d-none" data-help-section-view="chat">
                        <div class="help-center-chat">
                            <div class="help-center-chat-alert alert d-none" role="alert"></div>
                            <div class="help-center-chat-thread"></div>
                            <div class="help-center-chat-status text-muted small mt-2"></div>
                            <form class="help-center-chat-form mt-3" novalidate>
                                <label for="helpCenterChatMessage" class="form-label visually-hidden">{{ __('Message') }}</label>
                                <textarea class="form-control" id="helpCenterChatMessage" rows="2" placeholder="{{ __('Type your question…') }}" required></textarea>
                                <div class="d-flex align-items-center justify-content-between mt-2 gap-2">
                                    <button type="submit" class="btn btn--base">
                                        <i class="las la-paper-plane me-1"></i>{{ __('Send') }}
                                    </button>
                                    <button type="button" class="btn btn-link px-0 text-decoration-none help-center-handoff-trigger">
                                        <i class="las la-headset me-1"></i>{{ __('Need a human?') }}
                                    </button>
                                </div>
                            </form>
                            <form class="help-center-handoff-form mt-3 d-none" novalidate>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Your name') }}</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Email address') }}</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Subject') }}</label>
                                        <input type="text" class="form-control" name="subject" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Describe your issue') }}</label>
                                        <textarea class="form-control" name="message" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-3 gap-2">
                                    <button type="submit" class="btn btn--base flex-grow-1">
                                        <i class="las la-ticket-alt me-1"></i>{{ __('Create support ticket') }}
                                    </button>
                                    <button type="button" class="btn btn-link px-0 text-decoration-none help-center-handoff-cancel">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </form>
                        </div>
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
                const tabButtons = modalElement.querySelectorAll('[data-help-tab]');
                const sectionViews = modalElement.querySelectorAll('.help-center-section');
                const chatThread = modalElement.querySelector('.help-center-chat-thread');
                const chatForm = modalElement.querySelector('.help-center-chat-form');
                const chatTextarea = chatForm ? chatForm.querySelector('textarea') : null;
                const chatAlert = modalElement.querySelector('.help-center-chat-alert');
                const chatStatus = modalElement.querySelector('.help-center-chat-status');
                const handoffTrigger = modalElement.querySelector('.help-center-handoff-trigger');
                const handoffForm = modalElement.querySelector('.help-center-handoff-form');
                const handoffCancel = modalElement.querySelector('.help-center-handoff-cancel');

                let activeSection = null;
                let activeLanguage = null;
                let activeVersion = null;
                let viewStartedAt = null;
                let chatInitialized = false;
                let isSendingMessage = false;
                let ticketCreated = false;

                const sessionStorageKey = 'support_bot_session_id';
                let chatSessionId = localStorage.getItem(sessionStorageKey) || null;
                const chatTranscript = [];

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

                function switchTab(target) {
                    sectionViews.forEach(function (section) {
                        const isActive = section.dataset.helpSectionView === target;
                        section.classList.toggle('active', isActive);
                        section.classList.toggle('d-none', !isActive);
                    });
                    tabButtons.forEach(function (button) {
                        const isActive = button.dataset.helpTab === target;
                        button.classList.toggle('active', isActive);
                        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });
                    if (target === 'chat') {
                        initializeChat();
                        if (chatTextarea) {
                            setTimeout(function () {
                                chatTextarea.focus();
                            }, 120);
                        }
                    }
                }

                function initializeChat() {
                    if (chatInitialized || !chatThread) {
                        return;
                    }
                    chatInitialized = true;
                    const greeting = @json(__("Hi! I'm SupportBot. Ask me anything or let me know if you'd like to talk to a specialist."));
                    appendChatMessage('bot', greeting);
                    chatTranscript.push({ sender: 'bot', message: greeting });
                }

                function appendChatMessage(sender, text) {
                    if (!chatThread || !text) {
                        return;
                    }
                    const bubble = document.createElement('div');
                    bubble.className = 'help-center-chat-bubble help-center-chat-bubble-' + sender;
                    bubble.textContent = text;
                    chatThread.appendChild(bubble);
                    chatThread.scrollTop = chatThread.scrollHeight;
                }

                function setChatAlert(type, message) {
                    if (!chatAlert) {
                        return;
                    }
                    chatAlert.classList.remove('alert-success', 'alert-danger', 'alert-info');
                    if (!message) {
                        chatAlert.classList.add('d-none');
                        chatAlert.textContent = '';
                        return;
                    }
                    chatAlert.textContent = message;
                    chatAlert.classList.remove('d-none');
                    chatAlert.classList.add('alert-' + (type || 'info'));
                }

                function setChatStatus(message) {
                    if (chatStatus) {
                        chatStatus.textContent = message || '';
                    }
                }

                function transcriptSummary() {
                    if (!chatTranscript.length) {
                        return '{{ __('Customer requested support from chat, no transcript available.') }}';
                    }
                    return chatTranscript.map(function (entry) {
                        const prefix = entry.sender === 'user' ? '{{ __('Customer') }}' : '{{ __('Assistant') }}';
                        return prefix + ': ' + entry.message;
                    }).join('\n');
                }

                function toggleHandoffForm(show) {
                    if (!handoffForm) {
                        return;
                    }
                    handoffForm.classList.toggle('d-none', !show);
                    if (show) {
                        const subjectField = handoffForm.querySelector('[name="subject"]');
                        const messageField = handoffForm.querySelector('[name="message"]');
                        if (subjectField && !subjectField.value) {
                            subjectField.value = activeSection
                                ? `{{ __('Follow-up on') }} ${activeSection}`
                                : '{{ __('Support chat follow-up') }}';
                        }
                        if (messageField) {
                            messageField.value = transcriptSummary();
                        }
                    }
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

                function sendChatMessage(message) {
                    if (!message || isSendingMessage) {
                        return;
                    }
                    appendChatMessage('user', message);
                    chatTranscript.push({ sender: 'user', message: message });
                    setChatAlert(null, '');
                    setChatStatus('{{ __('SupportBot is typing…') }}');
                    isSendingMessage = true;

                    const context = {};
                    if (activeSection) {
                        context.section = activeSection;
                    }
                    if (activeLanguage) {
                        context.language = activeLanguage;
                    }
                    context.url = window.location.pathname;

                    const payload = {
                        message: message,
                        session_id: chatSessionId,
                        context: context,
                        locale: activeLanguage,
                    };

                    fetch('/api/v1/support/bot/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    }).then(async response => {
                        if (!response.ok) {
                            const errorPayload = await response.json().catch(() => ({}));
                            const errorMessage = errorPayload.message || '{{ __('We could not reach the virtual assistant. Please try again or request a human agent.') }}';
                            throw new Error(errorMessage);
                        }
                        return response.json();
                    }).then(data => {
                        const payload = data && data.data ? data.data : null;
                        if (!payload) {
                            throw new Error('Invalid payload');
                        }
                        chatSessionId = payload.session_id;
                        if (chatSessionId) {
                            localStorage.setItem(sessionStorageKey, chatSessionId);
                        }
                        appendChatMessage('bot', payload.reply);
                        chatTranscript.push({ sender: 'bot', message: payload.reply });
                        setChatStatus(payload.handoff ? '{{ __('I recommend talking with our support team.') }}' : '{{ __('Let me know if you need anything else.') }}');
                        if (payload.handoff && handoffTrigger) {
                            handoffTrigger.classList.add('text-warning');
                        }
                    }).catch(error => {
                        setChatAlert('danger', error.message || '{{ __('The assistant is unavailable right now. Please try again.') }}');
                        setChatStatus('');
                    }).finally(() => {
                        isSendingMessage = false;
                    });
                }

                if (chatForm && chatTextarea) {
                    chatForm.addEventListener('submit', function (event) {
                        event.preventDefault();
                        const value = chatTextarea.value.trim();
                        if (!value) {
                            return;
                        }
                        chatTextarea.value = '';
                        sendChatMessage(value);
                    });
                }

                if (handoffTrigger) {
                    handoffTrigger.addEventListener('click', function () {
                        switchTab('chat');
                        toggleHandoffForm(true);
                        handoffTrigger.classList.add('d-none');
                    });
                }

                if (handoffCancel) {
                    handoffCancel.addEventListener('click', function () {
                        toggleHandoffForm(false);
                        if (handoffTrigger && !ticketCreated) {
                            handoffTrigger.classList.remove('d-none');
                        }
                    });
                }

                if (handoffForm) {
                    handoffForm.addEventListener('submit', function (event) {
                        event.preventDefault();
                        const formData = new FormData(handoffForm);
                        const payload = {};
                        formData.forEach(function (value, key) {
                            payload[key] = value;
                        });
                        if (!payload.name || !payload.email || !payload.subject || !payload.message) {
                            setChatAlert('danger', '{{ __('Please fill in all required fields before creating a ticket.') }}');
                            return;
                        }
                        setChatAlert(null, '');
                        setChatStatus('{{ __('Sharing your conversation with our support specialists…') }}');

                        const submitButton = handoffForm.querySelector('button[type="submit"]');
                        if (submitButton) {
                            submitButton.disabled = true;
                        }

                        const requestBody = {
                            name: payload.name,
                            email: payload.email,
                            subject: payload.subject,
                            message: payload.message,
                            bot_session_id: chatSessionId,
                        };

                        fetch('/api/v1/support/tickets', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(requestBody),
                        }).then(async response => {
                            const json = await response.json().catch(() => ({}));
                            if (!response.ok) {
                                const errorMessage = json.message || '{{ __('Unable to create a ticket right now. Please try again later.') }}';
                                throw new Error(errorMessage);
                            }
                            return json;
                        }).then(data => {
                            ticketCreated = true;
                            const payload = data && data.data ? data.data : {};
                            const token = payload.token || '';
                            setChatAlert('success', token
                                ? `{{ __('Ticket created successfully! Reference:') }} ${token}`
                                : '{{ __('Ticket created successfully! Our team will reach out soon.') }}');
                            setChatStatus('{{ __('A specialist will contact you at the email you provided.') }}');
                            toggleHandoffForm(false);
                        }).catch(error => {
                            setChatAlert('danger', error.message || '{{ __('We were unable to create the ticket. Please try again later.') }}');
                            setChatStatus('');
                        }).finally(() => {
                            if (submitButton) {
                                submitButton.disabled = false;
                            }
                            if (handoffTrigger && !ticketCreated) {
                                handoffTrigger.classList.remove('d-none');
                            }
                        });
                    });
                }

                tabButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        switchTab(button.dataset.helpTab);
                    });
                });

                switchTab('articles');

                modalElement.addEventListener('hidden.bs.modal', function () {
                    if (activeSection && viewStartedAt) {
                        const duration = Math.max(1, Math.round((Date.now() - viewStartedAt) / 1000));
                        recordView(duration);
                    }
                    viewStartedAt = null;
                    activeSection = null;
                    activeLanguage = null;
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
                        switchTab('articles');
                        fetchContent(activeSection, activeLanguage);
                    });
                });
            });
        </script>
    @endpush
@endonce
