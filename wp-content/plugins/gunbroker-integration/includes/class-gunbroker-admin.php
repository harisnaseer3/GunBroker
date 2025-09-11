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
        add_action('wp_ajax_gunbroker_debug_test', array($this, 'debug_test_ajax'));
        add_action('wp_ajax_gunbroker_fetch_listings', array($this, 'fetch_gunbroker_listings_ajax'));
        add_action('wp_ajax_gunbroker_debug_credentials', array($this, 'debug_credentials_ajax'));
        add_action('wp_ajax_gunbroker_test_raw_auth', array($this, 'test_raw_auth_ajax'));
        add_action('wp_ajax_gunbroker_test_endpoints', array($this, 'test_endpoints_ajax'));
        add_action('wp_ajax_gunbroker_debug_listing_data', array($this, 'debug_listing_data_ajax'));
        add_action('wp_ajax_gunbroker_show_logs', array($this, 'show_logs_ajax'));
        add_action('wp_ajax_gunbroker_test_product_endpoints', array($this, 'test_product_endpoints_ajax'));
        add_action('wp_ajax_gunbroker_discover_endpoints', array($this, 'discover_endpoints_ajax'));
        add_action('wp_ajax_gunbroker_test_all_endpoints', array($this, 'test_all_endpoints_ajax'));

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
            'Browse GunBroker',
            'Browse GunBroker',
            'manage_options',
            'gunbroker-listings',
            array($this, 'gunbroker_listings_page')
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
     * FIXED: Debug API credentials with correct URLs
     */
    public function debug_credentials_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $dev_key = get_option('gunbroker_dev_key');
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $sandbox = get_option('gunbroker_sandbox_mode');

        $debug_info = array(
            'dev_key_length' => strlen($dev_key),
            'dev_key_start' => substr($dev_key, 0, 8) . '...',
            'username' => $username,
            'password_length' => strlen($password),
            'sandbox_mode' => $sandbox ? 'Yes' : 'No',
            // FIXED: Use correct URLs
            'base_url' => $sandbox ? 'https://api.sandbox.gunbroker.com/v1/' : 'https://api.gunbroker.com/v1/'
        );

        wp_send_json_success($debug_info);
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
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
        }

        // Apply bulk settings to product
        update_post_meta($product_id, '_gunbroker_enabled', 'yes');

        // Force fresh authentication before sync
        $api = new GunBroker_API();
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $api->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            wp_send_json_error('Authentication failed: ' . $auth_result->get_error_message());
        }

        // Now sync the product
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

        // Use the correct working endpoint from your permissions
        $orders_response = $api->get_sold_orders();

        if (is_wp_error($orders_response)) {
            wp_send_json_error('Failed to load orders: ' . $orders_response->get_error_message());
        }

        // Transform the orders data for display
        $orders = array();
        if (isset($orders_response['results']) && is_array($orders_response['results'])) {
            foreach ($orders_response['results'] as $order) {
                $orders[] = array(
                    'id' => $order['orderID'] ?? $order['OrderID'] ?? 'N/A',
                    'date' => $order['orderDate'] ?? $order['OrderDate'] ?? '',
                    'product_name' => $order['itemTitle'] ?? $order['ItemTitle'] ?? 'Unknown Item',
                    'buyer_name' => $order['buyerName'] ?? $order['BuyerName'] ?? 'Unknown Buyer',
                    'buyer_location' => $order['buyerLocation'] ?? $order['BuyerLocation'] ?? '',
                    'amount' => $order['totalAmount'] ?? $order['TotalAmount'] ?? 0,
                    'status' => $this->map_order_status($order['orderStatus'] ?? $order['OrderStatus'] ?? 'Unknown'),
                    'tracking_number' => $order['trackingNumber'] ?? $order['TrackingNumber'] ?? '',
                    'carrier' => $order['shippingCarrier'] ?? $order['ShippingCarrier'] ?? '',
                    'url' => 'https://www.sandbox.gunbroker.com/order/' . ($order['orderID'] ?? $order['OrderID'] ?? '')
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

    /**
     * Test individual components
     */
    public function debug_test_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $api = new GunBroker_API();

        // Test 1: Check credentials
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $dev_key = get_option('gunbroker_dev_key');

        error_log('DEBUG TEST: Username: ' . $username);
        error_log('DEBUG TEST: Dev Key: ' . substr($dev_key, 0, 8) . '...');

        // Test 2: Try authentication
        $auth_result = $api->authenticate($username, $password);
        error_log('DEBUG TEST: Auth result: ' . (is_wp_error($auth_result) ? $auth_result->get_error_message() : 'SUCCESS'));

        // Test 3: Try simple API call
        $test_call = $api->make_request('Users/AccountInfo');
        error_log('DEBUG TEST: API call result: ' . (is_wp_error($test_call) ? $test_call->get_error_message() : 'SUCCESS'));

        wp_send_json_success('Debug test complete - check logs');
    }

    /**
     * GunBroker listings browser page
     */
    public function gunbroker_listings_page() {
        include_once GUNBROKER_PLUGIN_PATH . 'templates/admin/gunbroker-listings.php';
    }

    /**
     * Fetch GunBroker listings via AJAX
     */
    public function fetch_gunbroker_listings_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api = new GunBroker_API();

        // Get listings type from request
        $listing_type = sanitize_text_field($_POST['listing_type'] ?? 'user');

        if ($listing_type === 'user') {
            // Get user's own listings using working endpoint
            $result = $api->get_user_listings();
        } else {
            // Search public listings using working endpoint
            $search_term = sanitize_text_field($_POST['search_term'] ?? '');
            $category = intval($_POST['category'] ?? 0);

            $search_params = array(
                'PageSize' => 20,
                'PageIndex' => 1
            );

            if (!empty($search_term)) {
                $search_params['Keywords'] = $search_term;
            }
            if (!empty($category)) {
                $search_params['CategoryID'] = $category;
            }

            $result = $api->search_gunbroker_items($search_params);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Process the results - handle different response formats
        $listings = array();
        $items_key = isset($result['results']) ? 'results' : (isset($result['Items']) ? 'Items' : null);

        $is_sandbox = get_option('gunbroker_sandbox_mode', true);
        $website_url = $is_sandbox ? 'https://www.sandbox.gunbroker.com/' : 'https://www.gunbroker.com/';

        if ($items_key && is_array($result[$items_key])) {
            foreach ($result[$items_key] as $item) {
                $listings[] = array(
                    'id' => $item['itemID'] ?? $item['ItemID'] ?? 'N/A',
                    'title' => $item['title'] ?? $item['Title'] ?? 'No Title',
                    'price' => $item['buyNowPrice'] ?? $item['BuyNowPrice'] ?? $item['currentPrice'] ?? $item['CurrentPrice'] ?? 0,
                    'end_date' => $item['endDate'] ?? $item['EndDate'] ?? '',
                    'category' => $item['categoryName'] ?? $item['CategoryName'] ?? 'Unknown',
                    'condition' => $item['condition'] ?? $item['Condition'] ?? 'Unknown',
                    'image_url' => isset($item['pictureURLs']) ? $item['pictureURLs'][0] ?? '' : ($item['PictureURLs'][0] ?? ''),
//                    'url' => 'https://www.sandbox.gunbroker.com/item/' . ($item['itemID'] ?? $item['ItemID'] ?? '')

                    'url' => $website_url . 'item/' . ($item['itemID'] ?? $item['ItemID'] ?? '')
                );
            }
        }

        wp_send_json_success(array(
            'listings' => $listings,
            'total' => $result['count'] ?? $result['Count'] ?? count($listings),
            'debug_response' => $result // Include full response for debugging
        ));
    }

    /**
     * FIXED: Test raw authentication with correct sandbox URL
     */
    public function test_raw_auth_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $dev_key = get_option('gunbroker_dev_key');
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $sandbox = get_option('gunbroker_sandbox_mode');

        // FIXED: Use correct URL based on sandbox mode
        $base_url = $sandbox
            ? 'https://api.sandbox.gunbroker.com/v1/'
            : 'https://api.gunbroker.com/v1/';

        // Make direct auth request
        $response = wp_remote_post($base_url . 'Users/AccessToken', array(
            'headers' => array(
                'X-DevKey' => $dev_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'Username' => $username,
                'Password' => $password
            )),
            'timeout' => 30,
            'sslverify' => true
        ));

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        wp_send_json_success(array(
            'url' => $base_url . 'Users/AccessToken',
            'response_code' => $response_code,
            'response_body' => $body,
            'sandbox_mode' => $sandbox ? 'Yes' : 'No',
            'headers_sent' => array(
                'X-DevKey' => substr($dev_key, 0, 8) . '...',
                'Content-Type' => 'application/json'
            ),
            'body_sent' => json_encode(array(
                'Username' => $username,
                'Password' => '***'
            ))
        ));
    }

    /**
     * Test API endpoints
     */
    public function test_endpoints_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $api = new GunBroker_API();

        // Test authentication first
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $auth_result = $api->authenticate($username, $password);

        if (is_wp_error($auth_result)) {
            wp_send_json_error('Auth failed: ' . $auth_result->get_error_message());
        }

        // Test the working endpoints from your permissions
        $endpoints = array(
            // Working endpoints
            'Categories',
            'Items?PageSize=5',
            'ItemsSelling',
            'OrdersSold',
            'Users/AccountInfo',

            // Test some others
            'Items/Selling',
            'ItemsEnded',
            'ItemsSold'
        );

        $results = array();
        foreach ($endpoints as $endpoint) {
            $result = $api->make_request($endpoint);
            $results[$endpoint] = array(
                'success' => !is_wp_error($result),
                'error' => is_wp_error($result) ? $result->get_error_message() : null,
                'data_preview' => is_wp_error($result) ? null : substr(json_encode($result), 0, 200) . '...'
            );

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 second delay
        }

        wp_send_json_success($results);
    }

    /**
     * Debug product listing data
     */
    public function debug_listing_data_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error('Product not found');
        }

        $api = new GunBroker_API();
        $listing_data = $api->prepare_listing_data($product);

        wp_send_json_success(array(
            'product_name' => $product->get_name(),
            'product_price' => $product->get_price(),
            'listing_data' => $listing_data
        ));
    }

    public function show_logs_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $debug_log = ABSPATH . 'wp-content/debug.log';

        if (!file_exists($debug_log)) {
            wp_send_json_success('No debug log file found. Make sure WP_DEBUG_LOG is enabled.');
            return;
        }

        $log_content = file_get_contents($debug_log);
        $lines = explode("\n", $log_content);

        // Get last 50 lines that contain "GunBroker"
        $gunbroker_lines = array_filter($lines, function($line) {
            return strpos($line, 'GunBroker') !== false;
        });

        $recent_lines = array_slice(array_reverse($gunbroker_lines), 0, 50);

        wp_send_json_success(implode("\n", array_reverse($recent_lines)));
    }

    /**
     * Test product fetching endpoints
     */
    public function test_product_endpoints_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        $api = new GunBroker_API();

        // Authenticate first
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $auth_result = $api->authenticate($username, $password);

        if (is_wp_error($auth_result)) {
            wp_send_json_error('Auth failed: ' . $auth_result->get_error_message());
        }

        // Test the working endpoints for products
        $endpoints = array(
            'Items?PageSize=5',
            'Items?CategoryID=851&PageSize=5',
            'ItemsSelling',
            'ItemsEnded',
            'ItemsSold'
        );

        $results = array();
        foreach ($endpoints as $endpoint) {
            $result = $api->make_request($endpoint);
            $results[$endpoint] = array(
                'success' => !is_wp_error($result),
                'error' => is_wp_error($result) ? $result->get_error_message() : null,
                'has_items' => !is_wp_error($result) && (isset($result['results']) || isset($result['items']) || isset($result['data'])),
                'data_preview' => is_wp_error($result) ? null : substr(json_encode($result), 0, 300) . '...'
            );

            usleep(750000); // 0.75 second delay to avoid rate limiting
        }

        wp_send_json_success($results);
    }

    /**
     * Comprehensive endpoint discovery
     */
    public function discover_endpoints_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api = new GunBroker_API();
        $dev_key = get_option('gunbroker_dev_key');
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        if (empty($dev_key) || empty($username) || empty($password)) {
            wp_send_json_error('API credentials not configured');
        }

        // Get fresh token
        $auth_result = $api->authenticate($username, $password);
        $token = null;

        if (!is_wp_error($auth_result) && isset($auth_result['accessToken'])) {
            $token = $auth_result['accessToken'];
        } elseif (!is_wp_error($auth_result) && $api->get_access_token()) {
            $token = $api->get_access_token();
        }

        // Run discovery
        $discovery = new GunBroker_Endpoint_Discovery($dev_key, $token);
        $results = $discovery->discover_working_endpoints();

        // Find working endpoints
        $working_endpoints = array();
        foreach ($results as $endpoint => $result) {
            if ($result['no_auth']['success'] || $result['with_auth']['success']) {
                $working_endpoints[] = array(
                    'endpoint' => $endpoint,
                    'works_no_auth' => $result['no_auth']['success'],
                    'works_with_auth' => $result['with_auth']['success'],
                    'data_preview' => $result['no_auth']['success'] ?
                        $result['no_auth']['data_preview'] :
                        $result['with_auth']['data_preview']
                );
            }
        }

        wp_send_json_success(array(
            'all_results' => $results,
            'working_endpoints' => $working_endpoints,
            'summary' => count($working_endpoints) . ' working endpoints found out of ' . count($results) . ' tested'
        ));
    }

    public function test_all_endpoints_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api = new GunBroker_API();

        // Test authentication first
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $auth_result = $api->authenticate($username, $password);

        if (is_wp_error($auth_result)) {
            wp_send_json_error('Authentication failed: ' . $auth_result->get_error_message());
        }

        // Test all product endpoints
        $results = $api->test_all_product_endpoints();

        // Add summary
        $working_count = 0;
        $total_count = count($results);

        foreach ($results as $result) {
            if ($result['success']) {
                $working_count++;
            }
        }

        $summary = array(
            'total_tested' => $total_count,
            'working' => $working_count,
            'failed' => $total_count - $working_count,
            'success_rate' => round(($working_count / $total_count) * 100, 1) . '%'
        );

        wp_send_json_success(array(
            'summary' => $summary,
            'results' => $results
        ));
    }
}