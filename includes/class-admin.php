<?php
namespace CustomTableCRUD;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin functionality handler
 *
 * @since 2.0.0
 */
class Admin {
    /**
     * Constructor - set up action hooks
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu pages
     *
     * @return void
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Custom CRUD', 'custom-table-crud'),
            __('Custom CRUD', 'custom-table-crud'),
            'manage_options',
            'custom_crud_dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-database',
            30
        );
        
        add_submenu_page(
            'custom_crud_dashboard',
            __('Dashboard', 'custom-table-crud'),
            __('Dashboard', 'custom-table-crud'),
            'manage_options',
            'custom_crud_dashboard'
        );
        
        add_submenu_page(
            'custom_crud_dashboard',
            __('Shortcode Generator', 'custom-table-crud'),
            __('Shortcode Generator', 'custom-table-crud'),
            'manage_options',
            'custom_crud_shortcode',
            array($this, 'render_shortcode_page')
        );
        
        // Add new submenu page for table creation and management
        add_submenu_page(
            'custom_crud_dashboard',
            __('Table Manager', 'custom-table-crud'),
            __('Table Manager', 'custom-table-crud'),
            'manage_options',
            'custom_crud_table_manager',
            array($this, 'render_table_manager_page')
        );
    }
    
    /**
     * Render the dashboard page
     *
     * @return void
     */
    public function render_dashboard_page() {
        // Get database tables
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        
        // Include template
        include CUSTOM_TABLE_CRUD_PATH . 'templates/admin/dashboard.php';
    }
    
    /**
     * Render the shortcode generator page
     *
     * @return void
     */
    public function render_shortcode_page() {
        // Get database tables
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $selected_table = isset($_GET['table']) ? sanitize_text_field($_GET['table']) : '';
        
        // Include template
        include CUSTOM_TABLE_CRUD_PATH . 'templates/admin/shortcode-generator.php';
    }
    
    /**
     * Render the table manager page
     *
     * @return void
     */
    public function render_table_manager_page() {
        // Get database tables
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        
        // Get active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        
        // Get table manager instance
        $table_manager = new Table_Manager();
        
        // Get selected table for editing
        $selected_table = isset($_GET['table']) ? sanitize_text_field($_GET['table']) : '';
        
        // Include template
        include CUSTOM_TABLE_CRUD_PATH . 'templates/admin/table-manager.php';
    }
    
    /**
     * Get table information
     *
     * @param string $table_name Table name
     * @return array Table information
     */
    public function get_table_info($table_name) {
        global $wpdb;
        
        $info = array(
            'columns' => array(),
            'primary_key' => 'id',
            'record_count' => 0
        );
        
        // Get columns
        $columns = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM `%s`", 
            $table_name
        ));
        
        if ($columns) {
            foreach ($columns as $column) {
                $type = 'text';
                
                // Determine field type based on database column type
                if (strpos($column->Type, 'int') !== false || strpos($column->Type, 'float') !== false || strpos($column->Type, 'decimal') !== false) {
                    $type = 'number';
                } elseif (strpos($column->Type, 'date') !== false) {
                    $type = 'date';
                } elseif (strpos($column->Type, 'text') !== false || strpos($column->Type, 'longtext') !== false) {
                    $type = 'textarea';
                }
                
                $info['columns'][$column->Field] = array(
                    'type' => $type,
                    'required' => ($column->Null === 'NO'),
                    'default' => $column->Default,
                    'is_primary' => ($column->Key === 'PRI')
                );
                
                // Store primary key
                if ($column->Key === 'PRI') {
                    $info['primary_key'] = $column->Field;
                }
            }
        }
        
        // Get record count
        $info['record_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `%s`",
            $table_name
        ));
        
        return $info;
    }
}