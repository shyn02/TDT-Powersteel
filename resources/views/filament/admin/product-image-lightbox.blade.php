{{--
    Shared click-to-enlarge overlay for product photos in /admin.

    Mounted ONCE via PanelsRenderHook::BODY_END (see AdminPanelProvider),
    outside Livewire so table/form re-renders never destroy its state.

    Behavior contract:
    - Opens centered on a dimmed overlay when any enlargeable thumbnail
      (see ProductImageLightbox::triggerAttributes()) is clicked, or
      activated via Enter/Space while focused.
    - The enlarged `src` is the thumbnail's own `src`, which Filament
      already resolves host-aware (alignPublicDiskUrlWithRequest), so this
      view follows MediaUploadRules::url() without duplicating it.
    - Empty/missing `src` never opens the overlay and never throws.
    - Closes via the X button, backdrop click, or Escape.
    - Locks background scroll while open, restores on close.
    - Moves focus to the close button on open, returns focus on close.
    - A broken full-size URL auto-closes instead of showing an error.

    No new dependencies: Alpine.js (bundled with Filament/Livewire) only.
--}}
<div
    id="tdt-image-lightbox"
    data-tdt-lightbox-overlay
    x-data="{
        open: false,
        src: '',
        alt: '',
        lastFocused: null,
        show(fullSrc, imageAlt) {
            fullSrc = (fullSrc || '').toString().trim();
            if (!fullSrc) return;
            this.lastFocused = document.activeElement;
            this.src = fullSrc;
            this.alt = (imageAlt || '').toString().trim() || 'Enlarged product image';
            this.open = true;
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            this.$nextTick(function () {
                var btn = document.querySelector('[data-tdt-lightbox-close]');
                if (btn) btn.focus();
            });
        },
        hide() {
            if (!this.open) return;
            this.open = false;
            this.src = '';
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            try {
                if (this.lastFocused && document.contains(this.lastFocused)) {
                    this.lastFocused.focus();
                }
            } catch (e) {}
            this.lastFocused = null;
        },
    }"
    x-on:tdt:open-lightbox.window="show($event.detail.src, $event.detail.alt)"
    x-on:keydown.escape.window="hide()"
>
    <div
        x-show="open"
        x-transition.opacity
        class="tdt-lightbox-backdrop"
        role="dialog"
        aria-modal="true"
        aria-label="Enlarged product image"
        x-on:click.self="hide()"
        style="display: none;"
    >
        <button
            type="button"
            data-tdt-lightbox-close
            x-on:click="hide()"
            class="tdt-lightbox-close"
            aria-label="Close enlarged image"
        ><span aria-hidden="true">&times;</span></button>
        <img
            x-bind:src="src"
            x-bind:alt="alt"
            x-on:click.stop
            x-on:error="hide()"
            class="tdt-lightbox-image"
        >
    </div>
</div>
<script>
(function () {
    // Guard against double-binding if the hook ever renders twice.
    if (window.__tdtLightboxInstalled) return;
    window.__tdtLightboxInstalled = true;

    var OPEN_EVENT = 'tdt:open-lightbox';
    var TRIGGER = '[data-tdt-lightbox]';
    // Scoped to the products admin pages so no other resource is affected.
    var PRODUCTS_PATH = '/admin/products';
    var THUMB_SELECTORS = [
        '[data-tdt-lightbox]',
        '.fi-ta-image img',
        '.fi-in-image img',
        '.fi-sc-image img',
        '.fi-fo-file-upload img',
        '.filepond--image-preview-wrapper img',
        '.filepond--image-preview img',
        '.filepond--image-preview-wrapper canvas',
    ].join(',');

    function isProductsPage() {
        return window.location.pathname.indexOf(PRODUCTS_PATH) === 0;
    }

    function imageSrc(img) {
        var raw = '';
        try {
            raw = img.currentSrc || img.getAttribute('src') || '';
        } catch (e) {
            raw = '';
        }
        return (raw || '').toString().trim();
    }

    function openFrom(img) {
        // Empty/missing image: no popup, no error.
        var src = imageSrc(img);
        if (!src) return false;
        var alt = '';
        try {
            alt = img.getAttribute('alt') || '';
        } catch (e) {}
        window.dispatchEvent(new CustomEvent(OPEN_EVENT, {
            detail: { src: src, alt: alt || 'Enlarged product image' },
        }));
        return true;
    }

    function findThumbnail(node) {
        if (!node || !node.closest) return null;
        // Never hijack clicks inside the overlay itself.
        if (node.closest('[data-tdt-lightbox-overlay]')) return null;
        return node.closest(THUMB_SELECTORS);
    }

    // Capture phase so enlarging wins over dropzone/row click handlers;
    // plain thumbnails have no competing behavior to break.
    document.addEventListener('click', function (event) {
        if (!isProductsPage()) return;
        var img = findThumbnail(event.target);
        if (!img) return;
        // Action buttons/links (e.g. FilePond remove) keep their behavior.
        if (event.target.closest('button, a')) return;
        if (openFrom(img)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    // Keyboard: Enter/Space on a focused enlargeable thumbnail.
    document.addEventListener('keydown', function (event) {
        if (!isProductsPage()) return;
        if (event.key !== 'Enter' && event.key !== ' ') return;
        var el = document.activeElement;
        if (!el || !el.matches || !el.matches(TRIGGER)) return;
        if (el.closest('[data-tdt-lightbox-overlay]')) return;
        event.preventDefault();
        openFrom(el);
    });
})();
</script>
