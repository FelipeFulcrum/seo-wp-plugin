<?php
/**
 * Plugin Name: SEO Optimizer - CMS Fields Viewer
 * Plugin URI: https://example.com/seo-optimizer
 * Description: A WordPress plugin that captures and displays all CMS fields from a post by post ID.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: seo-optimizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SEO_OPTIMIZER_VERSION', '1.0.0');
define('SEO_OPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEO_OPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main SEO Optimizer Class
 */
class SEO_Optimizer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_get_post_fields', array($this, 'ajax_get_post_fields'));
        add_action('wp_ajax_modify_post_content', array($this, 'ajax_modify_post_content'));
        add_action('wp_ajax_summarize_post_content', array($this, 'ajax_summarize_post_content'));
        add_action('wp_ajax_enhance_paragraph', array($this, 'ajax_enhance_paragraph'));
        add_action('wp_ajax_replace_paragraph', array($this, 'ajax_replace_paragraph'));
        add_action('wp_ajax_get_page_meta_data', array($this, 'ajax_get_page_meta_data'));
        add_action('wp_ajax_apply_meta_recommendation', array($this, 'ajax_apply_meta_recommendation'));
        // Add this new line:
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain('seo-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('SEO Optimizer', 'seo-optimizer'),
            __('SEO Optimizer', 'seo-optimizer'),
            'manage_options',
            'seo-optimizer',
            array($this, 'admin_page'),
            'dashicons-search',
            30
        );
        
        // Add submenu items
        add_submenu_page(
            'seo-optimizer',
            __('Dashboard', 'seo-optimizer'),
            __('Dashboard', 'seo-optimizer'),
            'manage_options',
            'seo-optimizer',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'seo-optimizer',
            __('Analysis', 'seo-optimizer'),
            __('Analysis', 'seo-optimizer'),
            'manage_options',
            'seo-optimizer-analysis',
            array($this, 'analysis_page')
        );
        
        add_submenu_page(
            'seo-optimizer',
            __('Implementation', 'seo-optimizer'),
            __('Implementation', 'seo-optimizer'),
            'manage_options',
            'seo-optimizer-implementation',
            array($this, 'implementation_page')
        );
        
        add_submenu_page(
            'seo-optimizer',
            __('Performance', 'seo-optimizer'),
            __('Performance', 'seo-optimizer'),
            'manage_options',
            'seo-optimizer-performance',
            array($this, 'performance_page')
        );
    }
    
    /**
     * Add meta boxes to post edit screens
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seo-optimizer-fields-viewer',
                __('CMS Fields Viewer', 'seo-optimizer'),
                array($this, 'meta_box_callback'),
                $post_type,
                'normal',
                'low'
            );
        }
    }

    /**
     * Meta box callback function
     */
    public function meta_box_callback($post) {
        // Add nonce for security
        wp_nonce_field('seo_optimizer_meta_box', 'seo_optimizer_meta_box_nonce');
        
        // Get all fields for this post
        $fields_data = $this->get_all_post_fields($post->ID);
        ?>
        <div class="seo-optimizer-meta-box">
            <p><?php _e('Below are all the CMS fields associated with this post:', 'seo-optimizer'); ?></p>
            
            <div class="seo-optimizer-toggle">
                <button type="button" id="toggle-seo-fields" class="button button-secondary">
                    <?php _e('Show CMS Fields', 'seo-optimizer'); ?>
                </button>
                <button type="button" id="modify-post-content" class="button button-primary" style="margin-left: 10px;">
                    <?php _e('Convert Content to Lowercase', 'seo-optimizer'); ?>
                </button>
                <button type="button" id="summarize-post-content" class="button button-secondary" style="margin-left: 10px;">
                    <?php _e('Summarize and Add to Bottom!', 'seo-optimizer'); ?>
                </button>
                <button type="button" id="toggle-enhance-paragraphs" class="button button-primary" style="margin-left: 10px;">
                    <?php _e('AI Enhance Paragraphs', 'seo-optimizer'); ?>
                </button>
            </div>
            
            <div id="seo-optimizer-fields-container" style="display: none; margin-top: 15px;">
                <?php if ($fields_data): ?>
                    <?php $this->display_results_html($fields_data); ?>
                <?php else: ?>
                    <p><em><?php _e('No fields found for this post.', 'seo-optimizer'); ?></em></p>
                <?php endif; ?>
            </div>
            
            <div id="seo-optimizer-paragraphs-container" style="display: none; margin-top: 15px;">
                <div class="paragraphs-loading" style="display: none;">
                    <p><?php _e('Loading paragraphs and enhancing with AI...', 'seo-optimizer'); ?></p>
                </div>
                <div id="paragraphs-table-container"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#toggle-seo-fields').on('click', function() {
                var container = $('#seo-optimizer-fields-container');
                if (container.is(':visible')) {
                    container.slideUp();
                    $(this).text('<?php _e('Show CMS Fields', 'seo-optimizer'); ?>');
                } else {
                    container.slideDown();
                    $(this).text('<?php _e('Hide CMS Fields', 'seo-optimizer'); ?>');
                }
            });
            
            $('#modify-post-content').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to convert the post content to lowercase? This action cannot be undone.', 'seo-optimizer'); ?>')) {
                    var button = $(this);
                    button.prop('disabled', true).text('<?php _e('Processing...', 'seo-optimizer'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'modify_post_content',
                            post_id: <?php echo $post->ID; ?>,
                            nonce: '<?php echo wp_create_nonce('seo_optimizer_modify_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php _e('Post content has been modified successfully!', 'seo-optimizer'); ?>');
                                location.reload();
                            } else {
                                alert('<?php _e('Error:', 'seo-optimizer'); ?> ' + response.data);
                            }
                        },
                        error: function() {
                            alert('<?php _e('An error occurred while modifying the post.', 'seo-optimizer'); ?>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Convert Content to Lowercase', 'seo-optimizer'); ?>');
                        }
                    });
                }
            });
            
            $('#summarize-post-content').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to summarize the post content and add it to the bottom? This action cannot be undone.', 'seo-optimizer'); ?>')) {
                    var button = $(this);
                    button.prop('disabled', true).text('<?php _e('Summarizing...', 'seo-optimizer'); ?>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'summarize_post_content',
                            post_id: <?php echo $post->ID; ?>,
                            nonce: '<?php echo wp_create_nonce('seo_optimizer_summarize_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('<?php _e('Post has been summarized and updated successfully!', 'seo-optimizer'); ?>');
                                location.reload();
                            } else {
                                alert('<?php _e('Error:', 'seo-optimizer'); ?> ' + response.data);
                            }
                        },
                        error: function() {
                            alert('<?php _e('An error occurred while summarizing the post.', 'seo-optimizer'); ?>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Summarize and Add to Bottom!', 'seo-optimizer'); ?>');
                        }
                    });
                }
            });
            
            $('#toggle-enhance-paragraphs').on('click', function() {
                var container = $('#seo-optimizer-paragraphs-container');
                var loading = $('.paragraphs-loading');
                var button = $(this);
                
                if (container.is(':visible')) {
                    container.slideUp();
                    button.text('<?php _e('AI Enhance Paragraphs', 'seo-optimizer'); ?>');
                } else {
                    container.slideDown();
                    button.text('<?php _e('Hide Enhanced Paragraphs', 'seo-optimizer'); ?>');
                    
                    // Load and enhance paragraphs
                    loading.show();
                    $('#paragraphs-table-container').empty();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'enhance_paragraph',
                            post_id: <?php echo $post->ID; ?>,
                            nonce: '<?php echo wp_create_nonce('seo_optimizer_enhance_nonce'); ?>'
                        },
                        success: function(response) {
                            loading.hide();
                            if (response.success && response.data.paragraphs) {
                                // Show debug info in console
                                console.group('🔍 SEO Optimizer - Paragraphs Loaded Successfully');
                                console.log('Post ID:', response.data.debug.post_id);
                                console.log('Content length:', response.data.debug.content_length);
                                console.log('Paragraphs found:', response.data.debug.paragraphs_found);
                                console.log('Content preview:', response.data.debug.content_preview);
                                
                                if (response.data.debug.paragraphs_preview && response.data.debug.paragraphs_preview.length > 0) {
                                    console.group('📝 First paragraphs:');
                                    response.data.debug.paragraphs_preview.forEach(function(p, index) {
                                        console.log('Paragraph ' + p.index + ' (length: ' + p.length + '):', p.preview);
                                    });
                                    console.groupEnd();
                                }
                                console.groupEnd();
                                
                                displayParagraphsTable(response.data.paragraphs);
                            } else {
                                console.error('❌ SEO Optimizer - Error loading paragraphs:', response.data);
                                if (response.data && response.data.debug) {
                                    console.group('🔍 Debug Info:');
                                    console.log('Post ID:', response.data.debug.post_id);
                                    console.log('Content length:', response.data.debug.content_length);
                                    console.log('Paragraphs found:', response.data.debug.paragraphs_found);
                                    console.log('Content preview:', response.data.debug.content_preview);
                                    console.groupEnd();
                                }
                                $('#paragraphs-table-container').html('<p class="error"><?php _e('Error loading paragraphs:', 'seo-optimizer'); ?> ' + (response.data || 'Unknown error') + '</p>');
                            }
                        },
                        error: function(xhr, status, error) {
                            loading.hide();
                            console.error('❌ SEO Optimizer - AJAX Error loading paragraphs:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            $('#paragraphs-table-container').html('<p class="error"><?php _e('An error occurred while loading paragraphs.', 'seo-optimizer'); ?></p>');
                        }
                    });
                }
            });
            
            function displayParagraphsTable(paragraphs) {
                var html = '<table class="wp-list-table widefat fixed striped paragraph-enhancement-table">';
                html += '<thead><tr><th><?php _e('Original Text', 'seo-optimizer'); ?></th><th><?php _e('AI Enhanced Text', 'seo-optimizer'); ?></th><th><?php _e('Action', 'seo-optimizer'); ?></th></tr></thead>';
                html += '<tbody>';
                
                paragraphs.forEach(function(paragraph, index) {
                    html += '<tr>';
                    html += '<td class="original-text">' + paragraph.original + '</td>';
                    html += '<td class="enhanced-text">' + paragraph.enhanced + '</td>';
                    html += '<td><button class="button button-small replace-paragraph-btn" data-index="' + index + '" data-original="' + escapeHtml(paragraph.original) + '" data-enhanced="' + escapeHtml(paragraph.enhanced) + '"><?php _e('Use Enhanced', 'seo-optimizer'); ?></button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#paragraphs-table-container').html(html);
                
                // Add click handlers for replace buttons
                $('.replace-paragraph-btn').on('click', function() {
                    var button = $(this);
                    var index = button.data('index');
                    var original = button.data('original');
                    var enhanced = button.data('enhanced');
                    
                    if (confirm('<?php _e('Are you sure you want to replace this paragraph with the AI enhanced version?', 'seo-optimizer'); ?>')) {
                        button.prop('disabled', true).text('<?php _e('Replacing...', 'seo-optimizer'); ?>');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'replace_paragraph',
                                post_id: <?php echo $post->ID; ?>,
                                original_text: original,
                                enhanced_text: enhanced,
                                nonce: '<?php echo wp_create_nonce('seo_optimizer_replace_nonce'); ?>'
                            },
                        success: function(response) {
                            if (response.success) {
                                var debugInfo = response.data.debug;
                                
                                // Show debug info in console
                                console.group('✅ SEO Optimizer - Paragraph Replaced Successfully');
                                
                                console.group('🧪 Strategy Tests:');
                                console.log('Original RAW:', debugInfo.original_raw);
                                console.log('Original Clean:', debugInfo.original_clean);
                                console.log('Enhanced Clean:', debugInfo.enhanced_clean);
                                console.log('Content length:', debugInfo.content_length);
                                console.log('Content snippet:', debugInfo.content_snippet);
                                
                                console.groupEnd();
                                
                                console.groupEnd();
                                //location.reload();
                            } else {
                                console.error('❌ SEO Optimizer - Error replacing paragraph:', response.data);
                                button.prop('disabled', false).text('<?php _e('Use Enhanced', 'seo-optimizer'); ?>');
                            }
                        },
                            error: function(xhr, status, error) {
                                console.error('❌ SEO Optimizer - AJAX Error replacing paragraph:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });
                                button.prop('disabled', false).text('<?php _e('Use Enhanced', 'seo-optimizer'); ?>');
                            }
                        });
                    }
                });
            }
            
            function escapeHtml(text) {
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Load on plugin admin pages (main and submenu pages)
        $seo_pages = array(
            'toplevel_page_seo-optimizer',
            'seo-optimizer_page_seo-optimizer-analysis',
            'seo-optimizer_page_seo-optimizer-implementation',
            'seo-optimizer_page_seo-optimizer-performance'
        );
        
        if (in_array($hook, $seo_pages)) {
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'seo-optimizer-admin',
                SEO_OPTIMIZER_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                SEO_OPTIMIZER_VERSION,
                true
            );
            
            wp_enqueue_style(
                'seo-optimizer-admin',
                SEO_OPTIMIZER_PLUGIN_URL . 'assets/admin.css',
                array(),
                SEO_OPTIMIZER_VERSION
            );
            
            // Localize script for AJAX
            wp_localize_script('seo-optimizer-admin', 'seoOptimizer', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seo_optimizer_nonce')
            ));
        }
        
        // Add this new section for post edit screens:
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_style(
                'seo-optimizer-meta-box',
                SEO_OPTIMIZER_PLUGIN_URL . 'assets/meta-box.css',
                array(),
                SEO_OPTIMIZER_VERSION
            );
        }
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        // Get all published posts and pages
        $pages = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>SEO Optimizer - Dashboard</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'blue-primary': '#3B82F6',
                                'blue-dark': '#1E40AF',
                                'orange-primary': '#F97316',
                                'green-success': '#10B981',
                                'purple-accent': '#8B5CF6'
                            }
                        }
                    }
                }
            </script>
        </head>
        <body class="bg-gray-50 min-h-screen">
            <!-- Top Navigation Bar -->
            <nav class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <!-- Logo -->
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <h1 class="text-2xl font-bold text-blue-primary">SEO Optimizer</h1>
                            </div>
                        </div>
                        
                        <!-- Navigation Items -->
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="#" class="bg-blue-primary text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="#" class="text-gray-700 hover:text-blue-primary px-3 py-2 rounded-md text-sm font-medium">Analysis</a>
                                <a href="#" class="text-gray-700 hover:text-blue-primary px-3 py-2 rounded-md text-sm font-medium">Implementation</a>
                                <a href="#" class="text-gray-700 hover:text-blue-primary px-3 py-2 rounded-md text-sm font-medium">Performance</a>
                            </div>
                        </div>
                        
                        <!-- User Profile -->
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">SEO Meta Data Management</h1>
                    <p class="text-gray-600 text-lg">Select a page or post to optimize its SEO meta data with AI-powered recommendations</p>
                </div>

                <!-- Page Selection -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Search Page or Post</h2>

                    <!-- Controls: search + dropdown select -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-4">
                        <input id="search-pages" type="text" placeholder="Search pages..." class="w-full sm:w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent" />
                        <select id="select-page" class="w-full sm:w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent">
                            <option value="">Select a page...</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>"><?php echo esc_html($page->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- List of pages with collapsible optimization panels -->
                    <div id="pages-list" class="divide-y divide-gray-200">
                        <?php foreach ($pages as $page): ?>
                            <div id="item-<?php echo $page->ID; ?>" data-title="<?php echo esc_attr(strtolower($page->post_title)); ?>" class="py-3">
                                <button type="button" onclick="togglePagePanel(<?php echo $page->ID; ?>)" class="w-full flex items-center justify-between text-left">
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-medium text-gray-900 truncate"><?php echo esc_html($page->post_title); ?></h3>
                                        <p class="text-xs text-gray-500 capitalize"><?php echo esc_html($page->post_type); ?></p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="panel-<?php echo $page->ID; ?>" class="hidden mt-3"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($pages)): ?>
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No pages found</h3>
                            <p class="mt-1 text-sm text-gray-500">No published pages or posts available.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Meta Data Management Interface (Hidden by default) -->
                <div id="meta-data-interface" class="hidden bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                    <!-- Page Header -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-primary rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 id="page-title" class="text-xl font-semibold text-gray-900"></h2>
                                <p id="page-url" class="text-sm text-gray-500"></p>
                            </div>
                        </div>
                        <button id="apply-all-btn" class="bg-green-success text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>Apply All</span>
                        </button>
                    </div>

                    <!-- Meta Data Sections -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Current Meta Data -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center space-x-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span>Current Meta Data</span>
                            </h3>
                            
                            <!-- Meta Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                                <input type="text" id="current-title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent" readonly>
                                <p id="current-title-length" class="text-sm text-gray-500 mt-1"></p>
                            </div>
                            
                            <!-- Meta Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                                <textarea id="current-description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent" readonly></textarea>
                                <p id="current-description-length" class="text-sm text-gray-500 mt-1"></p>
                            </div>
                            
                            <!-- Keywords -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Keywords</label>
                                <div id="current-keywords" class="min-h-[40px] px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                                    <p class="text-gray-500 text-sm">No keywords set</p>
                                </div>
                            </div>
                        </div>

                        <!-- AI Recommendations -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center space-x-2">
                                <svg class="w-5 h-5 text-purple-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>AI Recommendations</span>
                            </h3>
                            
                            <!-- Recommended Title -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700">Recommended Title</label>
                                    <button onclick="applyRecommendation('title')" class="bg-blue-primary text-white px-3 py-1 rounded text-sm hover:bg-blue-dark flex items-center space-x-1">
                                        <span>Apply</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" id="recommended-title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent">
                                <div class="flex items-center justify-between mt-1">
                                    <p id="recommended-title-length" class="text-sm text-gray-500"></p>
                                    <div class="flex items-center space-x-2">
                                        <span id="title-improvement" class="text-sm font-medium text-green-success"></span>
                                        <span id="title-source" class="text-xs text-gray-500"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recommended Description -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700">Recommended Description</label>
                                    <button onclick="applyRecommendation('description')" class="bg-blue-primary text-white px-3 py-1 rounded text-sm hover:bg-blue-dark flex items-center space-x-1">
                                        <span>Apply</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                                <textarea id="recommended-description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent"></textarea>
                                <div class="flex items-center justify-between mt-1">
                                    <p id="recommended-description-length" class="text-sm text-gray-500"></p>
                                    <div class="flex items-center space-x-2">
                                        <span id="description-improvement" class="text-sm font-medium text-green-success"></span>
                                        <span id="description-source" class="text-xs text-gray-500"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recommended Keywords -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700">Recommended Keywords</label>
                                    <button onclick="applyRecommendation('keywords')" class="bg-blue-primary text-white px-3 py-1 rounded text-sm hover:bg-blue-dark flex items-center space-x-1">
                                        <span>Apply</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div id="recommended-keywords" class="min-h-[40px] px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                                    <p class="text-gray-500 text-sm">Loading recommendations...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                let currentPageId = null;
                var pageDataCache = {};

                async function loadPageMetaData(pageId, options = { showGlobal: true, generateAI: true }) {
                    currentPageId = pageId;
                    
                    // Show loading state
                    var globalPanel = document.getElementById('meta-data-interface');
                    if (options && options.showGlobal) {
                        if (globalPanel) globalPanel.classList.remove('hidden');
                } else {
                        if (globalPanel) globalPanel.classList.add('hidden');
                    }
                    
                    try {
                        // AJAX call to get page data and recommendations
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'get_page_meta_data',
                                page_id: pageId,
                                nonce: '<?php echo wp_create_nonce('seo_optimizer_meta_nonce'); ?>'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            pageDataCache[pageId] = data.data;
                            // Log WordPress data before AI processing
                            console.group('📊 WordPress Page Data');
                            console.log('🏷️ Current Meta Title:', data.data.current_meta.title);
                            console.log('📝 Current Meta Description:', data.data.current_meta.description);
                            console.log('🔑 Current Keywords:', data.data.current_meta.keywords);
                            console.log('📄 Page Content Length:', data.data.content ? data.data.content.length : 0, 'characters');
                            console.log('🆔 Page ID:', pageId);
                            console.groupEnd();
                            
                            if (options && options.generateAI) {
                                // Generate AI recommendations using your API
                                const aiData = await aiGenMeta({
                                    title: data.data.current_meta.title,
                                    description: data.data.current_meta.description,
                                    content: data.data.content || '',
                                    id: String(pageId)
                                });
                                
                                // Merge AI recommendations with page data
                                if (aiData) {
                                    console.group('🔄 Data Merging Process');
                                    console.log('🤖 AI Response Data:', aiData);
                                    console.log('📋 Merged Recommendations Object:', {
                                        title: aiData.meta_title,
                                        description: aiData.meta_description,
                                        keywords: aiData.trending_keywords
                                    });
                                    console.groupEnd();
                                    
                                    data.data.recommendations = {
                                        title: aiData.meta_title,
                                        description: aiData.meta_description, 
                                        keywords: aiData.trending_keywords
                                    };
                                    pageDataCache[pageId] = data.data;
            } else {
                                    console.warn('⚠️ No AI data received, using fallback recommendations');
                                }
                            }
                            
                            if (options && options.showGlobal) {
                                displayPageMetaData(data.data);
                            } else {
                                return data.data;
                            }
            } else {
                            alert('Error loading page data: ' + data.data);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error loading page data');
                    }
                }

                async function aiGenMeta(pageData) {
                    // Log the input data being sent to AI API
                    console.group('🤖 AI API Input Data');
                    console.log('📝 Page Data Object:', pageData);
                    console.log('📋 Input Dictionary:', {
                        current_meta_title: pageData.title,
                        current_meta_description: pageData.description,
                        html_content: pageData.content,
                        post_id: pageData.id,
                    });
                    console.log('📏 Content Length:', pageData.content ? pageData.content.length : 0, 'characters');
                    console.log('🔗 API URL:', 'https://test-del-test--seo-seo-optimizer-meta.modal.run/');
                    console.groupEnd();
                    
                    // AJAX call to generate meta data
                    const res = await fetch('https://test-del-test--seo-seo-optimizer-meta.modal.run/', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            current_meta_title: pageData.title,
                            current_meta_description: pageData.description,
                            html_content: pageData.content,
                            post_id: pageData.id,
                        })
                    });
                    
                    const data = await res.json();
                    
                    // Log the AI API response
                    console.group('🤖 AI API Response');
                    console.log('📥 Raw Response:', data);
                    console.log('📊 Response Keys:', Object.keys(data));
                    console.groupEnd();
                    
                    return data;
                }

                function displayPageMetaData(pageData) {
                    // Log final page data being displayed
                    console.group('🎨 Displaying Page Data');
                    console.log('📊 Complete Page Data Object:', pageData);
                    console.log('🏷️ Page Title:', pageData.title);
                    console.log('🔗 Page URL:', pageData.url);
                    console.log('📋 Current Meta:', pageData.current_meta);
                    console.log('🤖 Recommendations:', pageData.recommendations);
                    console.groupEnd();
                    
                    // Update page header
                    document.getElementById('page-title').textContent = pageData.title;
                    document.getElementById('page-url').textContent = pageData.url;
                    
                    // Update current meta data
                    document.getElementById('current-title').value = pageData.current_meta.title || '';
                    document.getElementById('current-description').value = pageData.current_meta.description || '';
                    document.getElementById('current-keywords').innerHTML = pageData.current_meta.keywords || '<p class="text-gray-500 text-sm">No keywords set</p>';
                    
                    // Update lengths
                    document.getElementById('current-title-length').textContent = `Length: ${pageData.current_meta.title.length} characters`;
                    document.getElementById('current-description-length').textContent = `Length: ${pageData.current_meta.description.length} characters`;
                    
                    // Update recommendations
                    document.getElementById('recommended-title').value = pageData.recommendations.title;
                    document.getElementById('recommended-description').value = pageData.recommendations.description;
                    document.getElementById('recommended-keywords').innerHTML = pageData.recommendations.keywords;
                    
                    // Update recommendation details
                    document.getElementById('recommended-title-length').textContent = `Length: ${pageData.recommendations.title.length} characters`;
                    document.getElementById('recommended-description-length').textContent = `Length: ${pageData.recommendations.description.length} characters`;
                    document.getElementById('title-improvement').textContent = pageData.recommendations.title_improvement;
                    document.getElementById('description-improvement').textContent = pageData.recommendations.description_improvement;
                    document.getElementById('title-source').textContent = pageData.recommendations.title_source;
                    document.getElementById('description-source').textContent = pageData.recommendations.description_source;
                }

                async function applyRecommendation(type) {
                    if (!currentPageId) return;
                    
                    let value = '';
                    if (type === 'title') {
                        value = document.getElementById('recommended-title').value;
                    } else if (type === 'description') {
                        value = document.getElementById('recommended-description').value;
                    } else if (type === 'keywords') {
                        // Handle keywords application
                        alert('Keywords application functionality will be implemented');
                        return;
                    }
                    
                    try {
                        // AJAX call to apply recommendation
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'apply_meta_recommendation',
                                page_id: currentPageId,
                                type: type,
                                value: value,
                                nonce: '<?php echo wp_create_nonce('seo_optimizer_apply_nonce'); ?>'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            alert('Recommendation applied successfully!');
                            // Reload the page data
                            await loadPageMetaData(currentPageId);
                        } else {
                            alert('Error applying recommendation: ' + data.data);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error applying recommendation');
                    }
                }
                
                // Inline (per-row) apply using compact editor values
                async function applyInlineRecommendation(pageId, type) {
                    currentPageId = pageId;
                    let value = '';
                    if (type === 'title') {
                        var el = document.getElementById(`rt-${pageId}`);
                        value = el ? el.value : '';
                    } else if (type === 'description') {
                        var el2 = document.getElementById(`rd-${pageId}`);
                        value = el2 ? el2.value : '';
                    } else if (type === 'keywords') {
                        var el3 = document.getElementById(`rkval-${pageId}`);
                        value = el3 ? el3.value : '';
                        if (!value) {
                            alert('Generate recommendations first to get keywords.');
                            return;
                        }
                    } else {
                        alert('Only title, description and keywords supported inline.');
                        return;
                    }
                    if (!value || value.trim() === '') {
                        alert('Generate or type a recommendation first.');
                        return;
                    }
                    try {
                        const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'apply_meta_recommendation',
                                page_id: pageId,
                                type: type,
                                value: value,
                                nonce: '<?php echo wp_create_nonce('seo_optimizer_apply_nonce'); ?>'
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert('Recommendation applied successfully!');
                        } else {
                            alert('Error applying recommendation: ' + data.data);
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Error applying recommendation');
                    }
                }

                // Generate AI for inline panel
                async function generateRecommendationsFor(pageId) {
                    try {
                        var base = pageDataCache[pageId];
                        if (!base) {
                            base = await loadPageMetaData(pageId, { showGlobal: false, generateAI: false });
                        }
                        const btn = document.getElementById(`genbtn-${pageId}`);
                        if (btn) { btn.disabled = true; btn.textContent = 'Generating...'; }
                        const aiData = await aiGenMeta({
                            title: base.current_meta.title,
                            description: base.current_meta.description,
                            content: base.content || '',
                            id: String(pageId)
                        });
                        if (aiData) {
                            const rt = document.getElementById(`rt-${pageId}`);
                            const rd = document.getElementById(`rd-${pageId}`);
                            if (rt) rt.value = aiData.meta_title || '';
                            if (rd) rd.value = aiData.meta_description || '';
                            // Recommended keywords (store raw and render chips)
                            const rk = document.getElementById(`rk-${pageId}`);
                            const rkval = document.getElementById(`rkval-${pageId}`);
                            const kws = (aiData.trending_keywords || []).map(k => k.trim()).filter(Boolean);
                            if (rk) {
                                rk.innerHTML = kws.length ? kws.map(k => `<span class=\"bg-gray-200 text-gray-700 px-2 py-1 rounded mr-1 mb-1 inline-block\">${escapeHtmlLocal(k)}</span>`).join(' ') : '<span class="text-gray-400">No keywords</span>';
                            }
                            if (rkval) rkval.value = kws.join(', ');
                            base.recommendations = {
                                title: aiData.meta_title || '',
                                description: aiData.meta_description || '',
                                keywords: kws.join(', ')
                            };
                            pageDataCache[pageId] = base;
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Failed generating recommendations');
                    } finally {
                        const btn = document.getElementById(`genbtn-${pageId}`);
                        if (btn) { btn.disabled = false; btn.textContent = 'Generate AI Recommendations'; }
                    }
                }

                // Collapsible per-item optimization panel
                async function togglePagePanel(pageId) {
                    const panel = document.getElementById(`panel-${pageId}`);
                    const isHidden = panel.classList.contains('hidden');
                    
                    // Close any other open panel
                    document.querySelectorAll('[id^="panel-\\"]').forEach(function(p) {
                        if (p.id !== `panel-${pageId}`) {
                            p.classList.add('hidden');
                        }
                    });
                    
                    if (!isHidden) {
                        panel.classList.add('hidden');
                        return;
                    }
                    
                    // If not loaded yet, fetch and render compact editor
                    if (!panel.dataset.loaded) {
                        panel.innerHTML = '<div class="py-4 text-sm text-gray-500">Loading...</div>';
                        var pageData = pageDataCache[pageId];
                        if (!pageData) {
                            pageData = await loadPageMetaData(pageId, { showGlobal: false, generateAI: false });
                        }
                        
                        const pageTitle = pageData ? (pageData.title || '') : '';
                        const currentTitle = pageData ? (pageData.current_meta && pageData.current_meta.title ? pageData.current_meta.title : '') : '';
                        const currentDesc = pageData ? (pageData.current_meta && pageData.current_meta.description ? pageData.current_meta.description : '') : '';
                        const recTitle = pageData ? (pageData.recommendations && pageData.recommendations.title ? pageData.recommendations.title : '') : '';
                        const recDesc = pageData ? (pageData.recommendations && pageData.recommendations.description ? pageData.recommendations.description : '') : '';
                        
                        panel.innerHTML = `
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">Optimize: ${escapeHtmlLocal(pageTitle)}</h4>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Current Meta Title</label>
                                        <input value="${escapeAttr(currentTitle)}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" readonly />
                                        <label class="block mt-3 text-xs font-medium text-gray-600 mb-1">Current Meta Description</label>
                                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="3" readonly>${escapeHtmlLocal(currentDesc)}</textarea>
                                    </div>
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-xs font-medium text-gray-600">Recommended Title</label>
                                            <button class="text-blue-primary text-xs" onclick="applyInlineRecommendation(${pageId}, 'title')">Apply</button>
                                        </div>
                                        <input id="rt-${pageId}" value="${escapeAttr(recTitle)}" placeholder="Click Generate AI Recommendations" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                                        <div class="flex justify-between items-center mb-1 mt-3">
                                            <label class="block text-xs font-medium text-gray-600">Recommended Description</label>
                                            <button class="text-blue-primary text-xs" onclick="applyInlineRecommendation(${pageId}, 'description')">Apply</button>
                                        </div>
                                        <textarea id="rd-${pageId}" placeholder="Click Generate AI Recommendations" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="3">${escapeHtmlLocal(recDesc)}</textarea>
                                        <div class="flex justify-between items-center mb-1 mt-3">
                                            <label class="block text-xs font-medium text-gray-600">Recommended Keywords</label>
                                            <button class="text-blue-primary text-xs" onclick="applyInlineRecommendation(${pageId}, 'keywords')">Apply</button>
                                        </div>
                                        <input type="hidden" id="rkval-${pageId}" value="" />
                                        <div id="rk-${pageId}" class="min-h-[40px] px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm text-gray-700">
                                            <span class="text-gray-400">Click Generate AI Recommendations</span>
                                        </div>
                                        <div class="mt-4 text-right">
                                            <button id="genbtn-${pageId}" class="bg-blue-primary hover:bg-blue-dark text-white text-sm px-3 py-2 rounded" onclick="generateRecommendationsFor(${pageId})">Generate AI Recommendations</button>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                        panel.dataset.loaded = '1';
                    }
                    
                    panel.classList.remove('hidden');
                }

                function escapeHtmlLocal(v){
                    return (v || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                }
                function escapeAttr(v){
                    return escapeHtmlLocal(v).replace(/"/g,'&quot;');
                }

                // Search and dropdown behavior
                const searchEl = document.getElementById('search-pages');
                if (searchEl) {
                    searchEl.addEventListener('input', function(e){
                        const q = (e.target.value || '').trim().toLowerCase();
                        document.querySelectorAll('#pages-list > div[id^="item-"]').forEach(function(item){
                            const title = item.getAttribute('data-title') || '';
                            item.style.display = title.indexOf(q) !== -1 ? '' : 'none';
                        });
                    });
                }
                const selectEl = document.getElementById('select-page');
                if (selectEl) {
                    selectEl.addEventListener('change', function(e){
                        const id = e.target.value;
                        if (!id) return;
                        const item = document.getElementById(`item-${id}`);
                        if (item) {
                            item.scrollIntoView({behavior:'smooth', block:'center'});
                            togglePagePanel(id);
                        }
                    });
                }

                // Apply All functionality
                document.getElementById('apply-all-btn').addEventListener('click', async function() {
                    if (!currentPageId) return;
                    
                    if (confirm('Are you sure you want to apply all recommendations?')) {
                        try {
                            // Apply title
                            await applyRecommendation('title');
                            // Apply description
                            await applyRecommendation('description');
                            // Apply keywords
                            await applyRecommendation('keywords');
                        } catch (error) {
                            console.error('Error applying all recommendations:', error);
                            alert('Error applying some recommendations');
                        }
                    }
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    /**
     * Analysis page callback
     */
    public function analysis_page() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>SEO Optimizer - Analysis</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'blue-primary': '#3B82F6',
                                'blue-dark': '#1E40AF',
                                'orange-primary': '#F97316',
                                'green-success': '#10B981',
                                'purple-accent': '#8B5CF6'
                            }
                        }
                    }
                }
            </script>
        </head>
        <body class="bg-gray-50 min-h-screen">
            <!-- Top Navigation Bar -->
            <nav class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <!-- Logo -->
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <h1 class="text-2xl font-bold text-blue-primary">SEO Optimizer</h1>
                            </div>
                        </div>
                        
                        <!-- Navigation Items -->
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="#" class="text-gray-700 hover:text-blue-primary px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="#" class="bg-blue-primary text-white px-3 py-2 rounded-md text-sm font-medium">Analysis</a>
                                <a href="#" class="text-gray-700 hover:text-blue-primary px-3 py-2 rounded-md text-sm font-medium">Implementation</a>
                                <a href="#" class="text-gray-700 hover:text-blue-primary px-3 py-2 rounded-md text-sm font-medium">Performance</a>
                            </div>
                        </div>
                        
                        <!-- User Profile -->
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Breadcrumb -->
            <div class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-sm">
                            <li><a href="#" class="text-gray-500 hover:text-blue-primary">Dashboard</a></li>
                            <li class="text-gray-500">></li>
                            <li class="text-gray-900 font-medium">Analysis Results</li>
                        </ol>
                    </nav>
                </div>
                </div>
                
            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Website Analysis Results</h1>
                            <p class="text-gray-600 text-lg">Comprehensive SEO analysis with AI-powered optimization recommendations</p>
                        </div>
                        <div class="flex space-x-3">
                            <button class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Export Results</span>
                            </button>
                            <button class="bg-blue-primary text-white px-4 py-2 rounded-lg hover:bg-blue-dark flex items-center space-x-2">
                                <span>Implementation</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Analysis Results Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <!-- Website URL -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Website URL:</h2>
                        <p class="text-lg text-blue-primary font-medium">https://example-store.com</p>
                </div>
                
                    <!-- Analysis Details -->
                    <div class="mb-6">
                        <p class="text-gray-600 mb-4">Analyzed on <?php echo date('F j, Y'); ?> at <?php echo date('g:i A'); ?></p>
                        <div class="flex flex-wrap gap-6 text-sm text-gray-600">
                            <span class="flex items-center">
                                <span class="font-medium">Analysis Duration:</span>
                                <span class="ml-2">2m 34s</span>
                            </span>
                            <span class="flex items-center">
                                <span class="font-medium">Pages:</span>
                                <span class="ml-2">5</span>
                            </span>
                            <span class="flex items-center">
                                <span class="font-medium">Products:</span>
                                <span class="ml-2">5</span>
                            </span>
                        </div>
                    </div>

                    <!-- Key Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- SEO Score -->
                        <div class="text-center">
                            <div class="text-6xl font-bold text-orange-primary mb-2">67</div>
                            <div class="text-lg font-medium text-gray-900">SEO Score</div>
                    </div>
                        
                        <!-- Potential Gain -->
                        <div class="text-center">
                            <div class="text-6xl font-bold text-blue-primary mb-2">+28%</div>
                            <div class="text-lg font-medium text-gray-900">Potential Gain</div>
            </div>
                        
                        <!-- Projected Increases -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                <span class="font-medium text-gray-900">Traffic Increase</span>
                                <span class="text-2xl font-bold text-green-success">+35%</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                <span class="font-medium text-gray-900">Conversion Rate</span>
                                <span class="text-2xl font-bold text-blue-primary">+18%</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                <span class="font-medium text-gray-900">Sales Increase</span>
                                <span class="text-2xl font-bold text-orange-primary">+22%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Sources Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Data Sources</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <!-- Google Trends -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-primary rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">Google Trends</span>
                            </div>
                        </div>
                        
                        <!-- Google Keywords -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-success rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">Google Keywords</span>
                            </div>
                        </div>
                        
                        <!-- Amazon Data -->
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-orange-primary rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">Amazon Data</span>
                            </div>
                        </div>
                        
                        <!-- AI Platforms -->
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-accent rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900">AI Platforms</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Integration Status -->
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Integration Status:</span>
                            <span class="text-green-success font-medium">4 of 4 active</span>
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Implementation page callback
     */
    public function implementation_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="seo-optimizer-container">
                <div class="seo-optimizer-form">
                    <h2><?php _e('Implementation', 'seo-optimizer'); ?></h2>
                    <p><?php _e('This page will contain SEO implementation tools and features.', 'seo-optimizer'); ?></p>
                    
                    <div class="notice notice-info">
                        <p><?php _e('Implementation functionality coming soon!', 'seo-optimizer'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Performance page callback
     */
    public function performance_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="seo-optimizer-container">
                <div class="seo-optimizer-form">
                    <h2><?php _e('Performance', 'seo-optimizer'); ?></h2>
                    <p><?php _e('This page will contain SEO performance monitoring and optimization tools.', 'seo-optimizer'); ?></p>
                    
                    <div class="notice notice-info">
                        <p><?php _e('Performance functionality coming soon!', 'seo-optimizer'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler to get post fields
     */
    public function ajax_get_post_fields() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'seo_optimizer_nonce')) {
            wp_die(__('Security check failed', 'seo-optimizer'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'seo-optimizer'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'seo-optimizer'));
        }
        
        // Get post data
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'seo-optimizer'));
        }
        
        // Gather all CMS fields
        $fields_data = $this->get_all_post_fields($post_id);
        
        wp_send_json_success($fields_data);
    }
    
    /**
     * AJAX handler to modify post content
     */
    public function ajax_modify_post_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'seo_optimizer_modify_nonce')) {
            wp_die(__('Security check failed', 'seo-optimizer'));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'seo-optimizer'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'seo-optimizer'));
        }
        
        // Get post data
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'seo-optimizer'));
        }
        
        // Check if user can edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('You do not have permission to edit this post', 'seo-optimizer'));
        }
        
        // Get current content and convert to lowercase
        $current_content = $post->post_content;
        $modified_content = strtolower($current_content);
        
        // Update the post
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $modified_content
        ));
        
        if (is_wp_error($update_result)) {
            wp_send_json_error(__('Failed to update post: ', 'seo-optimizer') . $update_result->get_error_message());
        }
        
        wp_send_json_success(__('Post content has been modified successfully', 'seo-optimizer'));
    }
    
    /**
     * AJAX handler to summarize post content using OpenAI
     */
    public function ajax_summarize_post_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'seo_optimizer_summarize_nonce')) {
            wp_die(__('Security check failed', 'seo-optimizer'));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'seo-optimizer'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'seo-optimizer'));
        }
        
        // Get post data
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'seo-optimizer'));
        }
        
        // Check if user can edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('You do not have permission to edit this post', 'seo-optimizer'));
        }
        
        // Get current content and clean it for OpenAI
        $current_content = $post->post_content;
        $clean_content = $this->clean_content_for_ai($current_content);
        
        // Call OpenAI API to summarize
        $summary = $this->call_openai_api($clean_content);
        
        if (is_wp_error($summary)) {
            wp_send_json_error(__('OpenAI API Error: ', 'seo-optimizer') . $summary->get_error_message());
        }
        
        // Append summary to the bottom of the content
        $summary_html = "\n\n<!-- wp:paragraph -->\n<p><strong>Summary:</strong> " . esc_html($summary) . "</p>\n<!-- /wp:paragraph -->";
        $updated_content = $current_content . $summary_html;
        
        // Update the post
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $updated_content
        ));
        
        if (is_wp_error($update_result)) {
            wp_send_json_error(__('Failed to update post: ', 'seo-optimizer') . $update_result->get_error_message());
        }
        
        wp_send_json_success(__('Post has been summarized and updated successfully', 'seo-optimizer'));
    }
    
    /**
     * Clean content for AI processing
     */
    private function clean_content_for_ai($content) {
        // Remove HTML tags and WordPress block comments
        $clean = strip_tags($content);
        $clean = preg_replace('/<!-- wp:[^>]*-->/', '', $clean);
        $clean = preg_replace('/<!-- \/wp:[^>]*-->/', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);
        
        // Limit content length for API (OpenAI has token limits)
        if (strlen($clean) > 8000) {
            $clean = substr($clean, 0, 8000) . '...';
        }
        
        return $clean;
    }
    
    /**
     * AJAX handler to enhance paragraphs with AI
     */
    public function ajax_enhance_paragraph() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'seo_optimizer_enhance_nonce')) {
            wp_die(__('Security check failed', 'seo-optimizer'));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'seo-optimizer'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'seo-optimizer'));
        }
        
        // Get post data
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'seo-optimizer'));
        }
        
        // Check if user can edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('You do not have permission to edit this post', 'seo-optimizer'));
        }
        
        // Get content and split into paragraphs
        $content = $post->post_content;
        $paragraphs = $this->split_content_into_paragraphs($content);
        
        // Debug information for paragraph splitting
        $debug_info = array(
            'post_id' => $post_id,
            'content_length' => strlen($content),
            'paragraphs_found' => count($paragraphs),
            'content_preview' => substr($content, 0, 200) . '...',
            'paragraphs_preview' => array()
        );
        
        // Add preview of first few paragraphs
        foreach (array_slice($paragraphs, 0, 3) as $index => $paragraph) {
            $debug_info['paragraphs_preview'][] = array(
                'index' => $index,
                'length' => strlen($paragraph),
                'preview' => substr($paragraph, 0, 100) . '...'
            );
        }
        
        if (empty($paragraphs)) {
            wp_send_json_error(array(
                'message' => __('No paragraphs found in content', 'seo-optimizer'),
                'debug' => $debug_info
            ));
        }
        
        // Enhance each paragraph with AI
        $enhanced_paragraphs = array();
        foreach ($paragraphs as $index => $paragraph) {
            $clean_paragraph = $this->clean_content_for_ai($paragraph);
            if (!empty(trim($clean_paragraph))) {
                $enhanced = $this->call_openai_api_for_enhancement($clean_paragraph);
                if (!is_wp_error($enhanced)) {
                    $enhanced_paragraphs[] = array(
                        'original' => $paragraph,
                        'enhanced' => $enhanced
                    );
                } else {
                    $enhanced_paragraphs[] = array(
                        'original' => $paragraph,
                        'enhanced' => $paragraph . ' <em>(Error enhancing: ' . $enhanced->get_error_message() . ')</em>'
                    );
                }
            }
        }
        
        wp_send_json_success(array(
            'paragraphs' => $enhanced_paragraphs,
            'total' => count($enhanced_paragraphs),
            'debug' => $debug_info
        ));
    }
    
    /**
     * AJAX handler to replace a paragraph in the post
     */
    public function ajax_replace_paragraph() {
        check_ajax_referer('seo_optimizer_replace_nonce', 'nonce');
    
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Insufficient permissions or invalid post_id');
        }
    
        $original_text = isset($_POST['original_text']) ? wp_unslash($_POST['original_text']) : '';
        $enhanced_text = isset($_POST['enhanced_text']) ? wp_unslash($_POST['enhanced_text']) : '';
        if ($original_text === '' || $enhanced_text === '') {
            wp_send_json_error('Invalid parameters');
        }
    
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');
    
        // ---------- Normalización robusta ----------
        $normalize = static function(string $s): string {
            // 1) Decodificar entidades (&amp;,&nbsp;)
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // 2) NBSP y variantes a espacio normal
            $s = str_replace(["\xC2\xA0", "\xE2\x80\xAF", "\xE2\x80\x83", "\xE2\x80\x82", "\xE2\x80\x81"], ' ', $s);
            // 3) Unificar guiones/dashes a "—"
            $s = strtr($s, [
                "\xE2\x80\x93" => "—", // en dash
                "\xE2\x80\x94" => "—", // em dash
                "-" => "—",
            ]);
            // 4) Unificar comillas
            $s = strtr($s, [
                "“" => '"', "”" => '"', "„" => '"', "«" => '"', "»" => '"',
                "‘" => "'", "’" => "'", "‚" => "'", "‹" => "'", "›" => "'",
            ]);
            // 5) Quitar HTML si hay
            $s = wp_strip_all_tags($s, true);
            // 6) Colapsar espacios
            $s = preg_replace('/\s+/u', ' ', $s);
            return trim($s);
        };
    
        // Similitud por tokens (fallback)
        $similar_enough = static function(string $hay, string $needle): bool {
            $a = preg_split('/\s+/u', mb_strtolower($hay));
            $b = preg_split('/\s+/u', mb_strtolower($needle));
            if (!$a || !$b) return false;
            $setA = array_count_values($a);
            $inter = 0;
            foreach ($b as $tok) {
                if (!isset($setA[$tok])) continue;
                $inter++; // conteo aproximado
            }
            $ratio = $inter / max(1, count($b));
            return $ratio >= 0.8; // 80% de tokens presentes
        };
    
        $origN = $normalize($original_text);
        $enhN  = $normalize($enhanced_text);
    
        $blocks = parse_blocks($post->post_content);
        $targets = ['core/paragraph','core/quote','core/heading'];
        $found = 0;
    
        $walk = function(array $blocks) use (&$walk, $targets, $normalize, $similar_enough, $origN, $enhN, $original_text, $enhanced_text, &$found) {
            foreach ($blocks as &$b) {
                $name = $b['blockName'] ?? '';
                // Recurse first into innerBlocks (párrafos dentro de quotes, columns, etc.)
                if (!empty($b['innerBlocks'])) {
                    $b['innerBlocks'] = $walk($b['innerBlocks']);
                }
                if (!in_array($name, $targets, true)) {
                    continue;
                }
                $ih = $b['innerHTML'] ?? '';
                if ($ih === '') continue;
    
                // Texto plano normalizado del bloque
                $plain = $normalize($ih);
    
                // ¿Hay match?
                $match = (mb_stripos($plain, $origN) !== false) || $similar_enough($plain, $origN);
                if (!$match) continue;
    
                // Preservar wrapper: ¿tenía <em> envolviendo todo el párrafo?
                $hasEmWrapper = (bool) preg_match('~^\s*<p>\s*<em>.*</em>\s*</p>\s*$~us', $ih);
    
                if ($name === 'core/paragraph') {
                    $newIH = $hasEmWrapper
                        ? '<p><em>'. esc_html($enhanced_text) .'</em></p>'
                        : '<p>'. esc_html($enhanced_text) .'</p>';
                    $b['innerHTML'] = $newIH;
                    if (!empty($b['innerContent'])) {
                        // innerContent suele tener 1 string con el HTML del párrafo
                        foreach ($b['innerContent'] as &$piece) {
                            if (is_string($piece)) {
                                $piece = $newIH;
                            }
                        }
                    }
                    $found++;
                } elseif ($name === 'core/heading') {
                    // Mantener el nivel del heading si se conoce (fallback a <h2>)
                    $level = isset($b['attrs']['level']) ? (int) $b['attrs']['level'] : 2;
                    $level = max(1, min(6, $level));
                    $b['innerHTML'] = sprintf('<h%d>%s</h%d>', $level, esc_html($enhanced_text), $level);
                    if (!empty($b['innerContent'])) {
                        foreach ($b['innerContent'] as &$piece) {
                            if (is_string($piece)) $piece = $b['innerHTML'];
                        }
                    }
                    $found++;
                } elseif ($name === 'core/quote') {
                    // En quotes, el texto suele estar en innerBlocks (paragraphs). Si llegó acá es porque el quote tiene contenido directo.
                    // Reemplazo conservador:
                    $b['innerHTML'] = '<blockquote><p>'. esc_html($enhanced_text) .'</p></blockquote>';
                    if (!empty($b['innerContent'])) {
                        foreach ($b['innerContent'] as &$piece) {
                            if (is_string($piece)) $piece = $b['innerHTML'];
                        }
                    }
                    $found++;
                }
            }
            return $blocks;
        };
    
        $blocks = $walk($blocks);
    
        if ($found === 0) {
            wp_send_json_error('Original text not found in blocks');
        }
    
        $new_content = serialize_blocks($blocks);
        $res = wp_update_post(['ID' => $post_id, 'post_content' => $new_content], true);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }
    
        wp_send_json_success([
            'message'  => 'Paragraph replaced successfully',
            'post_id'  => $post_id,
            'replaced' => $found,
            'preview'  => mb_substr($new_content, 0, 300),
        ]);
    }
    
    
    /**
     * Split content into paragraphs
     */
    private function split_content_into_paragraphs($content) {
        // First, try to preserve paragraph structure from WordPress blocks
        $paragraphs = array();
        
        // Method 1: Try to split by WordPress paragraph blocks
        if (strpos($content, '<!-- wp:paragraph -->') !== false) {
            $blocks = preg_split('/<!-- wp:paragraph -->/', $content);
            foreach ($blocks as $block) {
                if (strpos($block, '<!-- /wp:paragraph -->') !== false) {
                    $paragraph_content = preg_replace('/<!-- \/wp:paragraph -->.*$/', '', $block);
                    $paragraph_content = strip_tags($paragraph_content);
                    $paragraph_content = trim($paragraph_content);
                    if (!empty($paragraph_content) && strlen($paragraph_content) > 20) {
                        $paragraphs[] = $paragraph_content;
                    }
                }
            }
        }
        
        // Method 2: If no WordPress blocks found, split by HTML paragraphs
        if (empty($paragraphs) && strpos($content, '<p>') !== false) {
            $html_paragraphs = preg_split('/<\/p>\s*<p[^>]*>/', $content);
            foreach ($html_paragraphs as $html_paragraph) {
                $paragraph_content = strip_tags($html_paragraph);
                $paragraph_content = trim($paragraph_content);
                if (!empty($paragraph_content) && strlen($paragraph_content) > 20) {
                    $paragraphs[] = $paragraph_content;
                }
            }
        }
        
        // Method 3: Fallback - split by double line breaks
        if (empty($paragraphs)) {
            $clean_content = strip_tags($content);
            $split_paragraphs = preg_split('/\n\s*\n/', $clean_content);
            
            foreach ($split_paragraphs as $paragraph) {
                $cleaned = trim($paragraph);
                if (!empty($cleaned) && strlen($cleaned) > 20) {
                    $paragraphs[] = $cleaned;
                }
            }
        }
        
        // Method 4: Last resort - split by single line breaks if content is very long
        if (empty($paragraphs)) {
            $clean_content = strip_tags($content);
            $split_paragraphs = preg_split('/\n/', $clean_content);
            
            foreach ($split_paragraphs as $paragraph) {
                $cleaned = trim($paragraph);
                if (!empty($cleaned) && strlen($cleaned) > 50) {
                    $paragraphs[] = $cleaned;
                }
            }
        }
        
        return $paragraphs;
    }
    
    /**
     * Call OpenAI API to enhance a single paragraph
     */
    private function call_openai_api_for_enhancement($content) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-5-nano',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a professional content writer and editor. Your task is to enhance the given paragraph by improving its clarity, flow, and engagement while maintaining the original meaning and tone. Make the text more compelling and better structured without changing the core message.'
                ),
                array(
                    'role' => 'user',
                    'content' => 'Please enhance this paragraph to make it more engaging and well-written: ' . $content
                )
            )
        );
        
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('openai_error', $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('openai_error', 'No enhanced text generated');
        }
        
        return trim($data['choices'][0]['message']['content']);
    }
    
    /**
     * Call OpenAI API to summarize content
     */
    private function call_openai_api($content) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-5-nano',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that creates concise, informative summaries of content. Summarize the following content in 2-3 sentences, focusing on the main points and key takeaways.'
                ),
                array(
                    'role' => 'user',
                    'content' => $content
                )
            ),
            'max_tokens' => 150,
            'temperature' => 0.7
        );
        
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('openai_error', $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('openai_error', 'No summary generated');
        }
        
        return trim($data['choices'][0]['message']['content']);
    }
    
    /**
     * Get all CMS fields for a post
     */
    public function get_all_post_fields($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }
        
        $fields_data = array();
        
        // Basic post data
        $fields_data['basic_info'] = array(
            'title' => __('Basic Post Information', 'seo-optimizer'),
            'fields' => array(
                'ID' => $post->ID,
                'Title' => $post->post_title,
                'Content' => $post->post_content,
                'Excerpt' => $post->post_excerpt,
                'Status' => $post->post_status,
                'Type' => $post->post_type,
                'Date Created' => $post->post_date,
                'Date Modified' => $post->post_modified,
                'Author' => get_the_author_meta('display_name', $post->post_author),
                'Slug' => $post->post_name,
                'Parent ID' => $post->post_parent,
                'Menu Order' => $post->menu_order,
                'Comment Status' => $post->comment_status,
                'Ping Status' => $post->ping_status
            )
        );
        
        // Post meta (custom fields)
        $meta_data = get_post_meta($post_id);
        if (!empty($meta_data)) {
            $fields_data['meta_fields'] = array(
                'title' => __('Custom Fields (Post Meta)', 'seo-optimizer'),
                'fields' => array()
            );
            
            foreach ($meta_data as $key => $values) {
                // Skip private meta fields (starting with _) unless they're common ones
                if (strpos($key, '_') === 0) {
                    $common_private_fields = array(
                        '_edit_last',
                        '_edit_lock',
                        '_wp_page_template',
                        '_thumbnail_id',
                        '_wp_attached_file',
                        '_wp_attachment_metadata'
                    );
                    
                    if (!in_array($key, $common_private_fields)) {
                        continue;
                    }
                }
                
                $value = is_array($values) && count($values) === 1 ? $values[0] : $values;
                
                // Try to unserialize if it's serialized data
                if (is_string($value) && is_serialized($value)) {
                    $unserialized = maybe_unserialize($value);
                    $value = $unserialized !== false ? $unserialized : $value;
                }
                
                $fields_data['meta_fields']['fields'][$key] = $value;
            }
        }
        
        // Taxonomies (categories, tags, custom taxonomies)
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        if (!empty($taxonomies)) {
            $fields_data['taxonomies'] = array(
                'title' => __('Taxonomies', 'seo-optimizer'),
                'fields' => array()
            );
            
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_post_terms($post_id, $taxonomy->name);
                if (!empty($terms) && !is_wp_error($terms)) {
                    $term_names = array();
                    foreach ($terms as $term) {
                        $term_names[] = $term->name . ' (ID: ' . $term->term_id . ')';
                    }
                    $fields_data['taxonomies']['fields'][$taxonomy->label] = implode(', ', $term_names);
                }
            }
        }
        
        // Featured image
        if (has_post_thumbnail($post_id)) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
            $fields_data['featured_image'] = array(
                'title' => __('Featured Image', 'seo-optimizer'),
                'fields' => array(
                    'Thumbnail ID' => $thumbnail_id,
                    'Thumbnail URL' => $thumbnail_url,
                    'Alt Text' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true)
                )
            );
        }
        
        // ACF Fields (if ACF is active)
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($post_id);
            if (!empty($acf_fields)) {
                $fields_data['acf_fields'] = array(
                    'title' => __('Advanced Custom Fields (ACF)', 'seo-optimizer'),
                    'fields' => $acf_fields
                );
            }
        }
        
        // Comments count
        $comments_count = wp_count_comments($post_id);
        if ($comments_count->total_comments > 0) {
            $fields_data['comments'] = array(
                'title' => __('Comments', 'seo-optimizer'),
                'fields' => array(
                    'Total Comments' => $comments_count->total_comments,
                    'Approved Comments' => $comments_count->approved,
                    'Pending Comments' => $comments_count->moderated,
                    'Spam Comments' => $comments_count->spam,
                    'Trash Comments' => $comments_count->trash
                )
            );
        }
        
        return $fields_data;
    }
    
    /**
     * Display results in HTML format (for PHP fallback)
     */
    public function display_results_html($data) {
        if (empty($data)) {
            echo '<p>' . __('No fields found.', 'seo-optimizer') . '</p>';
            return;
        }
        
        foreach ($data as $section_key => $section) {
            if (!isset($section['title']) || !isset($section['fields'])) {
                continue;
            }
            ?>
            <div class="seo-optimizer-section">
                <h3><?php echo esc_html($section['title']); ?></h3>
                <div class="seo-optimizer-fields">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Field Name', 'seo-optimizer'); ?></th>
                                <th><?php _e('Value', 'seo-optimizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($section['fields'] as $field_name => $field_value): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($field_name); ?></strong></td>
                                    <td><?php echo $this->format_field_value_html($field_value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Format field value for HTML display
     */
    public function format_field_value_html($value) {
        if ($value === null || $value === '') {
            return '<em>' . __('empty', 'seo-optimizer') . '</em>';
        }
        
        if (is_bool($value)) {
            return $value ? '<span style="color: #46b450; font-weight: bold;">true</span>' : '<span style="color: #dc3232; font-weight: bold;">false</span>';
        }
        
        if (is_array($value) || is_object($value)) {
            return '<pre style="background: #f6f7f7; border: 1px solid #ddd; padding: 10px; font-size: 12px; max-height: 200px; overflow-y: auto;">' . esc_html(print_r($value, true)) . '</pre>';
        }
        
        $string_value = (string) $value;
        
        // Handle URLs
        if (filter_var($string_value, FILTER_VALIDATE_URL)) {
            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $string_value)) {
                return '<a href="' . esc_url($string_value) . '" target="_blank">' . esc_html($string_value) . '</a><br><img src="' . esc_url($string_value) . '" alt="Image" style="max-width: 200px; max-height: 150px; margin-top: 5px; border: 1px solid #ddd;">';
            } else {
                return '<a href="' . esc_url($string_value) . '" target="_blank">' . esc_html($string_value) . '</a>';
            }
        }
        
        // Handle long text
        if (strlen($string_value) > 200) {
            return '<details><summary>' . esc_html(substr($string_value, 0, 200)) . '...</summary>' . esc_html($string_value) . '</details>';
        }
        
        return esc_html($string_value);
    }
    
    /**
     * AJAX handler to get page meta data and recommendations
     */
    public function ajax_get_page_meta_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'seo_optimizer_meta_nonce')) {
            wp_die(__('Security check failed', 'seo-optimizer'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'seo-optimizer'));
        }
        
        $page_id = intval($_POST['page_id']);
        
        if (!$page_id) {
            wp_send_json_error(__('Invalid page ID', 'seo-optimizer'));
        }
        
        // Get post data
        $post = get_post($page_id);
        
        if (!$post) {
            wp_send_json_error(__('Page not found', 'seo-optimizer'));
        }
        
        // Get current meta data
        $current_meta = array(
            'title' => get_post_meta($page_id, '_yoast_wpseo_title', true) ?: get_the_title($page_id),
            'description' => get_post_meta($page_id, '_yoast_wpseo_metadesc', true) ?: '',
            'keywords' => get_post_meta($page_id, '_yoast_wpseo_focuskw', true) ?: ''
        );
        
        // Generate URL
        $page_url = get_permalink($page_id);
        
        // Get page content for AI processing
        $page_content = $post->post_content;
        
        // Generate AI recommendations (mock data for now - will be overridden by AI API)
        $recommendations = $this->generate_meta_recommendations($post, $current_meta);
        
        wp_send_json_success(array(
            'title' => $post->post_title,
            'url' => $page_url,
            'content' => $page_content,
            'current_meta' => $current_meta,
            'recommendations' => $recommendations
        ));
    }
    
    /**
     * AJAX handler to apply meta recommendations
     */
    public function ajax_apply_meta_recommendation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'seo_optimizer_apply_nonce')) {
            wp_die(__('Security check failed', 'seo-optimizer'));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions', 'seo-optimizer'));
        }
        
        $page_id = intval($_POST['page_id']);
        $type = sanitize_text_field($_POST['type']);
        $value = sanitize_textarea_field($_POST['value']);
        
        if (!$page_id || !$type || !$value) {
            wp_send_json_error(__('Invalid parameters', 'seo-optimizer'));
        }
        
        // Check if user can edit this specific post
        if (!current_user_can('edit_post', $page_id)) {
            wp_send_json_error(__('You do not have permission to edit this post', 'seo-optimizer'));
        }
        
        // Apply the recommendation based on type
        switch ($type) {
            case 'title':
                update_post_meta($page_id, '_yoast_wpseo_title', $value);
                break;
            case 'description':
                update_post_meta($page_id, '_yoast_wpseo_metadesc', $value);
                break;
            case 'keywords':
                update_post_meta($page_id, '_yoast_wpseo_focuskw', $value);
                break;
            default:
                wp_send_json_error(__('Invalid recommendation type', 'seo-optimizer'));
        }
        
        wp_send_json_success(__('Recommendation applied successfully', 'seo-optimizer'));
    }
    
    /**
     * Generate AI-powered meta recommendations
     */
    private function generate_meta_recommendations($post, $current_meta) {
        // Mock AI recommendations - in a real implementation, this would call an AI API
        $title_improvements = array(
            'Contact' => array(
                'title' => 'Contact ExampleStore - Expert Electronics Support & Customer Service',
                'description' => 'Get instant help from our electronics experts. Live chat, phone support, and email assistance available 24/7. Contact us for product advice and technical support.',
                'keywords' => 'contact, customer service, electronics support, help, phone support, live chat',
                'title_improvement' => '+35% ranking',
                'description_improvement' => '+45% CTR',
                'title_source' => 'Based on AI Platforms',
                'description_source' => 'Based on Google Trends'
            ),
            'Home' => array(
                'title' => 'Premium Electronics & Gadgets Store - Best Deals Online | ExampleStore',
                'description' => 'Discover top-quality electronics, gadgets, and accessories at unbeatable prices. Free shipping on orders over $50. Shop now and save big on premium tech products.',
                'keywords' => 'electronics, gadgets, online store, deals, tech products, premium',
                'title_improvement' => '+25% ranking',
                'description_improvement' => '+18% CTR',
                'title_source' => 'Based on Google Trends',
                'description_source' => 'Based on AI Platforms'
            )
        );
        
        // Check if we have specific recommendations for this page
        $post_title_lower = strtolower($post->post_title);
        if (strpos($post_title_lower, 'contact') !== false) {
            $recommendations = $title_improvements['Contact'];
        } elseif (strpos($post_title_lower, 'home') !== false || strpos($post_title_lower, 'welcome') !== false) {
            $recommendations = $title_improvements['Home'];
        } else {
            // Generic recommendations
            $recommendations = array(
                'title' => $post->post_title . ' - Professional Services & Solutions',
                'description' => 'Discover professional services and solutions tailored to your needs. Contact us today for expert assistance and personalized support.',
                'keywords' => strtolower($post->post_title) . ', services, solutions, professional',
                'title_improvement' => '+20% ranking',
                'description_improvement' => '+15% CTR',
                'title_source' => 'Based on AI Platforms',
                'description_source' => 'Based on Google Trends'
            );
        }
        
        // Format keywords as HTML
        $keywords_array = explode(', ', $recommendations['keywords']);
        $keywords_html = '<div class="flex flex-wrap gap-2">';
        foreach ($keywords_array as $keyword) {
            $keywords_html .= '<span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-sm">' . esc_html(trim($keyword)) . '</span>';
        }
        $keywords_html .= '</div>';
        
        return array(
            'title' => $recommendations['title'],
            'description' => $recommendations['description'],
            'keywords' => $keywords_html,
            'title_improvement' => $recommendations['title_improvement'],
            'description_improvement' => $recommendations['description_improvement'],
            'title_source' => $recommendations['title_source'],
            'description_source' => $recommendations['description_source']
        );
    }
}

// Initialize the plugin
new SEO_Optimizer();
