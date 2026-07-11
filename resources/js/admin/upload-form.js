/**
 * Submits a form via XHR so large file uploads (electronic PDFs up to ~950 MB)
 * show real progress — otherwise a slow upload looks like a frozen page.
 *
 * When no file is chosen the form submits normally (no need to buffer it).
 * On success we follow the server redirect; on a 422 we surface the validation
 * messages inline without losing what the user typed.
 */
export default () => ({
    uploading: false,
    progress: 0,
    progressText: '',
    processing: false, // upload finished, server still saving the file
    uploadErrors: [],

    submitUpload(event) {
        const form = event.target;

        const hasFile = Array.from(form.querySelectorAll('input[type="file"]'))
            .some((input) => input.files && input.files.length > 0);

        if (!hasFile) {
            return; // let the browser submit it the normal way
        }

        event.preventDefault();

        const xhr = new XMLHttpRequest();
        xhr.open(form.getAttribute('method') || 'POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        this.uploadErrors = [];
        this.uploading = true;
        this.processing = false;
        this.progress = 0;
        this.progressText = '';

        xhr.upload.addEventListener('progress', (e) => {
            if (!e.lengthComputable) return;

            this.progress = Math.round((e.loaded / e.total) * 100);
            this.progressText = `${formatSize(e.loaded)} / ${formatSize(e.total)}`;

            // Bytes are all sent; the server is now storing/validating the file.
            if (this.progress >= 100) {
                this.processing = true;
            }
        });

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                // Followed the redirect to the target page — go there.
                window.location.href = xhr.responseURL || form.action;
                return;
            }

            this.uploading = false;
            this.processing = false;
            this.uploadErrors = extractErrors(xhr);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        xhr.addEventListener('error', () => {
            this.uploading = false;
            this.processing = false;
            this.uploadErrors = [form.dataset.uploadError || 'Faylni yuklashda xatolik yuz berdi.'];
        });

        xhr.send(new FormData(form));
    },
});

function formatSize(bytes) {
    if (bytes >= 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    if (bytes >= 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${bytes} B`;
}

function extractErrors(xhr) {
    if (xhr.status === 422) {
        try {
            const body = JSON.parse(xhr.responseText);
            const messages = Object.values(body.errors || {}).flat();
            if (messages.length) return messages;
            if (body.message) return [body.message];
        } catch (e) {
            // fall through to the generic message
        }
    }

    return [`Serverda xatolik (${xhr.status}). Qayta urinib ko‘ring.`];
}
