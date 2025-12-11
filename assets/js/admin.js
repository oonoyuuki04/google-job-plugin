/**
 * Admin JavaScript for Google Job Posting Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Placeholder for future admin JavaScript functionality

        // Example: Auto-format salary fields
        $('input[name="gjp_salary_min"], input[name="gjp_salary_max"]').on('blur', function() {
            var value = $(this).val();
            if (value) {
                // Remove non-numeric characters
                value = value.replace(/[^0-9]/g, '');
                $(this).val(value);
            }
        });

        // Example: Auto-format postal code
        $('input[name="gjp_postal_code"]').on('blur', function() {
            var value = $(this).val();
            if (value) {
                // Remove hyphens and spaces
                value = value.replace(/[-\s]/g, '');
                // Add hyphen if appropriate (e.g., 1000001 -> 100-0001)
                if (value.length === 7) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                }
                $(this).val(value);
            }
        });
    });

})(jQuery);
