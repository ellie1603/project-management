/**
 * Alpine component behind the "Update Project Progress" form.
 *
 * Built for personnel on a phone at the site: pick the stage status with one tap,
 * snap photos with the camera, write one short note, and optionally let the AI
 * assistant turn that note (plus the photos) into a clear report for the admin.
 */

// Keep in sync with ProjectController::MAX_PROGRESS_ATTACHMENTS / MAX_PROGRESS_ATTACHMENT_KB.
const MAX_ATTACHMENTS = 2;
const MAX_FILE_BYTES = 5 * 1024 * 1024;
const MAX_AI_PHOTOS = 2;
const MAX_AI_PHOTO_BYTES = 5 * 1024 * 1024;
const MAX_IMAGE_EDGE = 1600;
const JPEG_QUALITY = 0.75;

/**
 * Phone cameras produce 4-12 MB photos. Downscale to a 1600px JPEG (usually
 * 200-400 KB) so uploads finish on mobile data and storage stays small. Formats
 * the browser can't decode (e.g. HEIC outside Safari) are sent as-is.
 */
async function compressImage(file) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || typeof createImageBitmap !== 'function') {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, MAX_IMAGE_EDGE / Math.max(bitmap.width, bitmap.height));

        if (scale === 1 && file.size < 400 * 1024) {
            bitmap.close();

            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);

        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff'; // transparent PNG areas would otherwise turn black
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY));

        if (!blob || blob.size >= file.size) {
            return file;
        }

        return new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.jpg`, { type: 'image/jpeg', lastModified: file.lastModified });
    } catch {
        return file;
    }
}

const formatBytes = (bytes) => (bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);

const cameraErrorMessage = (error) => {
    switch (error?.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'Camera access is blocked. Click the camera icon in the address bar, choose Allow, then try again.';
        case 'NotFoundError':
        case 'OverconstrainedError':
            return 'No camera was found on this device. Use Upload files instead.';
        case 'NotReadableError':
            return 'The camera is being used by another app. Close it and try again.';
        default:
            return 'Could not start the camera. Use Upload files instead.';
    }
};

export default (config) => {
    // The live MediaStream stays outside Alpine's reactive state: a proxied
    // stream cannot be assigned to <video>.srcObject.
    let stream = null;
    let videoEl = null;

    return {
        phases: config.phases,
        phaseId: config.phaseId,
        phaseStatus: config.phaseStatus,
        currentCompletion: config.currentCompletion,
        changingStage: false,

        date: config.date,
        today: config.today,
        changingDate: config.date !== config.today,

        notes: config.notes ?? '',
        draft: null,
        aiEnabled: config.aiEnabled,
        aiLoading: false,
        aiError: '',

        attachments: [],
        processing: 0,
        fileError: '',
        maxAttachments: MAX_ATTACHMENTS,

        cameraOpen: false,
        cameraError: '',
        cameraCount: 0,
        facingMode: 'environment',
        shotsTaken: 0,
        flash: false,

        issueOptions: ['Rain / weather', 'Materials delayed', 'Short on workers', 'Equipment problem', 'Permit / inspection'],
        issueTags: [],
        issueText: config.issues ?? '',
        hasIssue: Boolean(config.issues),

        submitting: false,
        formError: '',

        get phase() {
            return this.phases.find((p) => p.id === this.phaseId) ?? null;
        },

        get newCompletion() {
            if (!this.phase) {
                return this.currentCompletion;
            }
            const completed = this.phaseStatus === 'Completed' ? this.phase.sequence : this.phase.sequence - 1;
            // Completion never goes backwards from a routine "still working" update.
            return Math.max(this.currentCompletion, Math.round((Math.max(0, completed) / this.phases.length) * 100));
        },

        get dateLabel() {
            if (this.date === this.today) {
                return 'Today';
            }
            const parsed = new Date(`${this.date}T00:00:00`);

            return Number.isNaN(parsed.getTime()) ? this.date : parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        },

        get accomplishments() {
            return (this.draft ? this.draft.accomplishments : this.notes).trim();
        },

        get issuesValue() {
            if (!this.hasIssue) {
                return '';
            }
            const tags = this.issueTags.join(', ');
            const text = this.issueText.trim();

            return tags && text ? `${tags}: ${text}` : tags || text;
        },

        get photoCount() {
            return this.attachments.filter((a) => a.isImage).length;
        },

        get isFull() {
            return this.attachments.length + this.processing >= MAX_ATTACHMENTS;
        },

        toggleIssueTag(tag) {
            this.issueTags = this.issueTags.includes(tag) ? this.issueTags.filter((t) => t !== tag) : [...this.issueTags, tag];
        },

        async addFiles(fileList) {
            this.fileError = '';

            for (const original of Array.from(fileList ?? [])) {
                if (this.attachments.length >= MAX_ATTACHMENTS) {
                    this.fileError = `You can attach up to ${MAX_ATTACHMENTS} files per update.`;
                    break;
                }

                this.processing++;
                try {
                    const file = original.type.startsWith('image/') ? await compressImage(original) : original;

                    if (file.size > MAX_FILE_BYTES) {
                        this.fileError = `${original.name} is larger than ${MAX_FILE_BYTES / 1024 / 1024} MB.`;
                        continue;
                    }

                    const isImage = file.type.startsWith('image/');
                    this.attachments.push({
                        key: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
                        file,
                        isImage,
                        name: file.name,
                        size: formatBytes(file.size),
                        url: isImage ? URL.createObjectURL(file) : null,
                    });
                } finally {
                    this.processing--;
                }
            }

            this.syncFiles();
        },

        removeAttachment(key) {
            const attachment = this.attachments.find((a) => a.key === key);
            if (attachment?.url) {
                URL.revokeObjectURL(attachment.url);
            }
            this.attachments = this.attachments.filter((a) => a.key !== key);
            this.syncFiles();
        },

        // The visible camera/upload pickers are reset after each pick so the same
        // photo can be retaken; the hidden `files[]` input carries the full list.
        syncFiles() {
            const transfer = new DataTransfer();
            this.attachments.forEach((a) => transfer.items.add(a.file));
            this.$refs.files.files = transfer.files;
        },

        async assist() {
            this.aiError = '';

            if (this.notes.trim().length < 3) {
                this.aiError = 'Write a few words about what was done first, then tap the AI button.';
                return;
            }

            this.aiLoading = true;

            const body = new FormData();
            body.append('phase_id', this.phaseId);
            body.append('phase_status', this.phaseStatus);
            body.append('notes', this.notes.trim());
            this.attachments
                .filter((a) => /^image\/(jpeg|png|webp|gif)$/.test(a.file.type) && a.file.size <= MAX_AI_PHOTO_BYTES)
                .slice(0, MAX_AI_PHOTOS)
                .forEach((a) => body.append('photos[]', a.file));

            try {
                const response = await fetch(config.assistUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body,
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.aiError = response.status === 429
                        ? 'Too many AI requests. Wait a minute and try again.'
                        : payload.message ?? 'The AI assistant is unavailable right now. You can still save your own words.';
                    return;
                }

                this.draft = {
                    accomplishments: payload.draft.accomplishments,
                    activities_completed: payload.draft.activities_completed,
                    activities_remaining: payload.draft.activities_remaining,
                };

                if (payload.draft.issues) {
                    this.hasIssue = true;
                    this.issueText = payload.draft.issues;
                }
            } catch {
                this.aiError = 'Could not reach the server. Check your connection and try again.';
            } finally {
                this.aiLoading = false;
            }
        },

        discardDraft() {
            this.draft = null;
        },

        submit(event) {
            this.formError = '';

            if (this.processing > 0) {
                event.preventDefault();
                this.formError = 'Still preparing your photos. Try again in a moment.';
                return;
            }

            if (this.accomplishments.length === 0) {
                event.preventDefault();
                this.formError = 'Write a short note about what was done.';
                this.$refs.notes?.focus();
                return;
            }

            this.syncFiles();
            this.submitting = true;
        },

        /**
         * Phones open their own camera app (native `capture` input), which gives
         * focus, flash and zoom for free. Laptops and desktops get an in-page
         * webcam view instead, because they ignore `capture` and show a file picker.
         */
        async takePhoto() {
            this.fileError = '';
            const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;

            if (isTouchDevice || !navigator.mediaDevices?.getUserMedia) {
                if (!isTouchDevice && !window.isSecureContext) {
                    this.fileError = 'The live camera only works on a secure (https) address. Choose a photo file for now.';
                }
                this.$refs.cameraInput.click();
                return;
            }

            this.shotsTaken = 0;
            this.cameraOpen = true;
            await this.startCamera();
        },

        // The camera view is teleported to <body>, so it hands us its <video> element.
        registerVideo(element) {
            videoEl = element;
        },

        async startCamera() {
            this.stopStream();
            this.cameraError = '';

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.facingMode, width: { ideal: 1920 }, height: { ideal: 1080 } },
                    audio: false,
                });
                videoEl.srcObject = stream;
                await videoEl.play();

                const devices = await navigator.mediaDevices.enumerateDevices();
                this.cameraCount = devices.filter((device) => device.kind === 'videoinput').length;
            } catch (error) {
                this.cameraError = cameraErrorMessage(error);
            }
        },

        async switchCamera() {
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
            await this.startCamera();
        },

        async capturePhoto() {
            const video = videoEl;

            if (!stream || !video.videoWidth) {
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
            if (!blob) {
                this.cameraError = 'Could not capture the photo. Try again.';
                return;
            }

            this.flash = true;
            window.setTimeout(() => (this.flash = false), 150);

            const stamp = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14);
            await this.addFiles([new File([blob], `site-photo-${stamp}.jpg`, { type: 'image/jpeg' })]);
            this.shotsTaken++;

            if (this.attachments.length >= MAX_ATTACHMENTS) {
                this.closeCamera();
            }
        },

        closeCamera() {
            this.stopStream();
            this.cameraOpen = false;
        },

        stopStream() {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;

            if (videoEl) {
                videoEl.srcObject = null;
            }
        },

        // Turbo navigation removes the component; make sure the webcam light goes off.
        destroy() {
            this.stopStream();
        },
    };
};
