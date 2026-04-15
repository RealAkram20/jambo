{{-- Shared picker wiring: preview sync + Browse button delegation. --}}
<script>
    (function () {
        // Live image preview sync
        document.querySelectorAll('[data-media-url]').forEach(function (input) {
            input.addEventListener('input', function () {
                const key = input.getAttribute('data-media-url');
                const preview = document.querySelector('[data-media-preview="' + key + '"]');
                if (!preview) return;
                const val = input.value.trim();
                if (val) { preview.src = val; preview.style.opacity = '1'; }
            });
        });

        // File Manager browse buttons
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-media-browse]');
            if (!btn) return;
            e.preventDefault();
            const target = btn.getAttribute('data-media-browse');
            const acceptAttr = btn.getAttribute('data-media-accept') || '';
            const previewKey = btn.getAttribute('data-media-preview-target');
            const opts = {
                target: target,
                accept: acceptAttr ? acceptAttr.split(',').map(s => s.trim()).filter(Boolean) : [],
            };
            if (previewKey) opts.preview = '[data-media-preview="' + previewKey + '"]';
            if (window.JamboMediaPicker) JamboMediaPicker.open(opts);
        });
    })();
</script>
