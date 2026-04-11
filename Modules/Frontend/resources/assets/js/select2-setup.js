export function setupSelect2() {
    // Use an async DOMContentLoaded handler so we can dynamically import Select2 if needed
    window.addEventListener('DOMContentLoaded', async () => {
        // Ensure jQuery is present
        if (!window.jQuery) {
            console.error('Required libraries missing: jQuery is not available');
            return;
        }

        // If Select2 plugin isn't attached to jQuery, try to dynamically import and attach it
        if (!window.jQuery.fn || !window.jQuery.fn.select2) {
            try {
                const mod = await import('select2');
                // select2 exports a function; depending on bundler it could be default or named
                const Select2 = (mod && (mod.default || mod));
                if (typeof Select2 === 'function') {
                    // Attach plugin to the jQuery instance
                    Select2(window.jQuery);
                }
            } catch (err) {
                // Dynamic import failed — probably missing package or load error
                console.error('Failed to dynamically import select2:', err);
            }
        }

        // Final check: if still missing, log and abort
        if (!window.jQuery.fn || !window.jQuery.fn.select2) {
            console.error('Required libraries missing');
            return;
        }

        // Initialize all Select2 elements
        try {
            window.jQuery('.select2-basic-single, .select2').select2({
                width: '100%'
            });
        } catch (initErr) {
            console.error('Select2 initialization failed:', initErr);
        }
    });
}