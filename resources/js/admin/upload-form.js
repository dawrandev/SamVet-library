/**
 * Submits a form via XHR so large file uploads (electronic PDFs/videos up to
 * ~950 MB) show real progress — otherwise a slow upload looks like a frozen
 * page. Files above CHUNK_THRESHOLD go through a chunked-upload pre-pass
 * first (small sequential POSTs, so a single huge request never hits a
 * shared host's upload_max_filesize/post_max_size — see
 * App\Services\ChunkedUploadService) before the (now small) form itself
 * is submitted the same way as before.
 *
 * `config.chunked` maps a file input's `name` to the ChunkedUploadKind value
 * the backend expects for it, e.g. `{ electronic_file: 'pdf' }`. Fields not
 * listed there are never chunked, regardless of size.
 *
 * When no file is chosen the form submits normally (no need to buffer it).
 * On success we follow the server redirect; on a 422 we surface the validation
 * messages inline without losing what the user typed.
 */
const CHUNK_THRESHOLD = 20 * 1024 * 1024; // 20 MB — below this, upload as one request as before
const CHUNK_RETRIES = 3;

export default (config = {}) => ({
    uploading: false,
    progress: 0,
    progressText: '',
    processing: false, // upload finished, server still saving the file
    uploadErrors: [],

    async submitUpload(event) {
        const form = event.target;
        const chunked = config.chunked || {};

        const fileInputs = Array.from(form.querySelectorAll('input[type="file"]'))
            .filter((input) => input.files && input.files.length > 0);

        if (fileInputs.length === 0) {
            return; // let the browser submit it the normal way
        }

        event.preventDefault();

        this.uploadErrors = [];
        this.uploading = true;
        this.processing = false;
        this.progress = 0;
        this.progressText = '';

        const toChunk = fileInputs.filter((input) => chunked[input.name] && input.files[0].size > CHUNK_THRESHOLD);

        try {
            for (const input of toChunk) {
                const token = await this.chunkUpload(form, input.files[0], chunked[input.name]);
                this.injectHiddenToken(form, input.name, token);
                input.disabled = true; // excluded from FormData below — the token replaces it
            }
        } catch (e) {
            this.uploading = false;
            this.processing = false;
            this.uploadErrors = [e.message || form.dataset.uploadError || 'Faylni yuklashda xatolik yuz berdi.'];
            window.scrollTo({ top: 0, behavior: 'smooth' });
            toChunk.forEach((input) => { input.disabled = false; });
            return;
        }

        this.submitForm(form);
    },

    /** Uploads one file in CHUNK_SIZE pieces; resolves with the server's upload token. */
    async chunkUpload(form, file, kind) {
        const csrfToken = form.querySelector('input[name="_token"]').value;
        const { token, chunk_size: chunkSize, total_chunks: totalChunks } = await this.startChunkedUpload(csrfToken, file.name, file.size, kind);

        for (let index = 0; index < totalChunks; index++) {
            const start = index * chunkSize;
            const blob = file.slice(start, start + chunkSize);

            await this.sendChunkWithRetry(csrfToken, token, index, blob, start, file.size);
        }

        return token;
    },

    startChunkedUpload(csrfToken, filename, totalSize, kind) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/admin/uploads/start');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    reject(new Error(extractMessage(xhr)));
                }
            });
            xhr.addEventListener('error', () => reject(new Error('Yuklashni boshlab bo‘lmadi.')));
            xhr.send(JSON.stringify({ filename, total_size: totalSize, kind }));
        });
    },

    async sendChunkWithRetry(csrfToken, uploadToken, index, blob, bytesBefore, totalSize) {
        let attempt = 0;

        while (true) {
            try {
                await this.sendChunk(csrfToken, uploadToken, index, blob, bytesBefore, totalSize);
                return;
            } catch (e) {
                attempt++;
                if (attempt >= CHUNK_RETRIES) throw e;
                await sleep(500 * attempt); // small backoff before retrying the same chunk
            }
        }
    },

    sendChunk(csrfToken, uploadToken, index, blob, bytesBefore, totalSize) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `/admin/uploads/${uploadToken}/chunk`);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.upload.addEventListener('progress', (e) => {
                if (!e.lengthComputable) return;
                const loaded = bytesBefore + e.loaded;
                this.progress = Math.min(99, Math.round((loaded / totalSize) * 100));
                this.progressText = `${formatSize(loaded)} / ${formatSize(totalSize)}`;
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    reject(new Error(extractMessage(xhr)));
                }
            });
            xhr.addEventListener('error', () => reject(new Error('Bo‘lakni yuklab bo‘lmadi.')));

            const body = new FormData();
            body.append('index', index);
            body.append('file', blob);
            xhr.send(body);
        });
    },

    injectHiddenToken(form, fieldName, token) {
        let hidden = form.querySelector(`input[type="hidden"][name="${fieldName}_token"]`);
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `${fieldName}_token`;
            form.appendChild(hidden);
        }
        hidden.value = token;
    },

    /** The (now small, chunks already uploaded) rest of the form — same XHR/progress/redirect flow as before. */
    submitForm(form) {
        const xhr = new XMLHttpRequest();
        xhr.open(form.getAttribute('method') || 'POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

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

        this.processing = true; // chunking (if any) already finished — this leg is quick either way
        xhr.send(new FormData(form));
    },
});

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function formatSize(bytes) {
    if (bytes >= 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    if (bytes >= 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${bytes} B`;
}

function extractMessage(xhr) {
    const errors = extractErrors(xhr);
    return errors[0];
}

function extractErrors(xhr) {
    if (xhr.status === 422 || xhr.status === 409) {
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
