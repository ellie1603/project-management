/**
 * Full-screen photo viewer shared by every page. Open it from anywhere with
 *   $dispatch('view-photos', { photos: [{ src, caption, download }], index })
 * where `download` (optional) links to the original file.
 *
 * Tap the photo to zoom in at that spot and tap again to fit; arrows, arrow
 * keys and swipes move between photos; Esc, the close button or a tap on the
 * dark background closes it.
 */
export default () => ({
    isOpen: false,
    photos: [],
    index: 0,
    zoomed: false,
    zoomWidth: 0,
    loading: false,
    lockedScroll: false,
    touchStartX: null,

    get current() {
        return this.photos[this.index] ?? { src: '', caption: '' };
    },

    get hasMany() {
        return this.photos.length > 1;
    },

    open({ photos = [], index = 0 } = {}) {
        if (photos.length === 0) {
            return;
        }

        this.photos = photos;
        this.show(index);
        this.isOpen = true;

        // A modal underneath may already hold the scroll lock; only release what we took.
        this.lockedScroll = !document.body.classList.contains('overflow-y-hidden');
        document.body.classList.add('overflow-y-hidden');
        this.$nextTick(() => this.$refs.close?.focus());
    },

    close() {
        this.isOpen = false;
        this.zoomed = false;

        if (this.lockedScroll) {
            document.body.classList.remove('overflow-y-hidden');
        }
    },

    show(index) {
        this.index = Math.min(Math.max(0, index), this.photos.length - 1);
        this.zoomed = false;

        // Cached or unchanged photos are complete at once and never fire `load`.
        this.loading = true;
        this.$nextTick(() => {
            if (this.$refs.image.complete) {
                this.loading = false;
            }
        });
    },

    next() {
        if (this.index < this.photos.length - 1) {
            this.show(this.index + 1);
        }
    },

    prev() {
        if (this.index > 0) {
            this.show(this.index - 1);
        }
    },

    toggleZoom(event) {
        const image = this.$refs.image;

        if (this.zoomed) {
            this.zoomed = false;
            return;
        }

        // Zoom to the photo's real size, or at least double the fitted size for small photos.
        const rect = image.getBoundingClientRect();
        const ratioX = (event.clientX - rect.left) / rect.width;
        const ratioY = (event.clientY - rect.top) / rect.height;

        this.zoomWidth = Math.max(image.naturalWidth, rect.width * 2);
        this.zoomed = true;

        // Keep the tapped spot under the finger once the larger image is laid out.
        this.$nextTick(() => {
            const stage = this.$refs.stage;
            stage.scrollLeft = image.offsetWidth * ratioX - stage.clientWidth / 2;
            stage.scrollTop = image.offsetHeight * ratioY - stage.clientHeight / 2;
        });
    },

    /**
     * Listens on window in the capture phase so that, while the viewer is open,
     * Esc closes only the viewer and not a modal sitting underneath it.
     */
    onKey(event) {
        if (!this.isOpen) {
            return;
        }

        const actions = {
            Escape: () => this.close(),
            ArrowRight: () => this.next(),
            ArrowLeft: () => this.prev(),
        };

        if (actions[event.key]) {
            event.preventDefault();
            event.stopPropagation();
            actions[event.key]();
        }
    },

    touchStart(event) {
        this.touchStartX = this.zoomed ? null : event.changedTouches[0].clientX;
    },

    touchEnd(event) {
        if (this.touchStartX === null) {
            return;
        }

        const distance = event.changedTouches[0].clientX - this.touchStartX;
        this.touchStartX = null;

        if (Math.abs(distance) > 50) {
            distance < 0 ? this.next() : this.prev();
        }
    },
});
