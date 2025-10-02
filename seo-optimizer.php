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
        // add_action('admin_init', array($this, 'register_api_settings'));
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
     * Register API settings
     */
    public function register_api_settings() {
        register_setting('seo_optimizer_api_settings', 'ai_model_provider', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('seo_optimizer_api_settings', 'ai_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('seo_optimizer_api_settings', 'ai_model_id', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        add_settings_section(
            'seo_optimizer_api_section',
            __('AI Model Configuration', 'seo-optimizer'),
            array($this, 'api_section_callback'),
            'seo_optimizer_api_settings'
        );
    }
    
    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure your AI model provider and API settings below.', 'seo-optimizer') . '</p>';
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
            __('API Integration', 'seo-optimizer'),
            __('API Integration', 'seo-optimizer'),
            'manage_options',
            'seo-optimizer-api-integration',
            array($this, 'api_integration_page')
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
            'seo-optimizer_page_seo-optimizer-api-integration',
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
    public function analysis_page() {
        // Get all published posts, pages, and products
        $post_types = array('page', 'post');
        
        // Check if WooCommerce is active and add product post type
        if (class_exists('WooCommerce')) {
            $post_types[] = 'product';
        }
        
        $pages = get_posts(array(
            'post_type' => $post_types,
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
            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Content Management Hub</h1>
                    <p class="text-gray-600 text-lg">Manage your website content across products, blogs, and pages from one unified interface.</p>
                </div>

                <!-- Tabs Navigation -->
                <div class="bg-white rounded-t-lg shadow-sm border border-gray-200 border-b-0">
                    <div class="flex space-x-1 p-2">
                        <?php if (class_exists('WooCommerce')): ?>
                        <button onclick="switchTab('product')" id="tab-product" class="tab-button flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span>Product</span>
                        </button>
                        <?php endif; ?>
                        <button onclick="switchTab('post')" id="tab-post" class="tab-button flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-medium bg-blue-50 text-blue-primary border-2 border-blue-primary transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Content</span>
                        </button>
                        <button onclick="switchTab('page')" id="tab-page" class="tab-button flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Pages</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="bg-white rounded-b-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4" id="tab-title">Blog Content Management</h2>

                    <!-- Controls: search + dropdown select -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-4">
                        <input id="search-pages" type="text" placeholder="Search content..." class="w-full sm:w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent" />
                        <select id="select-page" class="w-full sm:w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-primary focus:border-transparent">
                            <option value="">Select content...</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" data-type="<?php echo esc_attr($page->post_type); ?>"><?php echo esc_html($page->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- List of pages with collapsible optimization panels -->
                    <div id="pages-list" class="divide-y divide-gray-200">
                        <?php foreach ($pages as $page): ?>
                            <div id="item-<?php echo $page->ID; ?>" data-title="<?php echo esc_attr(strtolower($page->post_title)); ?>" data-type="<?php echo esc_attr($page->post_type); ?>" class="py-3 page-item">
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
                                }, pageId);
                                
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

                async function aiGenMeta(pageData, pageId) {
                    // Get writing style and tone input from the dynamic panel
                    const writingStyleTone = document.getElementById(`writing-style-tone-${pageId}`)?.value || '';
                    
                    // Log the input data being sent to AI API
                    console.group('🤖 AI API Input Data');
                    console.log('📝 Page Data Object:', pageData);
                    console.log('📋 Input Dictionary:', {
                        current_meta_title: pageData.title,
                        current_meta_description: pageData.description,
                        html_content: pageData.content,
                        post_id: pageData.id,
                        writing_style_tone: writingStyleTone,
                    });
                    console.log('📏 Content Length:', pageData.content ? pageData.content.length : 0, 'characters');
                    console.log('✍️ Writing Style & Tone:', writingStyleTone);
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
                            writing_style_tone: writingStyleTone,
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
                        }, pageId);
                        if (aiData) {
                            const rt = document.getElementById(`rt-${pageId}`);
                            const rd = document.getElementById(`rd-${pageId}`);
                            if (rt) rt.value = aiData.meta_title || '';
                            if (rd) rd.value = aiData.meta_description || '';
                            
                            // Show the meta preview section
                            const metaPreview = document.getElementById(`meta-preview-${pageId}`);
                            if (metaPreview) metaPreview.classList.remove('hidden');
                            
                            // Recommended keywords (store raw and render chips)
                            const rk = document.getElementById(`rk-${pageId}`);
                            const rkval = document.getElementById(`rkval-${pageId}`);
                            const kws = (aiData.trending_keywords || []).map(k => k.trim()).filter(Boolean);
                            if (rk) {
                                rk.innerHTML = kws.length ? kws.map(k => `<span class=\"bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs mr-1 mb-1 inline-block\">${escapeHtmlLocal(k)}</span>`).join(' ') : '<span class="text-gray-400 text-xs">No keywords</span>';
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
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Optimize: ${escapeHtmlLocal(pageTitle)}</h4>
                                
                                <!-- One-Click Optimize Button -->
                                <button 
                                    onclick="optimizeEntirePage(${pageId})"
                                    class="w-full bg-gradient-to-r from-blue-primary to-purple-accent hover:from-blue-dark hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg mb-3 flex items-center justify-center gap-2 transition-all shadow-sm hover:shadow-md">
                                    <span>🚀</span>
                                    <span>Optimize Entire Page</span>
                                </button>
                                
                                <!-- Writing Style & Keywords Section -->
                                <div class="bg-white border border-gray-200 rounded-lg p-3 mb-3">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Writing Style and Tone</label>
                                    <textarea id="writing-style-tone-${pageId}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" rows="2" placeholder="e.g., Professional, conversational, technical..."></textarea>
                                    
                                    <div class="mt-3">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Recommended Keywords</label>
                                        <input type="hidden" id="rkval-${pageId}" value="" />
                                        <div id="rk-${pageId}" class="min-h-[40px] px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm text-gray-700">
                                            <span class="text-gray-400 text-xs">Keywords will appear here after optimization</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Divider -->
                                <div class="flex items-center gap-3 my-3">
                                    <div class="flex-1 border-t border-gray-300"></div>
                                    <span class="text-xs text-gray-500 font-medium">CONTENT BLOCKS</span>
                                    <div class="flex-1 border-t border-gray-300"></div>
                                </div>
                                
                                <!-- Content Block Optimizer -->
                                <div id="content-block-optimizer-${pageId}">
                                    
                                    <!-- Accordion Sections -->
                                    <div class="space-y-2">
                                        
                                        <!-- Meta Tags & SEO Section (FIRST) -->
                                        <div class="border border-gray-300 rounded-lg overflow-hidden shadow-sm">
                                            <button 
                                                onclick="toggleBlockSection(${pageId}, 'meta')"
                                                class="w-full flex items-center justify-between p-3 bg-white hover:bg-gray-50 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg">🏷️</span>
                                                    <span class="font-medium text-sm text-gray-900">Meta Tags & SEO</span>
                                                </div>
                                                <svg id="meta-arrow-${pageId}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                            <div id="meta-section-${pageId}" class="hidden bg-gray-50 p-4 border-t border-gray-200">
                                                <!-- Current Meta -->
                                                <div class="mb-4">
                                                    <h5 class="text-xs font-semibold text-gray-700 mb-2">Current Meta Data</h5>
                                                    <div class="space-y-2">
                                                        <div>
                                                            <label class="block text-xs text-gray-600 mb-1">Meta Title</label>
                                                            <input value="${escapeAttr(currentTitle)}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" readonly />
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs text-gray-600 mb-1">Meta Description</label>
                                                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" rows="2" readonly>${escapeHtmlLocal(currentDesc)}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Generate Button -->
                                                <button id="genbtn-${pageId}" 
                                                    onclick="generateRecommendationsFor(${pageId})" 
                                                    class="w-full bg-blue-primary hover:bg-blue-dark text-white text-sm px-3 py-2 rounded transition-colors mb-4">
                                                    Generate AI Recommendations
                                                </button>
                                                
                                                <!-- Preview/Recommendations -->
                                                <div id="meta-preview-${pageId}" class="hidden">
                                                    <h5 class="text-xs font-semibold text-gray-700 mb-2">✨ Optimized Preview</h5>
                                                    <div class="space-y-3">
                                                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                            <div class="flex justify-between items-center mb-1">
                                                                <label class="block text-xs font-medium text-gray-600">Recommended Title</label>
                                                                <button class="text-blue-primary text-xs hover:text-blue-dark font-medium" onclick="applyInlineRecommendation(${pageId}, 'title')">Apply</button>
                                                            </div>
                                                            <input id="rt-${pageId}" value="${escapeAttr(recTitle)}" placeholder="Generating..." class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm bg-gray-50" readonly />
                                                        </div>
                                                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                                                            <div class="flex justify-between items-center mb-1">
                                                                <label class="block text-xs font-medium text-gray-600">Recommended Description</label>
                                                                <button class="text-blue-primary text-xs hover:text-blue-dark font-medium" onclick="applyInlineRecommendation(${pageId}, 'description')">Apply</button>
                                                            </div>
                                                            <textarea id="rd-${pageId}" placeholder="Generating..." class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm bg-gray-50" rows="2" readonly>${escapeHtmlLocal(recDesc)}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Text Content Section -->
                                        <div class="border border-gray-300 rounded-lg overflow-hidden shadow-sm">
                                            <button 
                                                onclick="toggleBlockSection(${pageId}, 'text')"
                                                class="w-full flex items-center justify-between p-3 bg-white hover:bg-gray-50 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg">📄</span>
                                                    <span class="font-medium text-sm text-gray-900">Text Content</span>
                                                    <span id="text-count-${pageId}" class="text-xs bg-blue-100 text-blue-primary px-2 py-1 rounded-full font-medium">
                                                        Loading...
                                                    </span>
                                                </div>
                                                <svg id="text-arrow-${pageId}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                            <div id="text-section-${pageId}" class="hidden bg-gray-50 p-4 border-t border-gray-200">
                                                <div id="text-blocks-${pageId}" class="space-y-2 mb-3 max-h-60 overflow-y-auto">
                                                    <!-- Blocks will be loaded here -->
                                                </div>
                                                <div class="flex gap-2 pt-3 border-t border-gray-200">
                                                    <button onclick="selectAllBlocks(${pageId}, 'text')" class="text-xs bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-100 transition-colors">
                                                        ✓ Select All
                                                    </button>
                                                    <button onclick="optimizeSelectedBlocks(${pageId}, 'text')" class="text-xs bg-blue-primary text-white px-3 py-1.5 rounded hover:bg-blue-dark transition-colors">
                                                        <span id="text-optimize-btn-${pageId}">Optimize Selected (0)</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Visual/CTA Content Section -->
                                        <div class="border border-gray-300 rounded-lg overflow-hidden shadow-sm">
                                            <button 
                                                onclick="toggleBlockSection(${pageId}, 'visual')"
                                                class="w-full flex items-center justify-between p-3 bg-white hover:bg-gray-50 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg">🎨</span>
                                                    <span class="font-medium text-sm text-gray-900">Visual/CTA Content</span>
                                                    <span id="visual-count-${pageId}" class="text-xs bg-purple-100 text-purple-accent px-2 py-1 rounded-full font-medium">
                                                        Loading...
                                                    </span>
                                                </div>
                                                <svg id="visual-arrow-${pageId}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                            <div id="visual-section-${pageId}" class="hidden bg-gray-50 p-4 border-t border-gray-200">
                                                <div id="visual-blocks-${pageId}" class="space-y-2 mb-3 max-h-60 overflow-y-auto"></div>
                                                <div class="flex gap-2 pt-3 border-t border-gray-200">
                                                    <button onclick="selectAllBlocks(${pageId}, 'visual')" class="text-xs bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-100 transition-colors">
                                                        ✓ Select All
                                                    </button>
                                                    <button onclick="optimizeSelectedBlocks(${pageId}, 'visual')" class="text-xs bg-purple-accent text-white px-3 py-1.5 rounded hover:bg-purple-700 transition-colors">
                                                        <span id="visual-optimize-btn-${pageId}">Optimize Selected (0)</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Product Content Section -->
                                        <div id="product-section-wrapper-${pageId}" class="border border-gray-300 rounded-lg overflow-hidden shadow-sm" style="display: none;">
                                            <button 
                                                onclick="toggleBlockSection(${pageId}, 'product')"
                                                class="w-full flex items-center justify-between p-3 bg-white hover:bg-gray-50 transition-colors">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg">🛍️</span>
                                                    <span class="font-medium text-sm text-gray-900">Product Content</span>
                                                    <span id="product-count-${pageId}" class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded-full font-medium">
                                                        0 blocks
                                                    </span>
                                                </div>
                                                <svg id="product-arrow-${pageId}" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                            <div id="product-section-${pageId}" class="hidden bg-gray-50 p-4 border-t border-gray-200">
                                                <div id="product-blocks-${pageId}" class="space-y-2 mb-3 max-h-60 overflow-y-auto"></div>
                                                <div class="flex gap-2 pt-3 border-t border-gray-200">
                                                    <button onclick="selectAllBlocks(${pageId}, 'product')" class="text-xs bg-white border border-gray-300 px-3 py-1.5 rounded hover:bg-gray-100 transition-colors">
                                                        ✓ Select All
                                                    </button>
                                                    <button onclick="optimizeSelectedBlocks(${pageId}, 'product')" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700 transition-colors">
                                                        <span id="product-optimize-btn-${pageId}">Optimize Selected (0)</span>
                                                    </button>
                                                </div>
                                            </div>
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

                // ========================================
                // Content Block Optimizer Functions
                // ========================================
                
                // Store blocks data per page
                const pageBlocksCache = {};
                
                // Toggle accordion section
                function toggleBlockSection(pageId, sectionType) {
                    const section = document.getElementById(`${sectionType}-section-${pageId}`);
                    const arrow = document.getElementById(`${sectionType}-arrow-${pageId}`);
                    
                    if (!section || !arrow) return;
                    
                    const isHidden = section.classList.contains('hidden');
                    
                    // For content blocks (not meta), close other content blocks when opening
                    if (sectionType !== 'meta') {
                        ['text', 'visual', 'product'].forEach(type => {
                            if (type !== sectionType) {
                                const otherSection = document.getElementById(`${type}-section-${pageId}`);
                                const otherArrow = document.getElementById(`${type}-arrow-${pageId}`);
                                if (otherSection && otherArrow) {
                                    otherSection.classList.add('hidden');
                                    otherArrow.classList.remove('rotate-90');
                                }
                            }
                        });
                    }
                    
                    // Toggle current section
                    if (isHidden) {
                        section.classList.remove('hidden');
                        arrow.classList.add('rotate-90');
                        
                        // Load blocks if not loaded yet (with mock data for now) - only for content blocks
                        if (sectionType !== 'meta' && !pageBlocksCache[pageId]) {
                            loadPageBlocksMock(pageId);
                        }
                    } else {
                        section.classList.add('hidden');
                        arrow.classList.remove('rotate-90');
                    }
                }
                
                // Mock data loader (simulates backend response)
                function loadPageBlocksMock(pageId) {
                    // Simulate realistic page blocks
                    const mockData = {
                        blocks: [
                            { id: 0, blockName: 'core/heading', heading_level: 'H1', clean_text: 'Welcome to Our Company', preview: 'Welcome to Our Company', category: 'text_content' },
                            { id: 1, blockName: 'core/paragraph', clean_text: 'We provide amazing services that help businesses grow...', preview: 'We provide amazing services that help businesses grow...', category: 'text_content' },
                            { id: 2, blockName: 'core/heading', heading_level: 'H2', clean_text: 'Our Services', preview: 'Our Services', category: 'text_content' },
                            { id: 3, blockName: 'core/paragraph', clean_text: 'Our team specializes in cutting-edge solutions...', preview: 'Our team specializes in cutting-edge solutions...', category: 'text_content' },
                            { id: 4, blockName: 'core/heading', heading_level: 'H2', clean_text: 'Why Choose Us', preview: 'Why Choose Us', category: 'text_content' },
                            { id: 5, blockName: 'core/list', clean_text: 'Service 1, Service 2, Service 3', preview: '• Service 1 • Service 2 • Service 3', category: 'text_content' },
                            { id: 6, blockName: 'core/button', clean_text: 'Get Started Now', preview: 'Get Started Now', category: 'cta_elements' },
                            { id: 7, blockName: 'core/button', clean_text: 'Contact Us', preview: 'Contact Us', category: 'cta_elements' },
                            { id: 8, blockName: 'core/image', clean_text: 'Team photo at office', preview: 'Team photo at office', category: 'visual_content', metadata: { alt_text: 'Team photo at office' } }
                        ],
                        categorized: {
                            text_content: [
                                { id: 0, blockName: 'core/heading', heading_level: 'H1', preview: 'Welcome to Our Company' },
                                { id: 1, blockName: 'core/paragraph', preview: 'We provide amazing services that help businesses grow...' },
                                { id: 2, blockName: 'core/heading', heading_level: 'H2', preview: 'Our Services' },
                                { id: 3, blockName: 'core/paragraph', preview: 'Our team specializes in cutting-edge solutions...' },
                                { id: 4, blockName: 'core/heading', heading_level: 'H2', preview: 'Why Choose Us' },
                                { id: 5, blockName: 'core/list', preview: '• Service 1 • Service 2 • Service 3' }
                            ],
                            cta_elements: [
                                { id: 6, blockName: 'core/button', preview: 'Get Started Now' },
                                { id: 7, blockName: 'core/button', preview: 'Contact Us' }
                            ],
                            visual_content: [
                                { id: 8, blockName: 'core/image', preview: 'Team photo at office' }
                            ],
                            product_content: []
                        },
                        stats: {
                            total: 9,
                            text_blocks: 6,
                            cta_blocks: 2,
                            visual_blocks: 1,
                            product_blocks: 0
                        }
                    };
                    
                    pageBlocksCache[pageId] = mockData;
                    renderBlocks(pageId, mockData);
                }
                
                // Render blocks into accordion sections
                function renderBlocks(pageId, data) {
                    const { categorized, stats } = data;
                    
                    // Update counts
                    const textCount = document.getElementById(`text-count-${pageId}`);
                    const visualCount = document.getElementById(`visual-count-${pageId}`);
                    const productCount = document.getElementById(`product-count-${pageId}`);
                    
                    if (textCount) textCount.textContent = `${stats.text_blocks} blocks`;
                    if (visualCount) visualCount.textContent = `${stats.cta_blocks + stats.visual_blocks} blocks`;
                    if (productCount) productCount.textContent = `${stats.product_blocks} blocks`;
                    
                    // Show/hide product section if has products
                    if (stats.product_blocks > 0) {
                        const productWrapper = document.getElementById(`product-section-wrapper-${pageId}`);
                        if (productWrapper) productWrapper.style.display = 'block';
                    }
                    
                    // Render text blocks
                    const textContainer = document.getElementById(`text-blocks-${pageId}`);
                    if (textContainer && categorized.text_content) {
                        textContainer.innerHTML = categorized.text_content.map(block => `
                            <label class="flex items-start gap-2 p-2 hover:bg-white rounded cursor-pointer border border-transparent hover:border-gray-200 transition-all">
                                <input type="checkbox" value="${block.id}" onchange="updateSelectedCount(${pageId}, 'text')" class="mt-1 text-blue-primary">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-semibold text-gray-700">${block.heading_level || 'Paragraph'}:</span>
                                    <span class="text-xs text-gray-600 break-words">"${block.preview}"</span>
                                </div>
                            </label>
                        `).join('');
                    }
                    
                    // Render visual/CTA blocks
                    const visualContainer = document.getElementById(`visual-blocks-${pageId}`);
                    if (visualContainer) {
                        const visualBlocks = [...(categorized.cta_elements || []), ...(categorized.visual_content || [])];
                        if (visualBlocks.length > 0) {
                            visualContainer.innerHTML = visualBlocks.map(block => `
                                <label class="flex items-start gap-2 p-2 hover:bg-white rounded cursor-pointer border border-transparent hover:border-gray-200 transition-all">
                                    <input type="checkbox" value="${block.id}" onchange="updateSelectedCount(${pageId}, 'visual')" class="mt-1 text-purple-accent">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs font-semibold text-gray-700">${getBlockTypeLabel(block.blockName)}:</span>
                                        <span class="text-xs text-gray-600 break-words">"${block.preview}"</span>
                                    </div>
                                </label>
                            `).join('');
                        } else {
                            visualContainer.innerHTML = '<div class="text-xs text-gray-500 text-center py-4">No visual/CTA content found</div>';
                        }
                    }
                    
                    // Render product blocks
                    if (stats.product_blocks > 0) {
                        const productContainer = document.getElementById(`product-blocks-${pageId}`);
                        if (productContainer && categorized.product_content) {
                            productContainer.innerHTML = categorized.product_content.map(block => `
                                <label class="flex items-start gap-2 p-2 hover:bg-white rounded cursor-pointer border border-transparent hover:border-gray-200 transition-all">
                                    <input type="checkbox" value="${block.id}" onchange="updateSelectedCount(${pageId}, 'product')" class="mt-1 text-green-600">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-xs font-semibold text-gray-700">${getBlockTypeLabel(block.blockName)}:</span>
                                        <span class="text-xs text-gray-600 break-words">"${block.preview}"</span>
                                    </div>
                                </label>
                            `).join('');
                        }
                    }
                }
                
                // Update "Optimize Selected (N)" button text
                function updateSelectedCount(pageId, sectionType) {
                    const checkboxes = document.querySelectorAll(`#${sectionType}-blocks-${pageId} input[type="checkbox"]:checked`);
                    const count = checkboxes.length;
                    const btn = document.getElementById(`${sectionType}-optimize-btn-${pageId}`);
                    if (btn) {
                        btn.textContent = `Optimize Selected (${count})`;
                    }
                }
                
                // Select all blocks in a section
                function selectAllBlocks(pageId, sectionType) {
                    const checkboxes = document.querySelectorAll(`#${sectionType}-blocks-${pageId} input[type="checkbox"]`);
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    
                    checkboxes.forEach(cb => cb.checked = !allChecked);
                    updateSelectedCount(pageId, sectionType);
                }
                
                // Optimize selected blocks (stub for now)
                function optimizeSelectedBlocks(pageId, sectionType) {
                    const checkboxes = document.querySelectorAll(`#${sectionType}-blocks-${pageId} input[type="checkbox"]:checked`);
                    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
                    
                    if (selectedIds.length === 0) {
                        alert('Please select at least one block to optimize');
                        return;
                    }
                    
                    // TODO: Connect to backend agent
                    console.log(`🚀 Optimizing ${selectedIds.length} blocks from ${sectionType} section for page ${pageId}`);
                    console.log('Selected block IDs:', selectedIds);
                    
                    // Show placeholder message
                    alert(`Ready to optimize ${selectedIds.length} ${sectionType} block(s)!\\n\\n(Backend connection coming in next step)`);
                }
                
                // Optimize entire page (stub for now)
                function optimizeEntirePage(pageId) {
                    console.log(`🚀 Optimizing entire page ${pageId}`);
                    
                    // TODO: Connect to backend agent
                    alert('Ready to optimize entire page!\\n\\n(Backend connection coming in next step)');
                }
                
                // Helper function to get block type label
                function getBlockTypeLabel(blockName) {
                    const labels = {
                        'core/button': 'Button',
                        'core/buttons': 'Buttons',
                        'core/image': 'Image Alt',
                        'core/gallery': 'Gallery',
                        'core/video': 'Video',
                        'core/cover': 'Cover Image',
                        'core/media-text': 'Media & Text'
                    };
                    return labels[blockName] || blockName.replace('core/', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                }

                // Tab switching functionality
                let currentTab = 'post'; // Default to Content/Blog tab
                
                function switchTab(tabType) {
                    currentTab = tabType;
                    
                    // Update tab button styles
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.classList.remove('bg-blue-50', 'text-blue-primary', 'border-2', 'border-blue-primary');
                        btn.classList.add('text-gray-600', 'hover:bg-gray-100');
                    });
                    
                    const activeTab = document.getElementById(`tab-${tabType}`);
                    if (activeTab) {
                        activeTab.classList.remove('text-gray-600', 'hover:bg-gray-100');
                        activeTab.classList.add('bg-blue-50', 'text-blue-primary', 'border-2', 'border-blue-primary');
                    }
                    
                    // Update title based on tab
                    const titleEl = document.getElementById('tab-title');
                    const titles = {
                        'product': 'Product Content Management',
                        'post': 'Blog Content Management',
                        'page': 'Page Content Management'
                    };
                    if (titleEl) titleEl.textContent = titles[tabType] || 'Content Management';
                    
                    // Filter content by post type
                    filterByPostType(tabType);
                    
                    // Clear search and reset dropdown
                    const searchEl = document.getElementById('search-pages');
                    if (searchEl) searchEl.value = '';
                    const selectEl = document.getElementById('select-page');
                    if (selectEl) selectEl.value = '';
                }
                
                function filterByPostType(postType) {
                    // Filter list items
                    document.querySelectorAll('.page-item').forEach(item => {
                        const itemType = item.getAttribute('data-type');
                        if (itemType === postType) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    // Filter dropdown options
                    const selectEl = document.getElementById('select-page');
                    if (selectEl) {
                        Array.from(selectEl.options).forEach(option => {
                            if (option.value === '') return; // Keep the placeholder
                            const optionType = option.getAttribute('data-type');
                            option.style.display = optionType === postType ? '' : 'none';
                        });
                    }
                }

                // Search and dropdown behavior
                const searchEl = document.getElementById('search-pages');
                if (searchEl) {
                    searchEl.addEventListener('input', function(e){
                        const q = (e.target.value || '').trim().toLowerCase();
                        document.querySelectorAll('#pages-list > div[id^="item-"]').forEach(function(item){
                            const title = item.getAttribute('data-title') || '';
                            const itemType = item.getAttribute('data-type');
                            // Only show if matches search AND current tab
                            if (title.indexOf(q) !== -1 && itemType === currentTab) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
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
                
                // Initialize with default tab
                switchTab('post');

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
    public function admin_page() {
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
                                <span>API Integration</span>
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
     * API Integration page callback
     */
    public function api_integration_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'ai_config_nonce')) {
            if (isset($_POST['ai_model_provider'])) {
                update_option('ai_model_provider', sanitize_text_field($_POST['ai_model_provider']));
            }
            if (isset($_POST['ai_api_key'])) {
                update_option('ai_api_key', sanitize_text_field($_POST['ai_api_key']));
            }
            if (isset($_POST['ai_model_id'])) {
                update_option('ai_model_id', sanitize_text_field($_POST['ai_model_id']));
            }
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'seo-optimizer') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="seo-optimizer-container">
                <div class="seo-optimizer-form">
                    <h2><?php _e('API Integration', 'seo-optimizer'); ?></h2>
                    <p><?php _e('Configure and manage API integrations for enhanced SEO functionality.', 'seo-optimizer'); ?></p>
                    
                    <!-- AI Model Configuration Panel -->
                    <div class="ai-config-panel" style="margin: 20px 0; border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #fff;">
                        <h3 style="margin-top: 0; color: #333;"><?php _e('AI Model Configuration', 'seo-optimizer'); ?></h3>
                        <p style="color: #666; margin-bottom: 20px;"><?php _e('Configure your preferred AI model provider and settings for content enhancement and meta generation.', 'seo-optimizer'); ?></p>
                        
                        <form method="post" action="" id="ai-config-form">
                            <?php wp_nonce_field('ai_config_nonce'); ?>
                            <?php
                            // settings_fields('seo_optimizer_api_settings');
                            // do_settings_sections('seo_optimizer_api_settings');
                            ?>
                            
                            <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <!-- AI Model Provider Selection -->
                                <div class="form-group">
                                    <label for="ai_model_provider" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                        <?php _e('AI Model Provider', 'seo-optimizer'); ?>
                                    </label>
                                    <select id="ai_model_provider" 
                                            name="ai_model_provider" 
                                            class="regular-text" 
                                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                            onchange="updateModelInfo()">
                                        <option value=""><?php _e('Select AI Model Provider', 'seo-optimizer'); ?></option>
                                        <option value="openai" <?php selected(get_option('ai_model_provider', ''), 'openai'); ?>>
                                            <?php _e('OpenAI', 'seo-optimizer'); ?>
                                        </option>
                                        <option value="grok" <?php selected(get_option('ai_model_provider', ''), 'grok'); ?>>
                                            <?php _e('Grok (xAI)', 'seo-optimizer'); ?>
                                        </option>
                                        <option value="anthropic" <?php selected(get_option('ai_model_provider', ''), 'anthropic'); ?>>
                                            <?php _e('Anthropic (Claude)', 'seo-optimizer'); ?>
                                        </option>
                                        <option value="gemini" <?php selected(get_option('ai_model_provider', ''), 'gemini'); ?>>
                                            <?php _e('Google Gemini', 'seo-optimizer'); ?>
                                        </option>
                                    </select>
                                    <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                        <?php _e('Choose your preferred AI model provider', 'seo-optimizer'); ?>
                                    </p>
                    </div>
                                
                                <!-- Model ID Input -->
                                <div class="form-group">
                                    <label for="ai_model_id" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                        <?php _e('Model ID', 'seo-optimizer'); ?>
                                    </label>
                                    <input type="text" 
                                           id="ai_model_id" 
                                           name="ai_model_id" 
                                           value="<?php echo esc_attr(get_option('ai_model_id', '')); ?>" 
                                           class="regular-text" 
                                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                           placeholder="<?php _e('e.g., gpt-5, gpt-5-mini, gpt-4, claude-3-sonnet, gemini-pro', 'seo-optimizer'); ?>" />
                                    <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                        <?php _e('Specific model version to use (optional)', 'seo-optimizer'); ?>
                                    </p>
                </div>
            </div>
                            
                            <!-- API Key Input -->
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="ai_api_key" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    <?php _e('API Key', 'seo-optimizer'); ?>
                                </label>
                                <input type="password" 
                                       id="ai_api_key" 
                                       name="ai_api_key" 
                                       value="<?php echo esc_attr(get_option('ai_api_key', '')); ?>" 
                                       class="regular-text" 
                                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                       placeholder="<?php _e('Enter your API key', 'seo-optimizer'); ?>" />
                                <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                    <?php _e('Your API key for the selected provider', 'seo-optimizer'); ?>
                                </p>
        </div>
                            
                            <!-- Provider Information Panel -->
                            <div id="provider-info" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 15px; margin-bottom: 20px; display: none;">
                                <h4 style="margin-top: 0; color: #495057;"><?php _e('Provider Information', 'seo-optimizer'); ?></h4>
                                <div id="provider-details"></div>
                            </div>
                            
                            <!-- Save Button -->
                            <div class="form-actions" style="text-align: right;">
                                <?php submit_button(__('Save AI Configuration', 'seo-optimizer'), 'primary', 'submit', false, array('style' => 'padding: 10px 20px; font-size: 14px;')); ?>
                            </div>
                        </form>
                    </div>
                    
                    <!-- API Status Section -->
                    <div class="api-status-section" style="margin: 20px 0;">
                        <h3><?php _e('API Status', 'seo-optimizer'); ?></h3>
                        <div class="api-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                            <div class="api-status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                <h4><?php _e('AI Model Provider', 'seo-optimizer'); ?></h4>
                                <div class="status-indicator">
                                    <?php 
                                    $provider = get_option('ai_model_provider', '');
                                    $api_key = get_option('ai_api_key', '');
                                    if (!empty($provider) && !empty($api_key)): 
                                    ?>
                                        <span style="color: #46b450;">✓ <?php echo esc_html(ucfirst($provider)); ?> <?php _e('Configured', 'seo-optimizer'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;">✗ <?php _e('Not Configured', 'seo-optimizer'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="description">
                                    <?php _e('AI model for content enhancement and meta generation', 'seo-optimizer'); ?>
                                </p>
                            </div>
                            
                            <div class="api-status-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                <h4><?php _e('SEO Meta API', 'seo-optimizer'); ?></h4>
                                <div class="status-indicator">
                                    <span style="color: #46b450;">✓ <?php _e('Active', 'seo-optimizer'); ?></span>
                                </div>
                                <p class="description">
                                    <?php _e('Internal API for meta tag optimization', 'seo-optimizer'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Features Section -->
                    <div class="api-features-section" style="margin: 20px 0;">
                        <h3><?php _e('Available API Features', 'seo-optimizer'); ?></h3>
                        <div class="features-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                            <div class="feature-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                <h4><?php _e('AI Content Enhancement', 'seo-optimizer'); ?></h4>
                                <p><?php _e('Automatically enhance your content using AI language models for better SEO performance.', 'seo-optimizer'); ?></p>
                                <div class="feature-status">
                                    <?php if (!empty(get_option('ai_api_key', ''))): ?>
                                        <span style="color: #46b450;">✓ <?php _e('Enabled', 'seo-optimizer'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;">✗ <?php _e('Requires API Key', 'seo-optimizer'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="feature-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                <h4><?php _e('Smart Meta Generation', 'seo-optimizer'); ?></h4>
                                <p><?php _e('Generate optimized meta titles and descriptions using AI analysis of your content.', 'seo-optimizer'); ?></p>
                                <div class="feature-status">
                                    <?php if (!empty(get_option('ai_api_key', ''))): ?>
                                        <span style="color: #46b450;">✓ <?php _e('Enabled', 'seo-optimizer'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;">✗ <?php _e('Requires API Key', 'seo-optimizer'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="feature-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                                <h4><?php _e('Content Summarization', 'seo-optimizer'); ?></h4>
                                <p><?php _e('Automatically generate content summaries to improve readability and SEO.', 'seo-optimizer'); ?></p>
                                <div class="feature-status">
                                    <?php if (!empty(get_option('ai_api_key', ''))): ?>
                                        <span style="color: #46b450;">✓ <?php _e('Enabled', 'seo-optimizer'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;">✗ <?php _e('Requires API Key', 'seo-optimizer'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Usage Instructions -->
                    <div class="api-instructions-section" style="margin: 20px 0;">
                        <h3><?php _e('Getting Started', 'seo-optimizer'); ?></h3>
                        <div class="instructions-content" style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
                            <ol>
                                <li><?php _e('Select your preferred AI model provider from the dropdown above', 'seo-optimizer'); ?></li>
                                <li><?php _e('Obtain an API key from your chosen provider:', 'seo-optimizer'); ?>
                                    <ul style="margin-top: 10px;">
                                        <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('Get OpenAI API Key', 'seo-optimizer'); ?></a></li>
                                        <li><strong>Grok (xAI):</strong> <a href="https://console.x.ai/" target="_blank"><?php _e('Get Grok API Key', 'seo-optimizer'); ?></a></li>
                                        <li><strong>Anthropic:</strong> <a href="https://console.anthropic.com/" target="_blank"><?php _e('Get Claude API Key', 'seo-optimizer'); ?></a></li>
                                        <li><strong>Google Gemini:</strong> <a href="https://makersuite.google.com/app/apikey" target="_blank"><?php _e('Get Gemini API Key', 'seo-optimizer'); ?></a></li>
                                    </ul>
                                </li>
                                <li><?php _e('Enter your API key and optional model ID in the configuration form', 'seo-optimizer'); ?></li>
                                <li><?php _e('Save the settings to activate AI-powered features', 'seo-optimizer'); ?></li>
                                <li><?php _e('Use the Analysis page to generate AI-powered SEO recommendations', 'seo-optimizer'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function updateModelInfo() {
            const provider = document.getElementById('ai_model_provider').value;
            const infoPanel = document.getElementById('provider-info');
            const detailsDiv = document.getElementById('provider-details');
            
            if (!provider) {
                infoPanel.style.display = 'none';
                return;
            }
            
            const providerInfo = {
                'openai': {
                    name: 'OpenAI',
                    description: 'Advanced language models including GPT-5, GPT-5-mini, GPT-4, GPT-3.5, and more.',
                    defaultModel: 'gpt-5',
                    website: 'https://openai.com',
                    pricing: 'Pay-per-token pricing'
                },
                'grok': {
                    name: 'Grok (xAI)',
                    description: 'Elon Musk\'s AI company offering advanced language models.',
                    defaultModel: 'grok-beta',
                    website: 'https://x.ai',
                    pricing: 'Competitive pricing'
                },
                'anthropic': {
                    name: 'Anthropic Claude',
                    description: 'Constitutional AI focused on helpfulness, harmlessness, and honesty.',
                    defaultModel: 'claude-3-sonnet-20240229',
                    website: 'https://anthropic.com',
                    pricing: 'Pay-per-token pricing'
                },
                'gemini': {
                    name: 'Google Gemini',
                    description: 'Google\'s multimodal AI model with strong reasoning capabilities.',
                    defaultModel: 'gemini-pro',
                    website: 'https://ai.google.dev',
                    pricing: 'Free tier available'
                }
            };
            
            const info = providerInfo[provider];
            if (info) {
                detailsDiv.innerHTML = `
                    <div style="margin-bottom: 10px;">
                        <strong>${info.name}</strong><br>
                        <span style="color: #666;">${info.description}</span>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Default Model:</strong> <code>${info.defaultModel}</code>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Pricing:</strong> ${info.pricing}
                    </div>
                    <div>
                        <a href="${info.website}" target="_blank" style="color: #0073aa;">Visit ${info.name} Website →</a>
                    </div>
                `;
                infoPanel.style.display = 'block';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateModelInfo();
        });
        </script>
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
        
        // Call AI API to summarize
        $summary = $this->call_ai_api($clean_content);
        
        if (is_wp_error($summary)) {
            wp_send_json_error(__('AI API Error: ', 'seo-optimizer') . $summary->get_error_message());
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
                $enhanced = $this->call_ai_api_for_enhancement($clean_paragraph);
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
     * Call AI API to enhance a single paragraph
     */
    private function call_ai_api_for_enhancement($content) {
        $provider = get_option('ai_model_provider', '');
        $api_key = get_option('ai_api_key', '');
        $model_id = get_option('ai_model_id', '');
        
        if (empty($provider) || empty($api_key)) {
            return new WP_Error('no_api_config', __('AI API not configured. Please configure your AI provider in the API Integration page.', 'seo-optimizer'));
        }
        
        // Set default model if not specified
        if (empty($model_id)) {
            $default_models = array(
                'openai' => 'gpt-5',
                'grok' => 'grok-beta',
                'anthropic' => 'claude-3-sonnet-20240229',
                'gemini' => 'gemini-pro'
            );
            $model_id = isset($default_models[$provider]) ? $default_models[$provider] : 'gpt-5';
        }
        
        // Configure API endpoints and data based on provider
        $api_config = $this->get_api_config($provider, $api_key, $model_id);
        
        // Prepare request data based on provider
        $request_data = $this->prepare_api_request($provider, $model_id, $content, 'enhancement');
        
        // Prepare headers based on provider
        $headers = $this->prepare_api_headers($api_config);
        
        // Prepare URL (some providers need query parameters)
        $url = $api_config['url'];
        if ($api_config['auth_type'] === 'query') {
            $url .= '?key=' . $api_key;
        }
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return $this->parse_api_response($provider, $body);
    }
    
    /**
     * Call AI API to summarize content
     */
    private function call_ai_api($content) {
        $provider = get_option('ai_model_provider', '');
        $api_key = get_option('ai_api_key', '');
        $model_id = get_option('ai_model_id', '');
        
        if (empty($provider) || empty($api_key)) {
            return new WP_Error('no_api_config', __('AI API not configured. Please configure your AI provider in the API Integration page.', 'seo-optimizer'));
        }
        
        // Set default model if not specified
        if (empty($model_id)) {
            $default_models = array(
                'openai' => 'gpt-5',
                'grok' => 'grok-beta',
                'anthropic' => 'claude-3-sonnet-20240229',
                'gemini' => 'gemini-pro'
            );
            $model_id = isset($default_models[$provider]) ? $default_models[$provider] : 'gpt-5';
        }
        
        // Configure API endpoints and data based on provider
        $api_config = $this->get_api_config($provider, $api_key, $model_id);
        
        // Prepare request data based on provider
        $request_data = $this->prepare_api_request($provider, $model_id, $content, 'summarization');
        
        // Prepare headers based on provider
        $headers = $this->prepare_api_headers($api_config);
        
        // Prepare URL (some providers need query parameters)
        $url = $api_config['url'];
        if ($api_config['auth_type'] === 'query') {
            $url .= '?key=' . $api_key;
        }
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return $this->parse_api_response($provider, $body);
    }
    
    /**
     * Get API configuration based on provider
     */
    private function get_api_config($provider, $api_key, $model_id) {
        $configs = array(
            'openai' => array(
                'url' => 'https://api.openai.com/v1/chat/completions',
                'auth_type' => 'bearer',
                'api_key' => $api_key
            ),
            'grok' => array(
                'url' => 'https://api.x.ai/v1/chat/completions',
                'auth_type' => 'bearer',
                'api_key' => $api_key
            ),
            'anthropic' => array(
                'url' => 'https://api.anthropic.com/v1/messages',
                'auth_type' => 'x-api-key',
                'api_key' => $api_key
            ),
            'gemini' => array(
                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/' . $model_id . ':generateContent',
                'auth_type' => 'query',
                'api_key' => $api_key
            )
        );
        
        return isset($configs[$provider]) ? $configs[$provider] : $configs['openai'];
    }
    
    /**
     * Prepare API request data based on provider
     */
    private function prepare_api_request($provider, $model_id, $content, $task_type) {
        $system_prompts = array(
            'enhancement' => 'You are a professional content writer and editor. Your task is to enhance the given paragraph by improving its clarity, flow, and engagement while maintaining the original meaning and tone. Make the text more compelling and better structured without changing the core message.',
            'summarization' => 'You are a helpful assistant that creates concise, informative summaries of content. Summarize the following content in 2-3 sentences, focusing on the main points and key takeaways.'
        );
        
        $system_prompt = isset($system_prompts[$task_type]) ? $system_prompts[$task_type] : $system_prompts['enhancement'];
        
        switch ($provider) {
            case 'openai':
            case 'grok':
                return array(
                    'model' => $model_id,
            'messages' => array(
                array(
                    'role' => 'system',
                            'content' => $system_prompt
                        ),
                        array(
                            'role' => 'user',
                            'content' => $task_type === 'enhancement' ? 'Please enhance this paragraph to make it more engaging and well-written: ' . $content : $content
                        )
                    ),
                    'max_tokens' => $task_type === 'enhancement' ? 500 : 150,
                    'temperature' => 0.7
                );
                
            case 'anthropic':
                return array(
                    'model' => $model_id,
                    'max_tokens' => $task_type === 'enhancement' ? 500 : 150,
                    'messages' => array(
                        array(
                            'role' => 'user',
                            'content' => $system_prompt . "\n\n" . ($task_type === 'enhancement' ? 'Please enhance this paragraph: ' . $content : 'Please summarize this content: ' . $content)
                        )
                    )
                );
                
            case 'gemini':
                return array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array(
                                    'text' => $system_prompt . "\n\n" . ($task_type === 'enhancement' ? 'Please enhance this paragraph: ' . $content : 'Please summarize this content: ' . $content)
                                )
                            )
                        )
                    ),
                    'generationConfig' => array(
                        'maxOutputTokens' => $task_type === 'enhancement' ? 500 : 150,
                        'temperature' => 0.7
                    )
                );
                
            default:
                return array(
                    'model' => $model_id,
                    'messages' => array(
                        array(
                            'role' => 'system',
                            'content' => $system_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $content
                )
            ),
                    'max_tokens' => 500,
            'temperature' => 0.7
        );
        }
    }
        
    /**
     * Prepare API headers based on provider
     */
    private function prepare_api_headers($api_config) {
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        switch ($api_config['auth_type']) {
            case 'bearer':
                $headers['Authorization'] = 'Bearer ' . $api_config['api_key'];
                break;
            case 'x-api-key':
                $headers['x-api-key'] = $api_config['api_key'];
                $headers['anthropic-version'] = '2023-06-01';
                break;
            case 'query':
                // For Gemini, API key is passed as query parameter
                break;
        }
        
        return $headers;
    }
    
    /**
     * Parse API response based on provider
     */
    private function parse_api_response($provider, $response_body) {
        $result = json_decode($response_body, true);
        
        if (isset($result['error'])) {
            return new WP_Error('api_error', $result['error']['message']);
        }
        
        switch ($provider) {
            case 'openai':
            case 'grok':
                if (!isset($result['choices'][0]['message']['content'])) {
                    return new WP_Error('api_error', 'No content generated');
                }
                return trim($result['choices'][0]['message']['content']);
                
            case 'anthropic':
                if (!isset($result['content'][0]['text'])) {
                    return new WP_Error('api_error', 'No content generated');
                }
                return trim($result['content'][0]['text']);
                
            case 'gemini':
                if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return new WP_Error('api_error', 'No content generated');
                }
                return trim($result['candidates'][0]['content']['parts'][0]['text']);
                
            default:
                if (!isset($result['choices'][0]['message']['content'])) {
                    return new WP_Error('api_error', 'No content generated');
                }
                return trim($result['choices'][0]['message']['content']);
        }
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
