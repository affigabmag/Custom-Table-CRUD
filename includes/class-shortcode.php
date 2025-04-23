<?php
namespace CustomTableCRUD;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler class
 *
 * @since 2.0.0
 */
class Shortcode {
    /**
     * Table manager instance
     * 
     * @var Table_Manager
     */
    private $table_manager;
    
    /**
     * Constructor - set up action hooks
     */
    public function __construct() {
        $this->table_manager = new Table_Manager();
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes() {
        add_shortcode('wp_table_manager', array($this, 'handle_shortcode'));
    }
    
/**
 * Process shortcode and return content
 *
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
public function handle_shortcode($atts = []) {
    $defaults = [
        'pagination' => 5,
        'table_view' => '',
        'showrecordscount' => 'true',
        'showform' => 'true',
        'showtable' => 'true',
        'showsearch' => 'true',
        'showpagination' => 'true',
        'showedit' => 'true',
        'showdelete' => 'true',
        'showactions' => 'true'  
    ];
    
    // Handle field attributes
    foreach ($atts as $key => $val) {
        if (preg_match('/^field\d+$/', $key)) {
            $defaults[$key] = '';
        }
    }
    
    $atts = shortcode_atts($defaults, $atts);
    
    // Log debug information if enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_file = CUSTOM_TABLE_CRUD_PATH . 'debug/shortcode_debug.log';
        
        // Create debug directory if it doesn't exist
        $debug_dir = dirname($log_file);
        if (!file_exists($debug_dir)) {
            wp_mkdir_p($debug_dir);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_data = "\n==== [$timestamp] ====\n";
        $log_data .= "[SHORTCODE ATTRIBUTES PARSED]\n" . print_r($atts, true);
        
        $columns = $this->parse_columns($atts);
        $log_data .= "\n[PARSED COLUMNS]\n" . print_r($columns, true);
        
        file_put_contents($log_file, $log_data, FILE_APPEND);
    }
    
    // Parse column definitions
    $columns = $this->parse_columns($atts);
    
    if (empty($columns)) {
        return '<div class="error-message">⚠️ ' . esc_html__('No valid fields defined in shortcode.', 'custom-table-crud') . '</div>';
    }
    
    if (empty($atts['table_view'])) {
        return '<div class="error-message">⚠️ ' . esc_html__('No table specified in shortcode.', 'custom-table-crud') . '</div>';
    }
    
    // Configure the table manager
    $config = [
        'table_name' => $atts['table_view'],
        'primary_key' => $this->get_primary_key($atts['table_view']),
        'columns' => $columns,
        'pagination' => intval($atts['pagination']),
        'showrecordscount' => strtolower($atts['showrecordscount']),
        'showform' => strtolower($atts['showform']),
        'showtable' => strtolower($atts['showtable']),
        'showsearch' => strtolower($atts['showsearch']),
        'showpagination' => strtolower($atts['showpagination']),
        'showedit' => strtolower($atts['showedit']),
        'showdelete' => strtolower($atts['showdelete']),
        'showactions' => strtolower($atts['showactions'])
    ];
    
    // Generate the table HTML
    return $this->table_manager->render_table($config);
}
    
    /**
     * Parse column definitions from shortcode attributes
     *
     * @param array $atts Shortcode attributes
     * @return array Parsed columns
     */
    private function parse_columns($atts) {
        $columns = [];
        
        foreach ($atts as $key => $val) {
            if (preg_match('/^field\d+$/', $key)) {
                $col = [];
                $segments = explode(';', $val);
                
                foreach ($segments as $seg) {
                    $parts = explode('=', $seg, 2);
                    if (count($parts) === 2) {
                        $k = trim($parts[0]);
                        $v = trim($parts[1], " '\"");
                        
                        if ($k && $v) {
                            $col[$k] = $v;
                        }
                    }
                }
                
                if (!empty($col['fieldname']) && !empty($col['displayname']) && !empty($col['displaytype'])) {
                    $columns[$col['fieldname']] = [
                        'label' => $col['displayname'],
                        'type' => $col['displaytype'],
                        'readonly' => isset($col['readonly']) ? $col['readonly'] : 'false'
                    ];
                    
                    // Add query parameter if it exists
                    if ($col['displaytype'] === 'query' && isset($col['query'])) {
                        $columns[$col['fieldname']]['query'] = $col['query'];
                    }
                }
            }
        }
        
        return $columns;
    }
    
    /**
     * Get the primary key for a table
     *
     * @param string $table_name Table name
     * @return string Primary key field name
     */
    private function get_primary_key($table_name) {
        global $wpdb;
        $primary_key = 'id';
        
        // Get primary key from database
        $query = "SHOW KEYS FROM $table_name WHERE Key_name = 'PRIMARY'";
        $results = $wpdb->get_results($query);
        
        if (!empty($results)) {
            $primary_key = $results[0]->Column_name;
        }
        
        return $primary_key;
    }
}