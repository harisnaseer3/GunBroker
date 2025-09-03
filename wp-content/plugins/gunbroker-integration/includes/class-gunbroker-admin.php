<?php

class GunBroker_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_gunbroker_test_connection', array($this, 'test_connection_ajax'));
        add_action('wp_ajax_gunbroker_sync_product', array($this, 'sync_product_ajax'));
        add_action('wp_ajax_gunbroker_bulk_sync', array($this, 'bulk_sync_ajax'));
        add_action('wp_ajax_gunbroker_clear_logs', array($this, 'clear_logs_ajax'));
        add_action('wp_ajax_gunbroker_bulk_list_products', array($this, 'bulk_list_products_ajax'));
        add_action('wp_ajax_gunbroker_load_orders', array($this, 'load_orders_ajax'));
        add_action('wp_ajax_gunbroker_ship_order', array($this, 'ship_order_ajax'));

        // Add product list column
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'GunBroker Integration',
            'GunBroker',
            'manage_options',
            'gunbroker-integration',
            array($this, 'bulk_listing_page'),
            'dashicons-store',
            56
        );

        add_submenu_page(
            'gunbroker-integration',
            'List Products',
            'List Products',
            'manage_options',
            'gunbroker-integration',
            array($this, 'bulk_listing_page')
        );

        add_submenu_page(
            'gunbroker-integration',
            'Orders',
            'Orders',
            'manage_options',
            'gunbroker-orders',
            array($this, 'orders_page')
        );

        add_submenu_page(
            'gunbroker-integration',
            'Settings',
            'Settings',
            'manage_options',
            'gunbroker-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'gunbroker-integration',
            'Sync Status',
            'Sync Status',
            'manage_options',
            'gunbroker-sync-status',
            array($this, 'sync_status_page')
        );

        add_submenu_page(
            'gunbroker-integration',
            'Help',
            'Help',
            'manage_options',
            'gunbroker-help',
            array($this, 'help_page')
        );
    }

    /**
     * Main bulk listing page
     */
    public function bulk_listing_page() {
        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/bulk-listing.php';
    }

    /**
     * Orders management page
     */
    public function orders_page() {
        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/order-management.php';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/settings.php';
    }

    /**
     * Sync status page
     */
    public function sync_status_page() {
        // Get sync statistics
        global $wpdb;

        $total_products = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_gunbroker_enabled' AND meta_value = 'yes'
        ");

        $active_listings = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}gunbroker_listings 
            WHERE status = 'active'
        ");

        $pending_listings = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}gunbroker_listings 
            WHERE status = 'pending'
        ");

        $error_listings = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}gunbroker_listings 
            WHERE status = 'error'
        ");

        // Get recent sync logs
        $recent_logs = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}gunbroker_sync_log 
            ORDER BY timestamp DESC LIMIT 20
        ");

        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/sync-status.php';
    }

    /**
     * Help page
     */
    public function help_page() {
        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/help.php';
    }

    /**
     * Add product meta boxes
     */
    public function add_product_meta_boxes() {
        add_meta_box(
            'gunbroker-settings',
            'GunBroker Integration',
            array($this, 'product_meta_box'),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render product meta box
     */
    public function product_meta_box($post) {
        wp_nonce_field('gunbroker_product_meta', 'gunbroker_meta_nonce');

        $enabled = get_post_meta($post->ID, '_gunbroker_enabled', true);
        $custom_title = get_post_meta($post->ID, '_gunbroker_custom_title', true);
        $category = get_post_meta($post->ID, '_gunbroker_category', true);
        $listing_id = $this->get_gunbroker_listing_id($post->ID);
        $listing_status = $this->get_listing_status($post->ID);

        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/product-meta-box.php';
    }

    /**
     * Save product meta data
     */
    public function save_product_meta($post_id) {
        if (!isset($_POST['gunbroker_meta_nonce']) ||
            !wp_verify_nonce($_POST['gunbroker_meta_nonce'], 'gunbroker_product_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save GunBroker settings
        $enabled = isset($_POST['gunbroker_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_gunbroker_enabled', $enabled);

        if (isset($_POST['gunbroker_custom_title'])) {
            update_post_meta($post_id, '_gunbroker_custom_title', sanitize_text_field($_POST['gunbroker_custom_title']));
        }

        if (isset($_POST['gunbroker_category'])) {
            update_post_meta($post_id, '_gunbroker_category', sanitize_text_field($_POST['gunbroker_category']));
        }

        // If enabled, queue for sync
        if ($enabled === 'yes') {
            $sync = new GunBroker_Sync();
            $sync->queue_product_sync($post_id);
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'gunbroker') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script(
                'gunbroker-admin',
                GUNBROKER_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                GUNBROKER_VERSION,
                true
            );

            wp_enqueue_style(
                'gunbroker-admin',
                GUNBROKER_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                GUNBROKER_VERSION
            );

            wp_localize_script('gunbroker-admin', 'gunbroker_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gunbroker_ajax_nonce')
            ));
        }
    }

    /**
     * Test API connection via AJAX
     */
    public function test_connection_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api = new GunBroker_API();
        $connection_test = $api->test_connection();

        if ($connection_test) {
            wp_send_json_success('Connection successful!');
        } else {
            wp_send_json_error('Connection failed. Please check your API credentials.');
        }
    }

    /**
     * Sync single product via AJAX
     */
    public function sync_product_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $product_id = intval($_POST['product_id']);
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        $sync = new GunBroker_Sync();
        $result = $sync->sync_single_product($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Product synced successfully!');
        }
    }

    /**
     * Save plugin settings
     */
    private function save_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['gunbroker_dev_key'])) {
            update_option('gunbroker_dev_key', sanitize_text_field($_POST['gunbroker_dev_key']));
        }

        if (isset($_POST['gunbroker_username'])) {
            update_option('gunbroker_username', sanitize_text_field($_POST['gunbroker_username']));
        }

        if (isset($_POST['gunbroker_password'])) {
            update_option('gunbroker_password', sanitize_text_field($_POST['gunbroker_password']));
        }

        if (isset($_POST['gunbroker_markup_percentage'])) {
            update_option('gunbroker_markup_percentage', floatval($_POST['gunbroker_markup_percentage']));
        }

        if (isset($_POST['gunbroker_listing_duration'])) {
            update_option('gunbroker_listing_duration', intval($_POST['gunbroker_listing_duration']));
        }

        update_option('gunbroker_sandbox_mode', isset($_POST['gunbroker_sandbox_mode']));

        // Try to authenticate with new credentials
        if (!empty($_POST['gunbroker_username']) && !empty($_POST['gunbroker_password'])) {
            $api = new GunBroker_API();
            $auth_result = $api->authenticate($_POST['gunbroker_username'], $_POST['gunbroker_password']);

            if (is_wp_error($auth_result)) {
                add_settings_error('gunbroker_settings', 'auth_failed', $auth_result->get_error_message());
            } else {
                add_settings_error('gunbroker_settings', 'auth_success', 'Authentication successful!', 'updated');
            }
        }
    }

    /**
     * Get GunBroker listing ID for product
     */
    private function get_gunbroker_listing_id($product_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT gunbroker_id FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
            $product_id
        ));
    }

    /**
     * Bulk sync all products via AJAX
     */
    public function bulk_sync_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $sync = new GunBroker_Sync();
        $sync->sync_all_inventory();

        wp_send_json_success('Bulk sync initiated for all enabled products');
    }

    /**
     * Add GunBroker column to product list
     */
    public function add_product_columns($columns) {
        // Add column after the product name
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') {
                $new_columns['gunbroker_status'] = 'GunBroker';
            }
        }
        return $new_columns;
    }

    /**
     * Populate GunBroker column content
     */
    public function populate_product_columns($column, $post_id) {
        if ($column === 'gunbroker_status') {
            $enabled = get_post_meta($post_id, '_gunbroker_enabled', true);

            if ($enabled === 'yes') {
                $status = $this->get_listing_status($post_id);
                $listing_id = $this->get_gunbroker_listing_id($post_id);

                if ($status && $listing_id) {
                    echo '<span class="gunbroker-status-' . esc_attr($status) . '">';
                    echo ucfirst($status);
                    echo '</span>';
                    echo '<br><small>ID: ' . esc_html($listing_id) . '</small>';

                    // Quick sync button
                    echo '<br><button type="button" class="button-link sync-product-inline" data-product-id="' . esc_attr($post_id) . '" style="font-size: 11px; text-decoration: none;">Sync</button>';
                } else {
                    echo '<span class="gunbroker-status-pending">Enabled</span>';
                    echo '<br><small>Not synced yet</small>';
                }
            } else {
                echo '<span style="color: #666;">Disabled</span>';
            }
        }
    }

    /**
     * Bulk list products via AJAX
     */
    public function bulk_list_products_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $product_id = intval($_POST['product_id']);
        $settings = $_POST['settings'];

        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
        }

        // Apply bulk settings to product
        update_post_meta($product_id, '_gunbroker_enabled', 'yes');
        update_post_meta($product_id, '_gunbroker_markup', $settings['markup']);
        update_post_meta($product_id, '_gunbroker_duration', $settings['duration']);
        update_post_meta($product_id, '_gunbroker_category', $settings['category']);

        // Sync the product
        $sync = new GunBroker_Sync();
        $result = $sync->sync_single_product($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Product listed successfully');
        }
    }

    /**
     * Load orders from GunBroker via AJAX
     */
    public function load_orders_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api = new GunBroker_API();

        // Get orders from GunBroker API
        $orders_response = $api->make_request('Orders/Sold');

        if (is_wp_error($orders_response)) {
            wp_send_json_error('Failed to load orders: ' . $orders_response->get_error_message());
        }

        // Transform the orders data for display
        $orders = array();
        if (isset($orders_response['Orders']) && is_array($orders_response['Orders'])) {
            foreach ($orders_response['Orders'] as $order) {
                $orders[] = array(
                    'id' => $order['OrderID'],
                    'date' => $order['OrderDate'],
                    'product_name' => $order['ItemTitle'],
                    'buyer_name' => $order['BuyerName'],
                    'buyer_location' => $order['BuyerLocation'],
                    'amount' => $order['TotalAmount'],
                    'status' => $this->map_order_status($order['OrderStatus']),
                    'tracking_number' => $order['TrackingNumber'] ?? '',
                    'carrier' => $order['ShippingCarrier'] ?? '',
                    'url' => 'https://www.gunbroker.com/order/' . $order['OrderID']
                );
            }
        }

        // Store orders in database for quick access
        $this->cache_orders($orders);

        wp_send_json_success($orders);
    }

    /**
     * Ship order via AJAX
     */
    public function ship_order_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $order_id = sanitize_text_field($_POST['order_id']);
        $carrier = sanitize_text_field($_POST['carrier']);
        $tracking_number = sanitize_text_field($_POST['tracking_number']);
        $notes = sanitize_textarea_field($_POST['notes']);

        if (!$order_id || !$tracking_number) {
            wp_send_json_error('Order ID and tracking number are required');
        }

        $api = new GunBroker_API();

        // Update shipping info on GunBroker
        $shipping_data = array(
            'TrackingNumber' => $tracking_number,
            'ShippingCarrier' => $carrier,
            'ShippingNotes' => $notes
        );

        $result = $api->make_request("Orders/{$order_id}/Shipping", 'PUT', $shipping_data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Order marked as shipped successfully');
        }
    }

    /**
     * Map GunBroker order status to our internal status
     */
    private function map_order_status($gb_status) {
        $status_map = array(
            'New' => 'new',
            'Paid' => 'paid',
            'Shipped' => 'shipped',
            'Completed' => 'completed'
        );

        return $status_map[$gb_status] ?? 'new';
    }

    /**
     * Cache orders in database
     */
    private function cache_orders($orders) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gunbroker_orders';

        // Create orders table if it doesn't exist
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id varchar(50) NOT NULL,
            order_data longtext NOT NULL,
            cached_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_order (order_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Cache each order
        foreach ($orders as $order) {
            $wpdb->replace(
                $table_name,
                array(
                    'order_id' => $order['id'],
                    'order_data' => json_encode($order)
                ),
                array('%s', '%s')
            );
        }
    }

    /**
     * Clear error logs via AJAX
     */
    public function clear_logs_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;

        // Clear sync logs
        $deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}gunbroker_sync_log WHERE status = 'error'");

        wp_send_json_success("Cleared {$deleted} error log entries");
    }

    /**
     * Get listing status for product
     */
    private function get_listing_status($product_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
            $product_id
        ));
    }
}