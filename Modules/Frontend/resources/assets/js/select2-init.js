export function initSelect2() {
    // Add a small delay to ensure everything is loaded
    setTimeout(() => {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
            console.error('Select2 initialization error: jQuery or Select2 not loaded');
            return;
        }

        try {
        // Initialize select2 instances with default configuration
        jQuery(".select2-basic-single").select2({
            width: '100%'
        });

        // Initialize remaining select2 instances
        jQuery(".select2").each(function() {
            if (!jQuery(this).data('select2')) {
                jQuery(this).select2({
                    width: '100%'
                });
            }
        });
        
    } catch (error) {
        console.error('Select2 initialization error:', error);
    }

    }, 100); // Small delay to ensure DOM and dependencies are ready
}