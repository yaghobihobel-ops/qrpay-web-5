import axios from 'axios';

class HelpIcon {
    constructor() {
        this.icons = Array.from(document.querySelectorAll('[data-help-section]'));
        if (this.icons.length === 0) {
            return;
        }

        this.overlay = null;
        this.modal = null;
        this.titleElement = null;
        this.messageElement = null;
        this.contentElement = null;
        this.videoFrame = null;
        this.closeButton = null;

        this.icons.forEach((icon) => {
            const button = icon.querySelector('button');
            if (!button) {
                return;
            }

            button.addEventListener('click', (event) => {
                event.preventDefault();
                this.openForIcon(icon);
            });
        });
    }

    ensureModal() {
        if (this.overlay) {
            return;
        }

        this.overlay = document.createElement('div');
        this.overlay.className = 'help-modal-overlay';
        this.overlay.setAttribute('role', 'presentation');

        this.modal = document.createElement('div');
        this.modal.className = 'help-modal';
        this.modal.setAttribute('role', 'dialog');
        this.modal.setAttribute('aria-modal', 'true');

        const header = document.createElement('div');
        header.className = 'help-modal__header';

        this.titleElement = document.createElement('h2');
        this.titleElement.className = 'help-modal__title';
        this.titleElement.textContent = '';

        this.closeButton = document.createElement('button');
        this.closeButton.type = 'button';
        this.closeButton.className = 'help-modal__close';
        this.closeButton.innerHTML = '&times;';
        this.closeButton.addEventListener('click', () => this.close());

        header.append(this.titleElement, this.closeButton);

        const body = document.createElement('div');
        body.className = 'help-modal__body';

        this.messageElement = document.createElement('div');
        this.messageElement.className = 'help-modal__message';

        this.contentElement = document.createElement('div');
        this.contentElement.className = 'help-modal__content';

        this.videoFrame = document.createElement('iframe');
        this.videoFrame.className = 'help-modal__video';
        this.videoFrame.setAttribute('allowfullscreen', 'true');
        this.videoFrame.style.display = 'none';

        body.append(this.messageElement, this.contentElement, this.videoFrame);

        this.modal.append(header, body);
        this.overlay.append(this.modal);

        this.overlay.addEventListener('click', (event) => {
            if (event.target === this.overlay) {
                this.close();
            }
        });

        document.body.appendChild(this.overlay);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.close();
            }
        });
    }

    openForIcon(icon) {
        this.ensureModal();

        const { helpSection, helpTitle, helpLoading, helpError, helpClose } = icon.dataset;

        if (helpClose && this.closeButton) {
            this.closeButton.setAttribute('aria-label', helpClose);
            this.closeButton.setAttribute('title', helpClose);
        }

        if (this.modal) {
            this.modal.setAttribute('aria-label', helpTitle || 'Help');
        }

        this.showModal();
        this.setLoadingState(helpTitle, helpLoading);

        axios
            .get(`/help/${helpSection}`)
            .then((response) => {
                const data = response?.data?.data;
                if (!data) {
                    throw new Error('Invalid response');
                }
                this.renderContent(helpTitle, data, helpError);
            })
            .catch(() => {
                this.setErrorState(helpTitle, helpError);
            });
    }

    showModal() {
        if (this.overlay) {
            this.overlay.classList.add('is-visible');
        }
    }

    close() {
        if (this.overlay) {
            this.overlay.classList.remove('is-visible');
        }
        if (this.videoFrame) {
            this.videoFrame.src = '';
            this.videoFrame.style.display = 'none';
        }
    }

    setLoadingState(title, loadingText) {
        if (this.titleElement) {
            this.titleElement.textContent = title || '';
        }
        if (this.messageElement) {
            this.messageElement.textContent = loadingText || '';
        }
        if (this.contentElement) {
            this.contentElement.innerHTML = '';
        }
        if (this.videoFrame) {
            this.videoFrame.src = '';
            this.videoFrame.style.display = 'none';
        }
    }

    setErrorState(title, errorText) {
        if (this.titleElement) {
            this.titleElement.textContent = title || '';
        }
        if (this.messageElement) {
            this.messageElement.textContent = errorText || '';
        }
        if (this.contentElement) {
            this.contentElement.innerHTML = '';
        }
        if (this.videoFrame) {
            this.videoFrame.src = '';
            this.videoFrame.style.display = 'none';
        }
    }

    renderContent(title, data, errorText) {
        if (!data) {
            this.setErrorState(title, errorText);
            return;
        }

        if (this.titleElement) {
            this.titleElement.textContent = data.title || title || '';
        }

        if (this.messageElement) {
            this.messageElement.textContent = data.summary || '';
        }

        if (this.contentElement) {
            this.contentElement.innerHTML = data.content || '';
        }

        if (this.videoFrame) {
            if (data.video) {
                this.videoFrame.src = data.video;
                this.videoFrame.style.display = 'block';
            } else {
                this.videoFrame.src = '';
                this.videoFrame.style.display = 'none';
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new HelpIcon();
});
