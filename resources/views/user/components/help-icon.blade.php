@php
    $sectionKey = \Illuminate\Support\Str::slug($section ?? '');
    $title = __("help.sections.$sectionKey.title");
    $triggerLabel = __("help.trigger_label", ['section' => $title]);
    $loadingText = __("help.modal.loading");
    $errorText = __("help.modal.error");
    $closeText = __("help.modal.close");
@endphp

@once
    @push('css')
        <style>
            .help-icon {
                display: inline-flex;
                align-items: center;
            }

            .help-icon-button {
                align-items: center;
                background: transparent;
                border: none;
                border-radius: 50%;
                color: var(--primary-color, #4f46e5);
                cursor: pointer;
                display: inline-flex;
                font-size: 1.25rem;
                height: 2.25rem;
                justify-content: center;
                transition: transform 0.2s ease, color 0.2s ease;
                width: 2.25rem;
            }

            .help-icon-button:focus,
            .help-icon-button:hover {
                color: var(--primary-color, #4f46e5);
                transform: scale(1.1);
                outline: none;
            }

            .help-modal-overlay {
                align-items: center;
                background-color: rgba(15, 23, 42, 0.55);
                display: none;
                inset: 0;
                justify-content: center;
                padding: 1.5rem;
                position: fixed;
                z-index: 9999;
            }

            .help-modal-overlay.is-visible {
                display: flex;
            }

            .help-modal {
                background: #ffffff;
                border-radius: 0.75rem;
                box-shadow: 0 10px 40px rgba(15, 23, 42, 0.25);
                max-height: 90vh;
                max-width: 36rem;
                overflow: hidden;
                width: 100%;
            }

            .help-modal__header {
                align-items: center;
                border-bottom: 1px solid rgba(148, 163, 184, 0.35);
                display: flex;
                justify-content: space-between;
                padding: 1rem 1.25rem;
            }

            .help-modal__title {
                font-size: 1.125rem;
                font-weight: 600;
                margin: 0;
            }

            .help-modal__close {
                background: transparent;
                border: none;
                color: #64748b;
                cursor: pointer;
                font-size: 1.5rem;
                line-height: 1;
                padding: 0.25rem;
            }

            .help-modal__close:focus,
            .help-modal__close:hover {
                color: var(--primary-color, #4f46e5);
                outline: none;
            }

            .help-modal__body {
                max-height: calc(90vh - 4rem);
                overflow-y: auto;
                padding: 1.25rem;
            }

            .help-modal__message {
                color: #475569;
                font-size: 0.95rem;
                margin-bottom: 1rem;
            }

            .help-modal__content p {
                color: #1e293b;
                line-height: 1.6;
            }

            .help-modal__video {
                aspect-ratio: 16 / 9;
                border: none;
                border-radius: 0.5rem;
                margin-top: 1rem;
                width: 100%;
            }
        </style>
    @endpush
@endonce

<span class="help-icon" data-help-section="{{ $sectionKey }}" data-help-title="{{ $title }}" data-help-loading="{{ $loadingText }}" data-help-error="{{ $errorText }}" data-help-close="{{ $closeText }}">
    <button type="button" class="help-icon-button" title="{{ $triggerLabel }}" aria-label="{{ $triggerLabel }}">
        <i class="fas fa-question-circle"></i>
    </button>
</span>
