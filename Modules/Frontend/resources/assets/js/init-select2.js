export function initSelect2() {
    // Add a small delay to ensure everything is loaded
    setTimeout(() => {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            console.error('Select2 initialization error: jQuery or Select2 not loaded');
            return;
        }

        try {
            // Initialize select2 instances with default configuration
            window.jQuery(".select2-basic-single").select2({
                width: '100%',
                theme: 'bootstrap-5'
            });

            // Initialize remaining select2 instances
            window.jQuery(".select2").each(function() {
                if (!window.jQuery(this).data('select2')) {
                    window.jQuery(this).select2({
                        width: '100%',
                        theme: 'bootstrap-5'
                    });
                }
            });
            
        } catch (error) {
            console.error('Select2 initialization error:', error);
        }
    }, 100); // Small delay to ensure DOM and dependencies are ready
}