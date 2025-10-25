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
                max-height: 70vh;
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

            #helpCenterModal .help-center-loader {
                display: none;
                align-items: center;
                gap: .75rem;
            }

            #helpCenterModal.loading .help-center-loader {
                display: flex;
            }

            #helpCenterModal.loading .help-center-body,
            #helpCenterModal.loading .help-center-faq-wrapper,
            #helpCenterModal.loading .help-center-feedback,
            #helpCenterModal.loading .help-center-error {
                display: none !important;
            }

            #helpCenterModal.error .help-center-error {
                display: block;
            }

            #helpCenterModal.error .help-center-body,
            #helpCenterModal.error .help-center-faq-wrapper,
            #helpCenterModal.error .help-center-feedback,
            #helpCenterModal.error .help-center-loader {
                display: none !important;
            }

            #helpCenterModal .help-center-search-results {
                max-height: 220px;
                overflow-y: auto;
                margin-top: .5rem;
                border-radius: .5rem;
                border: 1px solid rgba(0, 0, 0, 0.08);
                box-shadow: 0 6px 12px rgba(18, 38, 63, 0.08);
                background: #fff;
                z-index: 5;
            }

            #helpCenterModal .help-center-search-results button.list-group-item {
                border: none;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            #helpCenterModal .help-center-search-results button.list-group-item:last-child {
                border-bottom: none;
            }

            #helpCenterModal .help-center-steps-wrapper {
                border-top: 1px solid rgba(0, 0, 0, 0.05);
                padding-top: 1rem;
            }

            #helpCenterModal .help-step {
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: .75rem;
                padding: 1rem;
                margin-bottom: 1rem;
                background: #fff;
            }

            #helpCenterModal .help-step.completed {
                border-color: rgba(25, 135, 84, 0.4);
                background: rgba(25, 135, 84, 0.05);
            }

            #helpCenterModal .help-step-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            #helpCenterModal .help-step-number {
                font-weight: 600;
                color: var(--bs-primary, #0d6efd);
                margin-right: .75rem;
            }

            #helpCenterModal .help-step-summary {
                color: var(--gray-600, #6c757d);
            }

            #helpCenterModal .help-step-media video,
            #helpCenterModal .help-step-media img {
                width: 100%;
                border-radius: .5rem;
                margin-top: .75rem;
            }

            #helpCenterModal .help-step-media video {
                background: #000;
            }

            #helpCenterModal .help-step-media-caption {
                color: var(--gray-600, #6c757d);
            }

            #helpCenterModal .help-center-media-list .help-media-card {
                display: flex;
                align-items: center;
                gap: .75rem;
                padding: .85rem;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: .75rem;
                text-decoration: none;
                color: inherit;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            #helpCenterModal .help-center-media-list .help-media-card:hover {
                border-color: var(--bs-primary, #0d6efd);
                box-shadow: 0 6px 12px rgba(13, 110, 253, 0.12);
            }

            #helpCenterModal .help-media-card-icon {
                font-size: 1.5rem;
                color: var(--bs-primary, #0d6efd);
            }

            #helpCenterModal .help-feedback-option.active {
                color: #fff;
            }

            #helpCenterModal .help-feedback-option.active[data-rating="positive"] {
                background-color: var(--bs-success, #198754);
                border-color: var(--bs-success, #198754);
            }

            #helpCenterModal .help-feedback-option.active[data-rating="negative"] {
                background-color: var(--bs-danger, #dc3545);
                border-color: var(--bs-danger, #dc3545);
            }

            #helpCenterModal .help-center-progress .progress {
                height: .6rem;
                border-radius: .75rem;
                background-color: rgba(13, 110, 253, 0.12);
            }

            #helpCenterModal .help-center-progress .progress-bar {
                background: linear-gradient(90deg, #0d6efd, #6610f2);
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

            @media (max-width: 576px) {
                #helpCenterModal .modal-dialog {
                    margin: 0.5rem;
                }
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
                    <div class="help-center-search mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="las la-search"></i></span>
                            <input type="search" class="form-control help-center-search-input" placeholder="{{ __('Search help topics') }}" autocomplete="off">
                        </div>
                        <div class="help-center-search-results list-group d-none"></div>
                    </div>
                    <div class="help-center-loader mb-3">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <span>{{ __('Loading the latest guidance…') }}</span>
                    </div>
                    <div class="alert alert-danger d-none help-center-error" role="alert"></div>
                    <div class="help-center-body d-none">
                        <div class="help-center-meta mb-3"></div>
                        <div class="help-center-progress d-none">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted help-center-progress-label"></span>
                                <button type="button" class="btn btn-outline-success btn-sm help-center-complete-training" disabled>{{ __('Mark training complete') }}</button>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="help-center-steps-wrapper d-none mt-3">
                            <h6 class="text-uppercase text-muted small mb-2">{{ __('Guided walkthrough') }}</h6>
                            <div class="help-center-steps"></div>
                        </div>
                        <div class="help-center-content mt-3"></div>
                        <div class="help-center-media-library d-none mt-4">
                            <h6 class="text-uppercase text-muted small mb-2">{{ __('Additional resources') }}</h6>
                            <div class="help-center-media-list row g-3"></div>
                        </div>
                    </div>
                    <div class="help-center-faq-wrapper mt-4 d-none">
                        <h6 class="text-uppercase text-muted small">{{ __('Frequently asked questions') }}</h6>
                        <div class="help-center-faq-list"></div>
                    </div>
                    <div class="help-center-feedback mt-4 d-none">
                        <h6 class="text-uppercase text-muted small">{{ __('Was this helpful?') }}</h6>
                        <div class="btn-group" role="group" aria-label="{{ __('Help center feedback') }}">
                            <button type="button" class="btn btn-outline-success btn-sm help-feedback-option" data-rating="positive">
                                <i class="las la-thumbs-up"></i> {{ __('Yes') }}
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm help-feedback-option" data-rating="negative">
                                <i class="las la-thumbs-down"></i> {{ __('No') }}
                            </button>
                        </div>
                        <div class="help-feedback-form d-none mt-3">
                            <textarea class="form-control help-feedback-comment" rows="2" placeholder="{{ __('Share details (optional)') }}"></textarea>
                            <div class="d-flex justify-content-end mt-2">
                                <button type="button" class="btn btn-primary btn-sm help-feedback-submit" disabled>{{ __('Send feedback') }}</button>
                            </div>
                        </div>
                        <div class="help-feedback-status small mt-2 d-none"></div>
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

                const strings = @json([
                    'stepComplete' => __('Mark step complete'),
                    'stepCompleted' => __('Completed'),
                    'trainingComplete' => __('Mark training complete'),
                    'trainingCompleted' => __('Training completed'),
                    'progressLabel' => __('Steps completed: :done / :total', ['done' => ':done', 'total' => ':total']),
                    'searchEmpty' => __('No matching guides found.'),
                    'estimatedLabel' => __('Est. time'),
                    'minutes' => __('min'),
                    'feedbackSaved' => __('Thanks for sharing your feedback!'),
                    'feedbackError' => __('Unable to submit feedback right now.'),
                ]);

                const modalInstance = new bootstrap.Modal(modalElement);
                const loader = modalElement.querySelector('.help-center-loader');
                const bodyContainer = modalElement.querySelector('.help-center-body');
                const contentContainer = modalElement.querySelector('.help-center-content');
                const metaContainer = modalElement.querySelector('.help-center-meta');
                const errorContainer = modalElement.querySelector('.help-center-error');
                const faqList = modalElement.querySelector('.help-center-faq-list');
                const faqWrapper = modalElement.querySelector('.help-center-faq-wrapper');
                const progressContainer = modalElement.querySelector('.help-center-progress');
                const progressBar = progressContainer.querySelector('.progress-bar');
                const progressLabel = progressContainer.querySelector('.help-center-progress-label');
                const completeTrainingButton = modalElement.querySelector('.help-center-complete-training');
                const stepsWrapper = modalElement.querySelector('.help-center-steps-wrapper');
                const stepsContainer = modalElement.querySelector('.help-center-steps');
                const mediaLibrary = modalElement.querySelector('.help-center-media-library');
                const mediaList = modalElement.querySelector('.help-center-media-list');
                const feedbackSection = modalElement.querySelector('.help-center-feedback');
                const feedbackOptions = modalElement.querySelectorAll('.help-feedback-option');
                const feedbackForm = modalElement.querySelector('.help-feedback-form');
                const feedbackTextarea = modalElement.querySelector('.help-feedback-comment');
                const feedbackSubmit = modalElement.querySelector('.help-feedback-submit');
                const feedbackStatus = modalElement.querySelector('.help-feedback-status');
                const searchInput = modalElement.querySelector('.help-center-search-input');
                const searchResults = modalElement.querySelector('.help-center-search-results');

                let activeSection = null;
                let activeLanguage = null;
                let activeVersion = null;
                let viewStartedAt = null;
                let stepsData = [];
                let completedSteps = new Set();
                let completionStatus = null;
                let activeRating = null;
                let searchTimer = null;
                const sectionsCache = new Map();

                function csrfToken() {
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    return tokenMeta ? tokenMeta.getAttribute('content') : '';
                }

                function setState(state) {
                    modalElement.classList.remove('loading', 'error');
                    if (state === 'loading') {
                        modalElement.classList.add('loading');
                        loader.classList.remove('d-none');
                        bodyContainer.classList.add('d-none');
                        faqWrapper.classList.add('d-none');
                        feedbackSection.classList.add('d-none');
                    } else if (state === 'error') {
                        modalElement.classList.add('error');
                        errorContainer.classList.remove('d-none');
                    } else {
                        loader.classList.add('d-none');
                        errorContainer.classList.add('d-none');
                        bodyContainer.classList.remove('d-none');
                        feedbackSection.classList.remove('d-none');
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

                function resetFeedback() {
                    activeRating = null;
                    feedbackOptions.forEach(button => {
                        button.classList.remove('active');
                        button.disabled = false;
                    });
                    feedbackTextarea.value = '';
                    feedbackForm.classList.add('d-none');
                    feedbackSubmit.disabled = true;
                    feedbackStatus.classList.add('d-none');
                    feedbackStatus.classList.remove('text-success', 'text-danger');
                    feedbackStatus.textContent = '';
                }

                function updateProgress() {
                    const total = stepsData.length;
                    if (!total) {
                        progressContainer.classList.add('d-none');
                        completeTrainingButton.disabled = true;
                        return;
                    }

                    progressContainer.classList.remove('d-none');
                    const done = completedSteps.size;
                    const percent = Math.round((done / total) * 100);
                    progressBar.style.width = `${percent}%`;
                    progressBar.setAttribute('aria-valuenow', String(percent));
                    progressLabel.textContent = strings.progressLabel.replace(':done', done).replace(':total', total);

                    if (done >= total) {
                        completeTrainingButton.disabled = false;
                        completeTrainingButton.textContent = strings.trainingCompleted;
                        completeTrainingButton.classList.add('btn-success');
                        completeTrainingButton.classList.remove('btn-outline-success');
                    } else {
                        completeTrainingButton.disabled = true;
                        completeTrainingButton.textContent = strings.trainingComplete;
                        completeTrainingButton.classList.add('btn-outline-success');
                        completeTrainingButton.classList.remove('btn-success');
                    }
                }

                function createStepMediaElement(item) {
                    if (!item || !item.url) {
                        return null;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'help-step-media-item';

                    if (item.type === 'video') {
                        const video = document.createElement('video');
                        video.controls = true;
                        video.src = item.url;
                        video.preload = 'metadata';
                        if (item.poster) {
                            video.poster = item.poster;
                        }
                        wrapper.appendChild(video);
                    } else if (item.type === 'gif' || item.type === 'image') {
                        const img = document.createElement('img');
                        img.src = item.url;
                        img.alt = item.caption || item.label || '';
                        wrapper.appendChild(img);
                    } else {
                        const link = document.createElement('a');
                        link.href = item.url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.textContent = item.label || item.caption || item.url;
                        link.className = 'help-step-link';
                        wrapper.appendChild(link);
                    }

                    if (item.caption) {
                        const caption = document.createElement('div');
                        caption.className = 'help-step-media-caption small mt-1';
                        caption.textContent = item.caption;
                        wrapper.appendChild(caption);
                    }

                    return wrapper;
                }

                function renderSteps(steps) {
                    stepsData = Array.isArray(steps) ? steps : [];
                    completedSteps = new Set();
                    completionStatus = null;
                    stepsContainer.innerHTML = '';

                    if (!stepsData.length) {
                        stepsWrapper.classList.add('d-none');
                        updateProgress();
                        return;
                    }

                    stepsWrapper.classList.remove('d-none');

                    stepsData.forEach((step, index) => {
                        const stepElement = document.createElement('article');
                        stepElement.className = 'help-step';
                        stepElement.dataset.stepId = step.id || `step-${index + 1}`;

                        const header = document.createElement('div');
                        header.className = 'help-step-header';

                        const headerInfo = document.createElement('div');
                        headerInfo.className = 'help-step-heading';

                        const number = document.createElement('span');
                        number.className = 'help-step-number';
                        number.textContent = `${index + 1}.`;
                        headerInfo.appendChild(number);

                        const title = document.createElement('div');
                        title.className = 'help-step-title fw-semibold';
                        title.textContent = step.title || `Step ${index + 1}`;
                        headerInfo.appendChild(title);

                        if (step.summary) {
                            const summary = document.createElement('div');
                            summary.className = 'help-step-summary small';
                            summary.textContent = step.summary;
                            headerInfo.appendChild(summary);
                        }

                        const action = document.createElement('button');
                        action.type = 'button';
                        action.className = 'btn btn-outline-success btn-sm help-step-complete';
                        action.dataset.stepId = stepElement.dataset.stepId;
                        action.textContent = strings.stepComplete;
                        action.addEventListener('click', function () {
                            const id = this.dataset.stepId;
                            if (!id) {
                                return;
                            }
                            if (completedSteps.has(id)) {
                                completedSteps.delete(id);
                                stepElement.classList.remove('completed');
                                this.classList.remove('btn-success');
                                this.classList.add('btn-outline-success');
                                this.textContent = strings.stepComplete;
                            } else {
                                completedSteps.add(id);
                                stepElement.classList.add('completed');
                                this.classList.add('btn-success');
                                this.classList.remove('btn-outline-success');
                                this.textContent = strings.stepCompleted;
                            }
                            updateProgress();
                        });

                        header.appendChild(headerInfo);
                        header.appendChild(action);
                        stepElement.appendChild(header);

                        if (step.html) {
                            const body = document.createElement('div');
                            body.className = 'help-step-body mt-3';
                            body.innerHTML = step.html;
                            stepElement.appendChild(body);
                        }

                        if (Array.isArray(step.media) && step.media.length) {
                            const mediaContainer = document.createElement('div');
                            mediaContainer.className = 'help-step-media mt-3';
                            step.media.forEach(mediaItem => {
                                const mediaElement = createStepMediaElement(mediaItem);
                                if (mediaElement) {
                                    mediaContainer.appendChild(mediaElement);
                                }
                            });
                            if (mediaContainer.childElementCount) {
                                stepElement.appendChild(mediaContainer);
                            }
                        }

                        stepsContainer.appendChild(stepElement);
                    });

                    updateProgress();
                }

                function mediaIcon(type) {
                    switch (type) {
                        case 'video':
                            return 'las la-play-circle';
                        case 'gif':
                        case 'image':
                            return 'las la-image';
                        case 'pdf':
                            return 'las la-file-alt';
                        default:
                            return 'las la-external-link-alt';
                    }
                }

                function renderMediaLibrary(mediaItems) {
                    mediaList.innerHTML = '';
                    if (!Array.isArray(mediaItems) || !mediaItems.length) {
                        mediaLibrary.classList.add('d-none');
                        return;
                    }

                    mediaLibrary.classList.remove('d-none');
                    mediaItems.forEach(item => {
                        if (!item || !item.url) {
                            return;
                        }
                        const column = document.createElement('div');
                        column.className = 'col-md-6';
                        const link = document.createElement('a');
                        link.href = item.url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'help-media-card';
                        const icon = document.createElement('div');
                        icon.className = 'help-media-card-icon';
                        const iconElement = document.createElement('i');
                        iconElement.className = mediaIcon(item.type);
                        icon.appendChild(iconElement);
                        const copy = document.createElement('div');
                        const label = document.createElement('div');
                        label.className = 'fw-semibold';
                        label.textContent = item.label || item.caption || item.type || item.url;
                        copy.appendChild(label);
                        if (item.caption && item.caption !== label.textContent) {
                            const caption = document.createElement('div');
                            caption.className = 'small text-muted';
                            caption.textContent = item.caption;
                            copy.appendChild(caption);
                        }
                        link.appendChild(icon);
                        link.appendChild(copy);
                        column.appendChild(link);
                        mediaList.appendChild(column);
                    });
                }

                function renderFaqs(faqs) {
                    faqList.innerHTML = '';
                    if (!Array.isArray(faqs) || !faqs.length) {
                        faqWrapper.classList.add('d-none');
                        return;
                    }

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
                }

                function renderContent(payload) {
                    const { section, content, version, released_at, language, faqs, steps, media, meta } = payload;
                    modalElement.querySelector('.help-center-title').textContent = section.title || '{{ __('Help center') }}';
                    contentContainer.innerHTML = content;
                    activeVersion = version;
                    activeLanguage = language;

                    const metaBits = [];
                    if (version) {
                        metaBits.push(`{{ __('Version') }} ${version}`);
                    }
                    if (released_at) {
                        metaBits.push(`{{ __('Updated') }} ${formatDate(released_at)}`);
                    }
                    if (meta && meta.estimated_duration) {
                        metaBits.push(`${strings.estimatedLabel} ${meta.estimated_duration} ${strings.minutes}`);
                    }
                    metaContainer.textContent = metaBits.join(' · ');

                    renderSteps(steps);
                    renderMediaLibrary(media);
                    renderFaqs(faqs);
                    resetFeedback();
                    feedbackSection.classList.remove('d-none');
                }

                function resetSearch() {
                    searchInput.value = '';
                    searchResults.innerHTML = '';
                    searchResults.classList.add('d-none');
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
                        renderContent(data.data);
                        setState('ready');
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
                    if (completedSteps.size) {
                        payload.meta = {
                            steps_completed: Array.from(completedSteps),
                        };
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

                function recordCompletion(status) {
                    if (!activeSection || !stepsData.length) {
                        return Promise.resolve();
                    }
                    const payload = {
                        total_steps: stepsData.length,
                        completed_steps: completedSteps.size,
                        status,
                        meta: {
                            steps: Array.from(completedSteps),
                        },
                    };
                    if (activeVersion) {
                        payload.version = activeVersion;
                    }
                    if (activeLanguage) {
                        payload.language = activeLanguage;
                    }

                    return fetch(`/help-center/sections/${encodeURIComponent(activeSection)}/complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify(payload),
                    }).then(response => {
                        if (!response.ok) {
                            throw response;
                        }
                        completionStatus = status;
                    }).catch(() => {
                        completionStatus = completionStatus || null;
                    });
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

                function recordFeedback() {
                    if (!activeSection || !activeRating) {
                        return Promise.resolve(false);
                    }
                    const payload = {
                        rating: activeRating,
                    };
                    if (activeVersion) {
                        payload.version = activeVersion;
                    }
                    if (activeLanguage) {
                        payload.language = activeLanguage;
                    }
                    const comment = feedbackTextarea.value.trim();
                    if (comment) {
                        payload.comment = comment;
                    }
                    if (completedSteps.size) {
                        payload.meta = {
                            steps: Array.from(completedSteps),
                        };
                    }

                    return fetch(`/help-center/sections/${encodeURIComponent(activeSection)}/feedback`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken(),
                        },
                        body: JSON.stringify(payload),
                    }).then(response => {
                        if (!response.ok) {
                            throw response;
                        }
                        return true;
                    }).catch(() => false);
                }

                function fetchSections(language, query = '') {
                    const params = new URLSearchParams();
                    if (language) {
                        params.set('lang', language);
                    }
                    if (query) {
                        params.set('q', query);
                    }
                    const key = params.toString() || 'default';
                    if (sectionsCache.has(key)) {
                        return Promise.resolve(sectionsCache.get(key));
                    }
                    const url = params.toString() ? `/help-center/sections?${params.toString()}` : '/help-center/sections';
                    return fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    }).then(response => {
                        if (!response.ok) {
                            throw response;
                        }
                        return response.json();
                    }).then(data => {
                        const sections = data && data.data ? data.data : [];
                        sectionsCache.set(key, sections);
                        return sections;
                    }).catch(() => []);
                }

                function renderSearchResults(items) {
                    searchResults.innerHTML = '';
                    if (!items.length) {
                        const empty = document.createElement('div');
                        empty.className = 'list-group-item small text-muted';
                        empty.textContent = strings.searchEmpty;
                        searchResults.appendChild(empty);
                    } else {
                        items.forEach(item => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'list-group-item list-group-item-action';
                            const title = document.createElement('div');
                            title.className = 'fw-semibold';
                            title.textContent = item.title || item.id;
                            const summary = document.createElement('div');
                            summary.className = 'small text-muted';
                            summary.textContent = item.summary || '';
                            button.appendChild(title);
                            button.appendChild(summary);
                            button.addEventListener('click', function () {
                                const languages = Array.isArray(item.available_languages) ? item.available_languages : [];
                                const preferredLanguage = languages.includes(activeLanguage) ? activeLanguage : (item.default_language || languages[0] || null);
                                openSection(item.id, preferredLanguage);
                                resetSearch();
                            });
                            searchResults.appendChild(button);
                        });
                    }
                    searchResults.classList.remove('d-none');
                }

                function openSection(section, language) {
                    if (!section) {
                        return;
                    }
                    activeSection = section;
                    activeLanguage = language || activeLanguage || null;
                    viewStartedAt = Date.now();
                    resetSearch();
                    modalInstance.show();
                    fetchContent(activeSection, activeLanguage);
                    fetchSections(activeLanguage).catch(() => {});
                }

                modalElement.addEventListener('hidden.bs.modal', function () {
                    if (activeSection && viewStartedAt) {
                        const duration = Math.max(1, Math.round((Date.now() - viewStartedAt) / 1000));
                        recordView(duration);
                    }
                    if (activeSection && stepsData.length && completedSteps.size && completionStatus !== 'completed') {
                        recordCompletion('in_progress');
                    }
                    viewStartedAt = null;
                    activeSection = null;
                    activeVersion = null;
                    stepsData = [];
                    completedSteps = new Set();
                    completionStatus = null;
                    resetFeedback();
                    resetSearch();
                });

                completeTrainingButton.addEventListener('click', function () {
                    if (stepsData.length === 0 || completedSteps.size < stepsData.length) {
                        return;
                    }
                    const button = this;
                    button.disabled = true;
                    recordCompletion('completed').then(() => {
                        button.textContent = strings.trainingCompleted;
                        button.classList.add('btn-success');
                        button.classList.remove('btn-outline-success');
                    }).catch(() => {
                        button.disabled = false;
                    });
                });

                feedbackOptions.forEach(button => {
                    button.addEventListener('click', function () {
                        activeRating = this.dataset.rating;
                        feedbackOptions.forEach(option => option.classList.remove('active'));
                        this.classList.add('active');
                        feedbackForm.classList.remove('d-none');
                        feedbackSubmit.disabled = false;
                        feedbackStatus.classList.add('d-none');
                        feedbackStatus.textContent = '';
                    });
                });

                feedbackSubmit.addEventListener('click', function () {
                    if (!activeRating) {
                        return;
                    }
                    const button = this;
                    button.disabled = true;
                    recordFeedback().then(success => {
                        feedbackStatus.classList.remove('d-none');
                        feedbackStatus.classList.toggle('text-success', success);
                        feedbackStatus.classList.toggle('text-danger', !success);
                        feedbackStatus.textContent = success ? strings.feedbackSaved : strings.feedbackError;
                        if (success) {
                            feedbackForm.classList.add('d-none');
                            feedbackOptions.forEach(option => option.disabled = true);
                        } else {
                            button.disabled = false;
                        }
                    }).catch(() => {
                        feedbackStatus.classList.remove('d-none');
                        feedbackStatus.classList.add('text-danger');
                        feedbackStatus.textContent = strings.feedbackError;
                        button.disabled = false;
                    });
                });

                searchInput.addEventListener('input', function () {
                    const value = this.value.trim();
                    if (searchTimer) {
                        clearTimeout(searchTimer);
                    }
                    if (value.length < 2) {
                        resetSearch();
                        return;
                    }
                    searchTimer = setTimeout(() => {
                        fetchSections(activeLanguage, value).then(renderSearchResults);
                    }, 200);
                });

                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        resetSearch();
                    }
                });

                document.addEventListener('click', function (event) {
                    if (!modalElement.contains(event.target) || event.target === searchInput) {
                        return;
                    }
                    if (!searchResults.contains(event.target)) {
                        searchResults.classList.add('d-none');
                    }
                });

                document.querySelectorAll('.help-center-launcher').forEach(function (launcher) {
                    const trigger = launcher.querySelector('.help-center-trigger');
                    if (!trigger) {
                        return;
                    }
                    trigger.addEventListener('click', function () {
                        const sectionId = launcher.dataset.helpSection;
                        const language = launcher.dataset.helpLanguage;
                        openSection(sectionId, language);
                    });
                });
            });
        </script>
    @endpush
@endonce
