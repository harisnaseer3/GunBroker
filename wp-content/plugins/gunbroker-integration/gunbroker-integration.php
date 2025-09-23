<?php
/**
 * Plugin Name: GunBroker Integration
 * Description: Sync WooCommerce products with GunBroker marketplace
 * Version: 1.0.1
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
define('GUNBROKER_VERSION', '1.0.1');

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
        add_action('init', array($this, 'load_textdomain'));
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
        add_action('admin_enqueue_scripts', array($this, 'inject_toast_and_redirect'));

        // Add activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Create assets directory if it doesn't exist
        $this->create_assets_directory();
    }

    public function load_textdomain() {
        load_plugin_textdomain('gunbroker-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
        add_option('gunbroker_listing_duration', 7);
        add_option('gunbroker_sandbox_mode', true);
        add_option('gunbroker_auto_end_zero_stock', true);
        add_option('gunbroker_default_country', 'US');
        add_option('gunbroker_enable_buy_now', true);

        // Flush rewrite rules
        flush_rewrite_rules();

        // Log activation
        error_log('GunBroker Integration plugin activated');
    }

    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('gunbroker_sync_inventory');

        // Log deactivation
        error_log('GunBroker Integration plugin deactivated');
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
        if (!$screen || (strpos($screen->id, 'gunbroker') === false && $screen->id !== 'plugins')) {
            return;
        }

        $settings = new GunBroker_Settings();
        if (!$settings->is_configured()) {
            ?>
            <div class="notice notice-warning is-dismissible">
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

    // Inject toast popup and handle redirect after save
    public function inject_toast_and_redirect($hook) {
        if (!is_admin()) return;
        $notice = get_transient('gb_sync_notice_' . get_current_user_id());
        $redirect_flag = get_transient('gb_redirect_after_save_' . get_current_user_id());
        if ($notice || $redirect_flag) {
            delete_transient('gb_sync_notice_' . get_current_user_id());
            delete_transient('gb_redirect_after_save_' . get_current_user_id());
            $msg = $notice ? esc_js($notice['msg']) : '';
            $is_success = $notice && $notice['type'] === 'success';
            $bg = $is_success ? '#46b450' : ($notice && $notice['type']==='error' ? '#dc3232' : '#2271b1');
            $toast_js = $notice ? "(function($){ var $m=$('<div></div>').text('{$msg}').css({position:'fixed',top:'20px',right:'20px',zIndex:100000,padding:'12px 16px',borderRadius:'6px',background:'{$bg}',color:'#fff',boxShadow:'0 4px 12px rgba(0,0,0,.2)'}); $('body').append($m); setTimeout(function(){ $m.fadeOut(300,function(){ $(this).remove(); }); }, 4000); })(jQuery);" : '';
            $redir_js = '';
            if ($redirect_flag === 'gunbroker') {
                $url = esc_url(admin_url('admin.php?page=gunbroker-integration'));
                $redir_js = "setTimeout(function(){ window.location.href='{$url}'; }, 500);";
            }
            $inline = "jQuery(function($){ {$toast_js} {$redir_js} });";
            // Attach to WordPress admin's default jQuery handle
            wp_add_inline_script('jquery', $inline);
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
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_product (product_id),
            KEY idx_status (status),
            KEY idx_gunbroker_id (gunbroker_id),
            KEY idx_last_sync (last_sync)
        ) $charset_collate;";

        // Sync log table
        $log_table = $wpdb->prefix . 'gunbroker_sync_log';
        $log_sql = "CREATE TABLE $log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20),
            product_id bigint(20),
            action varchar(50),
            status varchar(20),
            message text,
            timestamp timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_listing (listing_id),
            KEY idx_product (product_id),
            KEY idx_timestamp (timestamp),
            KEY idx_status (status)
        ) $charset_collate;";

        // Orders cache table
        $orders_table = $wpdb->prefix . 'gunbroker_orders';
        $orders_sql = "CREATE TABLE $orders_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id varchar(50) NOT NULL,
            listing_id varchar(100),
            order_data longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            cached_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_order (order_id),
            KEY idx_listing_id (listing_id),
            KEY idx_cached_at (cached_at),
            KEY idx_status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($log_sql);
        dbDelta($orders_sql);

        // Update database version
        update_option('gunbroker_db_version', '1.0.1');
    }

    /**
     * Create assets directory and basic files
     */
    private function create_assets_directory() {
        $assets_dir = GUNBROKER_PLUGIN_PATH . 'assets';

        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }

        // Create basic admin.js file if it doesn't exist
        $admin_js_file = $assets_dir . '/admin.js';
        if (!file_exists($admin_js_file)) {
            $admin_js_content = "jQuery(document).ready(function($) {
    // Test connection functionality
    $('#test-connection').on('click', function() {
        var button = $(this);
        var resultDiv = $('#connection-result');
        
        button.prop('disabled', true).text('Testing...');
        resultDiv.html('');
        
        $.post(ajaxurl, {
            action: 'gunbroker_test_connection',
            nonce: gunbroker_ajax.nonce
        }, function(response) {
            if (response.success) {
                resultDiv.html('<div class=\"notice notice-success inline\"><p>' + response.data + '</p></div>');
                $('#api-status').html('<span class=\"dashicons dashicons-yes-alt\" style=\"color: green;\"></span> API Connection: <strong>Connected</strong>');
            } else {
                resultDiv.html('<div class=\"notice notice-error inline\"><p>Error: ' + response.data + '</p></div>');
                $('#api-status').html('<span class=\"dashicons dashicons-dismiss\" style=\"color: red;\"></span> API Connection: <strong>Failed</strong>');
            }
        }).fail(function() {
            resultDiv.html('<div class=\"notice notice-error inline\"><p>Connection test failed</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    });

    // Product sync functionality
    $('.sync-product').on('click', function() {
        var button = $(this);
        var productId = button.data('product-id');
        
        button.prop('disabled', true).text('Syncing...');
        
        $.post(ajaxurl, {
            action: 'gunbroker_sync_product',
            product_id: productId,
            nonce: gunbroker_ajax.nonce
        }, function(response) {
            if (response.success) {
                button.text('Synced!').css('background', '#46b450');
                setTimeout(function() {
                    button.prop('disabled', false).text('Sync Now').css('background', '');
                    location.reload(); // Refresh to show updated status
                }, 2000);
            } else {
                alert('Sync failed: ' + response.data);
                button.prop('disabled', false).text('Sync Now');
            }
        }).fail(function() {
            alert('Sync request failed');
            button.prop('disabled', false).text('Sync Now');
        });
    });
});";

            file_put_contents($admin_js_file, $admin_js_content);
        }

        // Create basic admin.css file if it doesn't exist
        $admin_css_file = $assets_dir . '/admin.css';
        if (!file_exists($admin_css_file)) {
            $admin_css_content = ".gunbroker-admin-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.gunbroker-main-content {
    flex: 2;
}

.gunbroker-sidebar {
    flex: 1;
    max-width: 300px;
}

.gunbroker-status-card, .gunbroker-quick-stats {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.gunbroker-status-card h3, .gunbroker-quick-stats h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.status-success { 
    color: #46b450; 
    font-weight: bold; 
}

.status-error { 
    color: #dc3232; 
    font-weight: bold; 
}

.status-pending { 
    color: #ffb900; 
    font-weight: bold; 
}

.gunbroker-help-content {
    max-width: 800px;
}

.gunbroker-help-content h2 {
    border-bottom: 1px solid #ccd0d4;
    padding-bottom: 10px;
    margin-top: 30px;
}

.gunbroker-help-content ul, .gunbroker-help-content ol {
    margin-left: 20px;
}

.notice.inline {
    margin: 5px 0;
    padding: 5px 12px;
}

.sync-product {
    margin-right: 5px;
}

@media (max-width: 768px) {
    .gunbroker-admin-container {
        flex-direction: column;
    }
    
    .gunbroker-sidebar {
        max-width: 100%;
    }
}";

            file_put_contents($admin_css_file, $admin_css_content);
        }
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>GunBroker Integration</strong> requires WooCommerce to be installed and active.</p></div>';
    }

    /**
     * Get plugin information
     */
    public static function get_plugin_info() {
        return array(
            'name' => 'GunBroker Integration',
            'version' => GUNBROKER_VERSION,
            'path' => GUNBROKER_PLUGIN_PATH,
            'url' => GUNBROKER_PLUGIN_URL
        );
    }

    /**
     * Log debug information
     */
    public static function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("GunBroker [{$level}]: {$message}");
        }
    }
}

// Initialize the plugin
GunBroker_Integration::get_instance();