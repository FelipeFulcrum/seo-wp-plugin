jQuery(document).ready(function($) {
    'use strict';
    
    console.log('SEO Optimizer admin script loaded');
    console.log('seoOptimizer object:', typeof seoOptimizer !== 'undefined' ? seoOptimizer : 'undefined');
    
    // Check if seoOptimizer object is available
    if (typeof seoOptimizer === 'undefined') {
        console.error('seoOptimizer object not found - AJAX will not work, falling back to PHP form submission');
        return; // Let the form submit normally
    }
    
    // Handle form submission
    $('#post-fields-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted, preventing default action');
        
        var postId = $('#post_id').val();
        console.log('Post ID:', postId);
        
        if (!postId || postId < 1) {
            alert('Please enter a valid post ID.');
            return false;
        }
        
        // Hide any existing PHP results
        $('.seo-optimizer-results').not('#results').hide();
        
        // Show loading
        $('#loading').show();
        $('#results').hide();
        
        // Make AJAX request
        $.ajax({
            url: seoOptimizer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_post_fields',
                post_id: postId,
                nonce: seoOptimizer.nonce
            },
            success: function(response) {
                $('#loading').hide();
                
                if (response.success) {
                    displayResults(response.data);
                } else {
                    displayError(response.data || 'An error occurred while fetching post fields.');
                }
            },
            error: function(xhr, status, error) {
                $('#loading').hide();
                displayError('AJAX Error: ' + error);
            }
        });
    });
    
    // Display results
    function displayResults(data) {
        var html = '<h2>Post Fields Results</h2>';
        
        // Loop through each section
        $.each(data, function(sectionKey, section) {
            if (section.title && section.fields) {
                html += '<div class="seo-optimizer-section">';
                html += '<h3>' + escapeHtml(section.title) + '</h3>';
                html += '<div class="seo-optimizer-fields">';
                
                // Display fields in a table
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Field Name</th><th>Value</th></tr></thead>';
                html += '<tbody>';
                
                $.each(section.fields, function(fieldName, fieldValue) {
                    html += '<tr>';
                    html += '<td><strong>' + escapeHtml(fieldName) + '</strong></td>';
                    html += '<td>' + formatFieldValue(fieldValue) + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
                html += '</div>';
            }
        });
        
        $('#results').html(html).show();
    }
    
    // Display error message
    function displayError(message) {
        var html = '<div class="notice notice-error"><p><strong>Error:</strong> ' + escapeHtml(message) + '</p></div>';
        $('#results').html(html).show();
    }
    
    // Format field value for display
    function formatFieldValue(value) {
        if (value === null || value === undefined) {
            return '<em>null</em>';
        }
        
        if (value === '') {
            return '<em>empty</em>';
        }
        
        if (typeof value === 'boolean') {
            return value ? '<span class="seo-optimizer-boolean-true">true</span>' : '<span class="seo-optimizer-boolean-false">false</span>';
        }
        
        if (typeof value === 'object') {
            // Handle arrays and objects
            try {
                var jsonString = JSON.stringify(value, null, 2);
                return '<pre class="seo-optimizer-json">' + escapeHtml(jsonString) + '</pre>';
            } catch (e) {
                return '<em>Complex object (cannot display)</em>';
            }
        }
        
        // Handle URLs
        var urlPattern = /^https?:\/\/.+/i;
        if (typeof value === 'string' && urlPattern.test(value)) {
            if (value.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
                // Image URL
                return '<a href="' + escapeHtml(value) + '" target="_blank">' + escapeHtml(value) + '</a><br>' +
                       '<img src="' + escapeHtml(value) + '" alt="Image" style="max-width: 200px; max-height: 150px; margin-top: 5px;">';
            } else {
                // Regular URL
                return '<a href="' + escapeHtml(value) + '" target="_blank">' + escapeHtml(value) + '</a>';
            }
        }
        
        // Handle long text
        var stringValue = String(value);
        if (stringValue.length > 200) {
            var truncated = stringValue.substring(0, 200) + '...';
            return '<span class="seo-optimizer-truncated">' + escapeHtml(truncated) + '</span>' +
                   '<a href="#" class="seo-optimizer-show-full" data-full-text="' + escapeHtml(stringValue) + '"> Show Full</a>';
        }
        
        return escapeHtml(stringValue);
    }
    
    // Handle "Show Full" links
    $(document).on('click', '.seo-optimizer-show-full', function(e) {
        e.preventDefault();
        var fullText = $(this).data('full-text');
        var $truncated = $(this).prev('.seo-optimizer-truncated');
        
        if ($(this).text() === ' Show Full') {
            $truncated.html(escapeHtml(fullText));
            $(this).text(' Show Less');
        } else {
            var truncated = fullText.substring(0, 200) + '...';
            $truncated.html(escapeHtml(truncated));
            $(this).text(' Show Full');
        }
    });
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Auto-focus on post ID input
    $('#post_id').focus();
});
