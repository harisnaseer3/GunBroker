<?php

class GunBroker_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post', array($this, 'save_product_meta'));
        // After creating or updating a product, redirect to GunBroker listings page
        add_filter('redirect_post_location', array($this, 'redirect_after_product_save'), 10, 2);
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
        add_action('wp_ajax_gunbroker_get_subcategories', array($this, 'get_subcategories_ajax'));
        add_action('wp_ajax_gunbroker_get_top_categories', array($this, 'get_top_categories_ajax'));
        add_action('wp_ajax_gunbroker_end_listing', array($this, 'end_listing_ajax'));

        // Add product list column
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
        
        // Ensure database tables exist on admin init
        add_action('admin_init', array($this, 'ensure_tables_exist'));
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

        // Always enable GunBroker sync for all products
        update_post_meta($post_id, '_gunbroker_enabled', 'yes');

        if (isset($_POST['gunbroker_custom_title'])) {
            update_post_meta($post_id, '_gunbroker_custom_title', sanitize_text_field($_POST['gunbroker_custom_title']));
        }

        if (isset($_POST['gunbroker_category'])) {
            update_post_meta($post_id, '_gunbroker_category', sanitize_text_field($_POST['gunbroker_category']));
        }

        // New: save per-product listing options
        $bool_keys = array(
            '_gunbroker_returns_accepted' => 'gunbroker_returns_accepted',
            '_gunbroker_will_ship_international' => 'gunbroker_will_ship_international',
            '_gunbroker_use_default_taxes' => 'gunbroker_use_default_taxes'
        );
        foreach ($bool_keys as $meta_key => $post_key) {
            update_post_meta($post_id, $meta_key, isset($_POST[$post_key]) ? '1' : '0');
        }

        $scalar_map = array(
            '_gunbroker_who_pays_shipping' => 'gunbroker_who_pays_shipping',
            '_gunbroker_auto_relist' => 'gunbroker_auto_relist',
            '_gunbroker_country' => 'gunbroker_country',
            '_gunbroker_seller_city' => 'gunbroker_seller_city',
            '_gunbroker_seller_state' => 'gunbroker_seller_state',
            '_gunbroker_seller_postal' => 'gunbroker_seller_postal',
            '_gunbroker_contact_phone' => 'gunbroker_contact_phone',
            '_gunbroker_condition' => 'gunbroker_condition',
            '_gunbroker_inspection_period' => 'gunbroker_inspection_period'
        );
        foreach ($scalar_map as $meta_key => $post_key) {
            if (isset($_POST[$post_key]) && $_POST[$post_key] !== '') {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }

        // Arrays: payment and shipping methods
        $pm = array();
        if (!empty($_POST['gunbroker_payment_methods']) && is_array($_POST['gunbroker_payment_methods'])) {
            foreach ($_POST['gunbroker_payment_methods'] as $m) {
                $pm[] = sanitize_text_field($m);
            }
        }
        if (!empty($pm)) {
            update_post_meta($post_id, '_gunbroker_payment_methods', $pm);
        } else {
            delete_post_meta($post_id, '_gunbroker_payment_methods');
        }

        $sm = array();
        if (!empty($_POST['gunbroker_shipping_methods']) && is_array($_POST['gunbroker_shipping_methods'])) {
            foreach ($_POST['gunbroker_shipping_methods'] as $m) {
                $sm[] = sanitize_text_field($m);
            }
        }
        if (!empty($sm)) {
            update_post_meta($post_id, '_gunbroker_shipping_methods', $sm);
        } else {
            delete_post_meta($post_id, '_gunbroker_shipping_methods');
        }

        // Pricing & duration and other fields
        $simple_map = array(
            '_gunbroker_listing_duration' => 'gunbroker_listing_duration',
            '_gunbroker_listing_type' => 'gunbroker_listing_type',
            '_gunbroker_quantity' => 'gunbroker_quantity',
            '_gunbroker_fixed_price' => 'gunbroker_fixed_price',
            '_gunbroker_inspection_period' => 'gunbroker_inspection_period',
            '_gunbroker_schedule_date' => 'gunbroker_schedule_date',
            '_gunbroker_schedule_time' => 'gunbroker_schedule_time',
            '_gunbroker_shipping_profile_id' => 'gunbroker_shipping_profile_id',
            '_gunbroker_serial_number' => 'gunbroker_serial_number'
        );
        foreach ($simple_map as $meta_key => $post_key) {
            if (isset($_POST[$post_key]) && $_POST[$post_key] !== '') {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }

        // Always queue for sync (all products are now auto-synced)
        if (true) {
            $sync = new GunBroker_Sync();
            $sync->queue_product_sync($post_id);
        }
    }

    /**
     * Redirect to GunBroker listings page after saving/creating a product
     */
    public function redirect_after_product_save($location, $post_id) {
        if (get_post_type($post_id) === 'product') {
            // Only redirect on standard save/update actions in admin
            if (!isset($_POST['action']) || in_array($_POST['action'], array('editpost', 'post'))) {
                $location = admin_url('admin.php?page=gunbroker-integration');
            }
        }
        return $location;
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

        // Check if product is already listed (Active status)
        $current_status = $this->get_product_gunbroker_status($product_id);
        error_log("GunBroker Debug: Product {$product_id} status check - Status: '{$current_status['status']}', GunBroker ID: '{$current_status['gunbroker_id']}'");
        
        // Additional debug: Check raw database values
        global $wpdb;
        $raw_db_result = $wpdb->get_row($wpdb->prepare(
            "SELECT status, gunbroker_id FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
            $product_id
        ));
        if ($raw_db_result) {
            error_log("GunBroker Debug: Raw DB values - Status: '{$raw_db_result->status}', GunBroker ID: '{$raw_db_result->gunbroker_id}'");
        } else {
            error_log("GunBroker Debug: No database record found for product {$product_id}");
        }
        
        if ($current_status['status'] === 'active') {
            wp_send_json_error('This product is already listed on GunBroker and cannot be listed again. Use the Update button to modify the existing listing.');
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
            // Update status to error
            $this->update_product_gunbroker_status($product_id, 'error');
            wp_send_json_error($result->get_error_message());
        } else {
            // Sync was successful - the sync process already saved the listing ID
            // Just update the status to active to ensure persistence
            $this->update_product_gunbroker_status($product_id, 'active');
            error_log("GunBroker Debug: Updated product {$product_id} status to active after successful sync");
            
            // Get the listing ID for the response (this should now work since sync saved it)
            $listing_id = $this->get_gunbroker_listing_id($product_id);
            error_log("GunBroker Debug: Retrieved listing ID {$listing_id} for product {$product_id}");
            
            wp_send_json_success(array(
                'message' => 'Product listed successfully',
                'listing_id' => $listing_id
            ));
        }
    }

    /**
     * End listing via AJAX
     */
    public function end_listing_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $product_id = intval($_POST['product_id']);
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }

        // Get the GunBroker listing ID for this product
        $gunbroker_id = $this->get_gunbroker_listing_id($product_id);
        error_log('GunBroker Debug: End listing request for product ' . $product_id . ', GunBroker ID: ' . ($gunbroker_id ?: 'null'));
        
        // Check if we have a valid GunBroker ID (not null, empty, or 'unknown')
        if (!$gunbroker_id || $gunbroker_id === 'unknown' || $gunbroker_id === '') {
            // If no valid GunBroker ID, just update the status to inactive
            error_log('GunBroker Debug: No valid GunBroker ID found, updating status to inactive only');
            $this->update_product_gunbroker_status($product_id, 'inactive');
            wp_send_json_success('Listing status updated to inactive (no GunBroker listing to end)');
            return;
        }

        // Call the API to end the listing
        $api = new GunBroker_API();
        error_log('GunBroker Debug: Calling end_listing API for GunBroker ID: ' . $gunbroker_id);
        $result = $api->end_listing($gunbroker_id);

        if (is_wp_error($result)) {
            error_log('GunBroker: Failed to end listing ' . $gunbroker_id . ': ' . $result->get_error_message());
            wp_send_json_error('Failed to end listing: ' . $result->get_error_message());
        }

        // Update the status in our database
        $this->update_product_gunbroker_status($product_id, 'inactive');
        
        error_log('GunBroker: Successfully ended listing ' . $gunbroker_id . ' for product ' . $product_id);
        wp_send_json_success('Listing ended successfully');
    }

    /**
     * Get top-level categories via AJAX
     */
    public function get_top_categories_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');
        
        $api = new GunBroker_API();
        $categories = $api->get_top_level_categories();
        
        if (is_wp_error($categories)) {
            wp_send_json_error($categories->get_error_message());
        }
        
        wp_send_json_success($categories);
    }

    /**
     * Get subcategories via AJAX
     */
    public function get_subcategories_ajax() {
        check_ajax_referer('gunbroker_ajax_nonce', 'nonce');
        
        $parent_category_id = intval($_POST['parent_category_id']);
        if (!$parent_category_id) {
            wp_send_json_error('Parent category ID is required');
        }
        
        $api = new GunBroker_API();
        $subcategories = $api->get_subcategories($parent_category_id);
        
        if (is_wp_error($subcategories)) {
            wp_send_json_error($subcategories->get_error_message());
        }
        
        wp_send_json_success($subcategories);
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
        
        // Debug: Log the raw response
        // Raw API response received
        
        // Try multiple possible keys for listings data
        $possible_keys = array('results', 'Items', 'data', 'listings', 'items', 'Results');
        $items_key = null;
        $items_data = null;
        
        foreach ($possible_keys as $key) {
            if (isset($result[$key]) && is_array($result[$key])) {
                $items_key = $key;
                $items_data = $result[$key];
                // Found listings in key: {$key} with " . count($items_data) . " items
                break;
            }
        }

        $is_sandbox = get_option('gunbroker_sandbox_mode', true);
        $website_url = $is_sandbox ? 'https://www.sandbox.gunbroker.com/' : 'https://www.gunbroker.com/';

        if ($items_data && is_array($items_data)) {
            foreach ($items_data as $item) {
                $listings[] = array(
                    'id' => $item['itemID'] ?? $item['ItemID'] ?? $item['id'] ?? 'N/A',
                    'title' => $item['title'] ?? $item['Title'] ?? $item['name'] ?? 'No Title',
                    'price' => $item['buyNowPrice'] ?? $item['BuyNowPrice'] ?? $item['currentPrice'] ?? $item['CurrentPrice'] ?? $item['price'] ?? 0,
                    'end_date' => $item['endDate'] ?? $item['EndDate'] ?? $item['endTime'] ?? '',
                    'category' => $item['categoryName'] ?? $item['CategoryName'] ?? $item['category'] ?? 'Unknown',
                    'condition' => $item['condition'] ?? $item['Condition'] ?? 'Unknown',
                    'image_url' => isset($item['pictureURLs']) ? $item['pictureURLs'][0] ?? '' : (isset($item['PictureURLs']) ? $item['PictureURLs'][0] ?? '' : ($item['image'] ?? '')),
                    'url' => $website_url . 'item/' . ($item['itemID'] ?? $item['ItemID'] ?? $item['id'] ?? '')
                );
            }
        } else {
            // No listings data found in response
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


    /**
     * Test product fetching endpoints
     */

    /**
     * Ensure database tables exist
     */
    public function ensure_tables_exist() {
        global $wpdb;
        
        // Check if listings table exists
        $table_name = $wpdb->prefix . 'gunbroker_listings';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if (!$table_exists) {
            error_log('GunBroker: Database tables missing, creating them now...');
            $this->create_tables();
        } else {
            // Check if we need to update the table structure
            $this->update_table_structure();
        }
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Listings table
        $table_name = $wpdb->prefix . 'gunbroker_listings';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            gunbroker_id varchar(100) NULL DEFAULT NULL,
            status enum('active','inactive','error','pending','not_listed') DEFAULT 'not_listed',
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($log_sql);
        
        // Update database version
        update_option('gunbroker_db_version', '1.0.2');
        
        error_log('GunBroker: Database tables created successfully');
    }

    /**
     * Get products organized by GunBroker status
     * This method efficiently separates products into Active and Not Listed categories
     */
    public function get_products_by_status($limit = 50) {
        global $wpdb;
        
        // Ensure tables exist
        $this->ensure_tables_exist();
        
        // First, let's check if we have any products at all
        $total_products = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'product' AND post_status = 'publish'
        ");
        
        error_log("GunBroker Debug: Total products found: " . $total_products);
        
        if ($total_products == 0) {
            return array(
                'active' => array(),
                'not_listed' => array(),
                'total_active' => 0,
                'total_not_listed' => 0
            );
        }
        
        // Get all products with their GunBroker status in one query
        $products_query = "
            SELECT 
                p.ID as product_id,
                p.post_title as product_name,
                p.post_status,
                pm_sku.meta_value as sku,
                pm_price.meta_value as price,
                pm_stock.meta_value as stock,
                pm_image.meta_value as image_id,
                gb.status as gunbroker_status,
                gb.gunbroker_id,
                gb.last_sync
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
            LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
            LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
            LEFT JOIN {$wpdb->postmeta} pm_image ON p.ID = pm_image.post_id AND pm_image.meta_key = '_thumbnail_id'
            LEFT JOIN {$wpdb->prefix}gunbroker_listings gb ON p.ID = gb.product_id
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish'
            ORDER BY 
                CASE 
                    WHEN gb.status = 'active' THEN 1
                    WHEN gb.status IS NULL OR gb.status = 'not_listed' THEN 2
                    ELSE 3
                END,
                p.post_title
            LIMIT %d
        ";
        
        $products = $wpdb->get_results($wpdb->prepare($products_query, $limit));
        
        error_log("GunBroker Debug: Products from query: " . count($products));
        
        // Separate into active and not listed
        $active_products = array();
        $not_listed_products = array();
        
        foreach ($products as $product_data) {
            // Create a product object for compatibility
            $product = wc_get_product($product_data->product_id);
            if (!$product) {
                error_log("GunBroker Debug: Failed to get product object for ID: " . $product_data->product_id);
                continue;
            }
            
            // Add GunBroker data to the product object
            $product->gunbroker_status = $product_data->gunbroker_status ?: 'not_listed';
            $product->gunbroker_id = $product_data->gunbroker_id;
            $product->gunbroker_last_sync = $product_data->last_sync;
            
            error_log("GunBroker Debug: Product '{$product->get_name()}' - Status: {$product->gunbroker_status}");
            
            // Check if product has a valid GunBroker ID or is marked as active in database
            $gunbroker_id = !empty($product_data->gunbroker_id) ? $product_data->gunbroker_id : null;
            $gunbroker_status = !empty($product_data->gunbroker_status) ? $product_data->gunbroker_status : 'not_listed';
            
            // CRITICAL FIX: If status is 'active' in database, treat as active regardless of GunBroker ID
            // This handles cases where sync was successful but no GunBroker ID was returned
            if ($gunbroker_status === 'active') {
                // Product is marked as active in database - treat as active
                $product->gunbroker_status = 'active';
                $product->gunbroker_id = $gunbroker_id;
                $active_products[] = $product;
            } elseif ($gunbroker_id && $gunbroker_status !== 'error') {
                // Product has a GunBroker ID and is not in error state - treat as active
                $product->gunbroker_status = 'active';
                $product->gunbroker_id = $gunbroker_id;
                $active_products[] = $product;
            } else {
                // Product has no GunBroker ID or is in error state - treat as not listed
                $product->gunbroker_status = 'not_listed';
                $product->gunbroker_id = null;
                $not_listed_products[] = $product;
            }
        }
        
        error_log("GunBroker Debug: Active products: " . count($active_products) . ", Not listed: " . count($not_listed_products));
        
        // Debug: Check what's actually in the database
        $db_check = $wpdb->get_results("SELECT product_id, gunbroker_id, status FROM {$wpdb->prefix}gunbroker_listings LIMIT 10");
        error_log("GunBroker Debug: Database contents: " . print_r($db_check, true));
        
        // If no active products found, try to fix the database manually
        if (count($active_products) == 0) {
            error_log("GunBroker Debug: No active products found, attempting to fix database...");
            $this->fix_database_listings();
            
            // Re-run the query after fix
            $products = $wpdb->get_results($wpdb->prepare($products_query, $limit));
            $active_products = array();
            $not_listed_products = array();
            
            foreach ($products as $product_data) {
                $product = wc_get_product($product_data->product_id);
                if (!$product) continue;
                
                $product->gunbroker_status = $product_data->gunbroker_status ?: 'not_listed';
                $product->gunbroker_id = $product_data->gunbroker_id;
                $product->gunbroker_last_sync = $product_data->last_sync;
                
                if ($product_data->gunbroker_id && $product_data->gunbroker_status !== 'error') {
                    $product->gunbroker_status = 'active';
                    $active_products[] = $product;
                } else {
                    $product->gunbroker_status = 'not_listed';
                    $not_listed_products[] = $product;
                }
            }
            
            error_log("GunBroker Debug: After fix - Active: " . count($active_products) . ", Not listed: " . count($not_listed_products));
        }
        
        return array(
            'active' => $active_products,
            'not_listed' => $not_listed_products,
            'total_active' => count($active_products),
            'total_not_listed' => count($not_listed_products)
        );
    }

    /**
     * Update table structure to fix NULL constraint issues
     */
    private function update_table_structure() {
        global $wpdb;
        
        // Fix gunbroker_id column to allow NULL values
        $table_name = $wpdb->prefix . 'gunbroker_listings';
        $result = $wpdb->query("ALTER TABLE $table_name MODIFY COLUMN gunbroker_id varchar(100) NULL DEFAULT NULL");
        
        if ($result !== false) {
            error_log('GunBroker: Successfully updated gunbroker_id column to allow NULL values');
        } else {
            error_log('GunBroker: Failed to update gunbroker_id column: ' . $wpdb->last_error);
        }
        
        // Fix any "unknown" values in the database
        $fix_result = $wpdb->query("UPDATE $table_name SET gunbroker_id = NULL WHERE gunbroker_id = 'unknown' OR gunbroker_id = ''");
        if ($fix_result !== false) {
            error_log('GunBroker: Fixed ' . $fix_result . ' records with invalid gunbroker_id values');
        }
    }

    /**
     * Update product GunBroker status in database
     */
    public function update_product_gunbroker_status($product_id, $status, $gunbroker_id = null, $sync_data = null) {
        global $wpdb;
        
        $this->ensure_tables_exist();
        
        $data = array(
            'product_id' => $product_id,
            'status' => $status,
            'last_sync' => current_time('mysql'),
            'gunbroker_id' => $gunbroker_id ?: null
        );
        
        if ($sync_data) {
            $data['sync_data'] = is_string($sync_data) ? $sync_data : json_encode($sync_data);
        }
        
        error_log("GunBroker Debug: Updating product {$product_id} status to '{$status}' with GunBroker ID: " . ($gunbroker_id ?: 'null'));
        error_log("GunBroker Debug: Data array: " . print_r($data, true));
        
        $result = $wpdb->replace(
            $wpdb->prefix . 'gunbroker_listings',
            $data,
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        error_log("GunBroker Debug: wpdb->replace result: " . ($result !== false ? 'SUCCESS' : 'FAILED'));
        if ($result === false) {
            error_log("GunBroker Debug: wpdb->replace error: " . $wpdb->last_error);
        }
        
        if ($result !== false) {
            // Log the status change
            $this->log_status_change($product_id, $status, $gunbroker_id);
            
            // Verify the update worked
            $verify = $wpdb->get_row($wpdb->prepare(
                "SELECT status, gunbroker_id FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
                $product_id
            ));
            error_log("GunBroker Debug: Verification after update - Status: '{$verify->status}', GunBroker ID: '{$verify->gunbroker_id}'");
            
            return true;
        }
        
        return false;
    }

    /**
     * Log status changes for debugging
     */
    private function log_status_change($product_id, $status, $gunbroker_id = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("GunBroker Status Update: Product {$product_id} -> {$status}" . ($gunbroker_id ? " (ID: {$gunbroker_id})" : ""));
        }
    }

    /**
     * Get product GunBroker status efficiently
     */
    public function get_product_gunbroker_status($product_id) {
        global $wpdb;
        
        $this->ensure_tables_exist();
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT status, gunbroker_id, last_sync FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
            $product_id
        ));
        
        if ($result) {
            // Check if the product has a valid GunBroker ID and is not in error state
            $gunbroker_id = !empty($result->gunbroker_id) ? $result->gunbroker_id : null;
            $status = !empty($result->status) ? $result->status : 'not_listed';
            
            // CRITICAL FIX: If status is 'active' in database, respect it regardless of GunBroker ID
            // This handles cases where sync was successful but no GunBroker ID was returned
            if ($status === 'active') {
                // Keep as active - this means the sync was successful
                $status = 'active';
            } elseif (!$gunbroker_id || $status === '' || $status === null) {
                // No GunBroker ID and not explicitly active - treat as not_listed
                $status = 'not_listed';
            }
            
            return array(
                'status' => $status,
                'gunbroker_id' => $gunbroker_id,
                'last_sync' => $result->last_sync
            );
        }
        
        return array(
            'status' => 'not_listed',
            'gunbroker_id' => null,
            'last_sync' => null
        );
    }

    /**
     * Fix database listings by updating error status to active for products that should be active
     */
    private function fix_database_listings() {
        global $wpdb;
        
        // Get all products that have GunBroker enabled but are in error status
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_gunbroker_enabled',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        ));

        error_log("GunBroker Debug: Found " . count($products) . " products with GunBroker enabled");

        foreach ($products as $product_post) {
            $product_id = $product_post->ID;
            $product = wc_get_product($product_id);
            if (!$product) continue;

            // Check if this product has a listing record
            $listing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
                $product_id
            ));

            if ($listing) {
                // If it has a GunBroker ID, update status to active
                if ($listing->gunbroker_id) {
                    $this->update_product_gunbroker_status($product_id, 'active', $listing->gunbroker_id);
                    error_log("GunBroker Debug: Fixed product '{$product->get_name()}' - Status: error -> active (ID: {$listing->gunbroker_id})");
                } else {
                    // If no GunBroker ID, set to not_listed
                    $this->update_product_gunbroker_status($product_id, 'not_listed');
                    error_log("GunBroker Debug: Fixed product '{$product->get_name()}' - Status: error -> not_listed (no GunBroker ID)");
                }
            } else {
                // No listing record, create one as not_listed
                $this->update_product_gunbroker_status($product_id, 'not_listed');
                error_log("GunBroker Debug: Created listing record for '{$product->get_name()}' - Status: not_listed");
            }
        }
    }


}
 