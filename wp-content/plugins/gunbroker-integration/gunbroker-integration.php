<?php
/**
 * Plugin Name: GunBroker Integration
 * Description: Sync WooCommerce products with GunBroker marketplace
 * Version: 1.0.0
 * Author: Your Name
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GUNBROKER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GUNBROKER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GUNBROKER_VERSION', '1.0.0');

/**
 * Main GunBroker Integration Class
 */
class GunBroker_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load plugin files
        $this->load_files();

        // Initialize components
        $this->init_components();

        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));

        // Add activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    private function load_files() {
        require_once GUNBROKER_PLUGIN_PATH . 'includes/class-gunbroker-api.php';
        require_once GUNBROKER_PLUGIN_PATH . 'includes/class-gunbroker-admin.php';
        require_once GUNBROKER_PLUGIN_PATH . 'includes/class-gunbroker-sync.php';
        require_once GUNBROKER_PLUGIN_PATH . 'includes/class-gunbroker-settings.php';
    }

    private function init_components() {
        // Initialize admin interface
        if (is_admin()) {
            new GunBroker_Admin();
            new GunBroker_Settings();
        }

        // Initialize sync functionality
        new GunBroker_Sync();
    }

    public function activate() {
        // Create database tables if needed
        $this->create_tables();

        // Set default options
        add_option('gunbroker_enabled', false);
        add_option('gunbroker_markup_percentage', 10);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('gunbroker_sync_inventory');
    }

    /**
     * Show admin notice if plugin needs configuration
     */
    public function admin_notices() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Only show on relevant pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'gunbroker') === false) {
            return;
        }

        $settings = new GunBroker_Settings();
        if (!$settings->is_configured()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>GunBroker Integration:</strong>
                    Plugin is not configured yet.
                    <a href="<?php echo admin_url('admin.php?page=gunbroker-integration'); ?>">Configure now</a>
                    or <a href="<?php echo admin_url('admin.php?page=gunbroker-help'); ?>">view setup guide</a>.
                </p>
            </div>
            <?php
        }
    }

    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Listings table
        $table_name = $wpdb->prefix . 'gunbroker_listings';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            gunbroker_id varchar(100) NOT NULL,
            status enum('active','inactive','error','pending') DEFAULT 'pending',
            last_sync datetime DEFAULT NULL,
            sync_data longtext,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_product (product_id),
            KEY idx_status (status),
            KEY idx_gunbroker_id (gunbroker_id)
        ) $charset_collate;";

        // Sync log table
        $log_table = $wpdb->prefix . 'gunbroker_sync_log';
        $log_sql = "CREATE TABLE $log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20),
            action varchar(50),
            status varchar(20),
            message text,
            timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_listing (listing_id),
            KEY idx_timestamp (timestamp)
        ) $charset_collate;";

        // Orders cache table
        $orders_table = $wpdb->prefix . 'gunbroker_orders';
        $orders_sql = "CREATE TABLE $orders_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id varchar(50) NOT NULL,
            order_data longtext NOT NULL,
            cached_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_order (order_id),
            KEY idx_cached_at (cached_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($log_sql);
        dbDelta($orders_sql);
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>GunBroker Integration</strong> requires WooCommerce to be installed and active.</p></div>';
    }
}

// Initialize the plugin
GunBroker_Integration::get_instance();