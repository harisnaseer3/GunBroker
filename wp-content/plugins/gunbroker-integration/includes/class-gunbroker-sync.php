<?php

class GunBroker_Sync {

    public function __construct() {
        // Hook into WooCommerce product events
        add_action('woocommerce_new_product', array($this, 'on_product_created'));
        add_action('woocommerce_update_product', array($this, 'on_product_updated'));
        add_action('woocommerce_product_set_stock', array($this, 'on_stock_changed'));

        // Schedule inventory sync
        add_action('gunbroker_sync_inventory', array($this, 'sync_all_inventory'));

        // Schedule the recurring sync if not already scheduled
        if (!wp_next_scheduled('gunbroker_sync_inventory')) {
            wp_schedule_event(time(), 'hourly', 'gunbroker_sync_inventory');
        }

        // Add manual sync action
        add_action('wp_ajax_gunbroker_manual_sync', array($this, 'handle_manual_sync'));
    }

    /**
     * Handle manual sync via AJAX
     */
    public function handle_manual_sync() {
        check_ajax_referer('gunbroker_manual_sync', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        $product_id = intval($_POST['product_id']);
        $result = $this->sync_single_product($product_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Product synced successfully');
        }
    }

    /**
     * Queue a product for sync
     */
    public function queue_product_sync($product_id) {
        // For now, we'll sync immediately
        // Later we can implement proper background processing
        return $this->sync_single_product($product_id);
    }

    /**
     * Sync a single product with GunBroker
     */
    /**
     * Sync a single product with GunBroker
     */
    /**
     * Sync a single product with GunBroker
     */
    public function sync_single_product($product_id) {
        // Starting sync for product ID: {$product_id}

        $product = wc_get_product($product_id);
        if (!$product) {
            error_log('GunBroker: Product not found: ' . $product_id);
            return new WP_Error('invalid_product', 'Product not found');
        }

        // All products are now auto-synced (checkbox removed)

        // Check if plugin is configured
        $settings = new GunBroker_Settings();
        if (!$settings->is_configured()) {
            error_log('GunBroker: Plugin not configured');
            return new WP_Error('not_configured', 'GunBroker plugin is not configured');
        }

        // CRITICAL FIX: Force fresh authentication for every sync
        $api = new GunBroker_API();
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        // Forcing fresh authentication for sync
        $auth_result = $api->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            error_log('GunBroker: Authentication failed in sync: ' . $auth_result->get_error_message());
            $this->log_sync_result($product_id, 'auth', 'error', $auth_result->get_error_message());
            return $auth_result;
        }

        // Check if we already have a listing for this product
        $listing_id = $this->get_listing_id($product_id);
        // Existing listing ID: {$listing_id ?: 'none'}

        try {
            $listing_data = $api->prepare_listing_data($product);
            
            // Check if prepare_listing_data returned an error
            if (is_wp_error($listing_data)) {
                $error_msg = $listing_data->get_error_message();
                error_log('GunBroker: prepare_listing_data failed: ' . $error_msg);
                $this->log_sync_result($product_id, 'prepare', 'error', $error_msg);
                return $listing_data;
            }

            // Validate required fields (dynamic based on listing type)
            $required_fields = array('Title', 'Description', 'CategoryID', 'Condition', 'CountryCode');
            if (isset($listing_data['IsFixedPrice']) && $listing_data['IsFixedPrice'] === true) {
                $required_fields[] = 'FixedPrice';
            } else {
                $required_fields[] = 'StartingBid';
            }

            $missing_fields = array();
            foreach ($required_fields as $field) {
                if (!isset($listing_data[$field]) || $listing_data[$field] === '' || $listing_data[$field] === null) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                $error_msg = 'Missing required fields: ' . implode(', ', $missing_fields);
                error_log('GunBroker: ' . $error_msg);
                $this->log_sync_result($product_id, 'validate', 'error', $error_msg);
                return new WP_Error('invalid_data', $error_msg);
            }

            if ($listing_id) {
                // Update existing listing
                // Updating existing listing: {$listing_id}
                $result = $api->update_listing($listing_id, $listing_data);
                $action = 'update';
            } else {
                // Create new listing
                // Before creating, try to detect if a listing already exists on GunBroker by SKU
                $sku = $product->get_sku();
                if ($sku) {
                    $maybe_existing_id = $api->find_existing_listing_by_sku($sku);
                    if ($maybe_existing_id) {
                        // Link to existing instead of creating duplicate
                        $this->save_listing_id($product_id, $maybe_existing_id);
                        $result = $api->update_listing($maybe_existing_id, $listing_data);
                        $action = 'update';
                    } else {
                        $result = $api->create_listing($listing_data);
                        $action = 'create';
                    }
                } else {
                    $result = $api->create_listing($listing_data);
                    $action = 'create';
                }
            }

            if (is_wp_error($result)) {
                $error_msg = $result->get_error_message();
                error_log('GunBroker: API call failed: ' . $error_msg);
                $this->log_sync_result($product_id, $action, 'error', $error_msg);
                return $result;
            }

            // API call successful
            error_log('GunBroker: API Response for product ' . $product_id . ': ' . print_r($result, true));

            // Save the listing ID if it's a new listing
            $listing_id = null;
            if ($action === 'create') {
                // Check multiple possible field names for the listing ID
                $possible_fields = ['ItemID', 'itemId', 'id', 'listingId', 'listing_id', 'ItemId'];
                foreach ($possible_fields as $field) {
                    if (isset($result[$field]) && !empty($result[$field])) {
                        $listing_id = $result[$field];
                        break;
                    }
                }
                
                if ($listing_id) {
                    $this->save_listing_id($product_id, $listing_id);
                    error_log('GunBroker: Saved new listing ID: ' . $listing_id . ' for product: ' . $product_id);
                } else {
                    error_log('GunBroker: No listing ID found in response - Action: ' . $action);
                    error_log('GunBroker: Available keys in result: ' . implode(', ', array_keys($result)));
                }
            }

            $this->log_sync_result($product_id, $action, 'success', 'Product synced successfully');
            // Sync completed successfully for product: {$product_id}
            return true;

        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            error_log('GunBroker: Exception during sync: ' . $error_msg);
            $this->log_sync_result($product_id, 'sync', 'error', $error_msg);
            return new WP_Error('sync_failed', $error_msg);
        }
    }

    /**
     * Handle new product creation
     */
    public function on_product_created($product_id) {
        error_log('GunBroker: New product created, queuing sync: ' . $product_id);
        $this->queue_product_sync($product_id);
    }

    /**
     * Handle product updates
     */
    public function on_product_updated($product_id) {
        error_log('GunBroker: Product updated, queuing sync: ' . $product_id);
        $this->queue_product_sync($product_id);
    }

    /**
     * Handle stock changes - key feature for client
     */
    public function on_stock_changed($product) {
        $auto_end = get_option('gunbroker_auto_end_zero_stock', true);
        $stock_qty = $product->get_stock_quantity();

        error_log('GunBroker: Stock changed for product ' . $product->get_id() . ' to: ' . $stock_qty);

        // If stock is 0 and auto-end is enabled, end the listing
        if ($stock_qty <= 0 && $auto_end) {
            error_log('GunBroker: Stock is 0, ending listing for product: ' . $product->get_id());
            $this->end_listing_if_out_of_stock($product->get_id());
        } else {
            // Update inventory on GunBroker
            error_log('GunBroker: Updating inventory for product: ' . $product->get_id());
            $this->queue_product_sync($product->get_id());
        }
    }

    /**
     * Sync all inventory
     */
    public function sync_all_inventory() {
        error_log('GunBroker: Starting bulk inventory sync');

        // Get all published products (all are now auto-synced)
        $products = get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        error_log('GunBroker: Found ' . count($products) . ' products to sync');

        $success_count = 0;
        $error_count = 0;

        foreach ($products as $product_post) {
            $result = $this->queue_product_sync($product_post->ID);
            if (is_wp_error($result)) {
                $error_count++;
                error_log('GunBroker: Bulk sync error for product ' . $product_post->ID . ': ' . $result->get_error_message());
            } else {
                $success_count++;
            }

            // Add small delay to prevent rate limiting
            sleep(1);
        }

        error_log("GunBroker: Bulk sync completed. Success: {$success_count}, Errors: {$error_count}");
    }

    /**
     * End listing if product is out of stock
     */
    public function end_listing_if_out_of_stock($product_id) {
        $listing_id = $this->get_listing_id($product_id);
        if (!$listing_id) {
            error_log('GunBroker: No listing ID found to end for product: ' . $product_id);
            return;
        }

        error_log('GunBroker: Ending listing ' . $listing_id . ' for out of stock product: ' . $product_id);

        $api = new GunBroker_API();
        $result = $api->end_listing($listing_id);

        if (!is_wp_error($result)) {
            $this->update_listing_status($product_id, 'inactive');
            $this->log_sync_result($product_id, 'end', 'success', 'Listing ended due to no stock');
            error_log('GunBroker: Successfully ended listing: ' . $listing_id);
        } else {
            $this->log_sync_result($product_id, 'end', 'error', $result->get_error_message());
            error_log('GunBroker: Failed to end listing ' . $listing_id . ': ' . $result->get_error_message());
        }
    }

    /**
     * Get listing ID for a product
     */
    private function get_listing_id($product_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT gunbroker_id FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
            $product_id
        ));
    }

    /**
     * Save listing ID for a product
     */
    private function save_listing_id($product_id, $gunbroker_id) {
        global $wpdb;

        $data = array(
            'product_id' => $product_id,
            'gunbroker_id' => $gunbroker_id,
            'status' => 'active',
            'last_sync' => current_time('mysql'),
            'sync_data' => json_encode(array('last_action' => 'create'))
        );
        
        error_log('GunBroker Debug: save_listing_id - Product: ' . $product_id . ', GunBroker ID: ' . $gunbroker_id);
        error_log('GunBroker Debug: save_listing_id - Data: ' . print_r($data, true));
        
        $result = $wpdb->replace(
            $wpdb->prefix . 'gunbroker_listings',
            $data,
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('GunBroker: Failed to save listing ID to database: ' . $wpdb->last_error);
        } else {
            error_log('GunBroker: Successfully saved listing ID ' . $gunbroker_id . ' for product ' . $product_id . ' (Result: ' . $result . ')');
            
            // Verify the save worked
            $verify = $wpdb->get_row($wpdb->prepare(
                "SELECT status, gunbroker_id FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
                $product_id
            ));
            error_log('GunBroker Debug: save_listing_id verification - Status: ' . $verify->status . ', GunBroker ID: ' . $verify->gunbroker_id);
        }
    }

    /**
     * Update listing status
     */
    private function update_listing_status($product_id, $status) {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'gunbroker_listings',
            array(
                'status' => $status,
                'last_sync' => current_time('mysql')
            ),
            array('product_id' => $product_id),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * Log sync results
     */
    private function log_sync_result($product_id, $action, $status, $message) {
        global $wpdb;

        $listing_id = $this->get_listing_id($product_id);

        $wpdb->insert(
            $wpdb->prefix . 'gunbroker_sync_log',
            array(
                'listing_id' => $listing_id,
                'action' => $action,
                'status' => $status,
                'message' => $message,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        // Also update the main listing record
        if ($listing_id) {
            $this->update_listing_status($product_id, $status === 'success' ? 'active' : 'error');
        }
    }

    /**
     * Get sync status for a product
     */
    public function get_product_sync_status($product_id) {
        global $wpdb;

        $listing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
            $product_id
        ));

        if (!$listing) {
            return array(
                'status' => 'not_synced',
                'listing_id' => null,
                'last_sync' => null
            );
        }

        return array(
            'status' => $listing->status,
            'listing_id' => $listing->gunbroker_id,
            'last_sync' => $listing->last_sync
        );
    }

    /**
     * Get recent sync logs
     */
    public function get_recent_sync_logs($limit = 50) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gunbroker_sync_log 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $limit
        ));
    }
}