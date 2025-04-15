<?php
namespace CustomTableCRUD;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class responsible for initializing all components
 *
 * @since 2.0.0
 */
class Plugin_Core {
    /**
     * Admin component instance
     * 
     * @var Admin
     */
    private $admin;
    
    /**
     * Shortcode component instance
     * 
     * @var Shortcode
     */
    private $shortcode;
    
    /**
     * AJAX handler instance
     * 
     * @var Ajax_Handler
     */
    private $ajax_handler;
    
    /**
     * Table manager instance
     * 
     * @var Table_Manager
     */
    private $table_manager;
    
    /**
     * Initialize the plugin components
     *
     * @return void
     */
    public function init() {
        // Load text domain for translations
        add_action('init', array($this, 'load_textdomain'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Remove WordPress footer text on plugin pages
        add_filter('admin_footer_text', array($this, 'remove_admin_footer_text'), 99);
        
        // Initialize components
        $this->admin = new Admin();
        $this->shortcode = new Shortcode();
        $this->ajax_handler = new Ajax_Handler();
        $this->table_manager = new Table_Manager();
    }
    
    /**
     * Load plugin text domain for translations
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'custom-table-crud', 
            false, 
            dirname(plugin_basename(CUSTOM_TABLE_CRUD_PATH)) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend CSS and JS
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'custom-table-crud-style', 
            CUSTOM_TABLE_CRUD_URL . 'assets/css/custom-table-crud.css', 
            array(), 
            CUSTOM_TABLE_CRUD_VERSION
        );
        
        wp_enqueue_script(
            'custom-table-crud-script', 
            CUSTOM_TABLE_CRUD_URL . 'assets/js/custom-table-crud.js', 
            array('jquery'), 
            CUSTOM_TABLE_CRUD_VERSION, 
            true
        );
        
        wp_localize_script('custom-table-crud-script', 'ctcrudSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('custom-table-crud-nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this record?', 'custom-table-crud'),
                'recordAdded' => __('Record added successfully!', 'custom-table-crud'),
                'recordUpdated' => __('Record updated successfully!', 'custom-table-crud'),
                'recordDeleted' => __('Record deleted successfully!', 'custom-table-crud'),
                'pleaseCompleteAllFields' => __('Please complete all required fields.', 'custom-table-crud'),
            )
        ));
    }
    
    /**
     * Enqueue admin CSS and JS
     *
     * @param string $hook Current admin page
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'custom_crud') === false) {
            return;
        }
        
        wp_enqueue_style(
            'custom-table-crud-admin-style', 
            CUSTOM_TABLE_CRUD_URL . 'assets/css/custom-table-crud-admin.css', 
            array(), 
            CUSTOM_TABLE_CRUD_VERSION
        );
        
        wp_enqueue_script(
            'custom-table-crud-admin-script', 
            CUSTOM_TABLE_CRUD_URL . 'assets/js/custom-table-crud-admin.js', 
            array('jquery'), 
            CUSTOM_TABLE_CRUD_VERSION, 
            true
        );
        
        wp_localize_script('custom-table-crud-admin-script', 'ctcrudAdminSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('custom_crud_admin_nonce'), // This needs to match what we check in Ajax_Handler
            'i18n' => array(
                'shortcodeCopied' => __('Shortcode copied to clipboard!', 'custom-table-crud'),
                'noFieldsSelected' => __('Please select at least one field', 'custom-table-crud'),
                'noTableSelected' => __('Please select a table', 'custom-table-crud'),
                'loadingFields' => __('Loading fields...', 'custom-table-crud'),
                'errorLoadingFields' => __('Error loading fields', 'custom-table-crud'),
                'confirmDeleteTable' => __('Are you sure you want to delete this table? This action cannot be undone!', 'custom-table-crud'),
                'addAnotherField' => __('Add Another Field', 'custom-table-crud'),
                'removeField' => __('Remove Field', 'custom-table-crud'),
            )
        ));
    }
    
    /**
     * Remove WordPress admin footer text
     *
     * @param string $text Footer text
     * @return string Empty string on plugin pages, original text elsewhere
     */
    public function remove_admin_footer_text($text) {
        $screen = get_current_screen();
        if (strpos($screen->id, 'custom_crud') !== false) {
            return '';
        }
        return $text;
    }
    
    /**
     * Plugin activation hook
     *
     * @return void
     */
    public static function activate() {
        // Create necessary directories
        $dirs = array(
            CUSTOM_TABLE_CRUD_PATH . 'assets/css',
            CUSTOM_TABLE_CRUD_PATH . 'assets/js',
        );
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
        
        // Store the current version
        update_option('custom_table_crud_version', CUSTOM_TABLE_CRUD_VERSION);
        
        // Maybe flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation hook
     *
     * @return void
     */
    public static function deactivate() {
        // Clean up transients or other temporary data
        delete_transient('custom_table_crud_table_list');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}