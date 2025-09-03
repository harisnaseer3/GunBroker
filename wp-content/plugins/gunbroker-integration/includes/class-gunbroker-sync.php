<?php

class GunBroker_Sync {

    public function __construct() {
        // Hook into WooCommerce product events
        add_action('woocommerce_update_product', array($this, 'on_product_updated'));
        add_action('woocommerce_product_set_stock', array($this, 'on_stock_changed'));

        // Schedule inventory sync
        add_action('gunbroker_sync_inventory', array($this, 'sync_all_inventory'));

        // Schedule the recurring sync if not already scheduled
        if (!wp_next_scheduled('gunbroker_sync_inventory')) {
            wp_schedule_event(time(), 'hourly', 'gunbroker_sync_inventory');
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
    public function sync_single_product($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Product not found');
        }

        // Check if GunBroker sync is enabled for this product
        $enabled = get_post_meta($product_id, '_gunbroker_enabled', true);
        if ($enabled !== 'yes') {
            return new WP_Error('sync_disabled', 'GunBroker sync not enabled for this product');
        }

        $api = new GunBroker_API();

        // Check if we already have a listing for this product
        $listing_id = $this->get_listing_id($product_id);

        try {
            $listing_data = $api->prepare_listing_data($product);

            if ($listing_id) {
                // Update existing listing
                $result = $api->update_listing($listing_id, $listing_data);
                $action = 'update';
            } else {
                // Create new listing
                $result = $api->create_listing($listing_data);
                $action = 'create';
            }

            if (is_wp_error($result)) {
                $this->log_sync_result($product_id, $action, 'error', $result->get_error_message());
                return $result;
            }

            // Save the listing ID if it's a new listing
            if ($action === 'create' && isset($result['ItemID'])) {
                $this->save_listing_id($product_id, $result['ItemID']);
            }

            $this->log_sync_result($product_id, $action, 'success', 'Product synced successfully');
            return true;

        } catch (Exception $e) {
            $this->log_sync_result($product_id, 'sync', 'error', $e->getMessage());
            return new WP_Error('sync_failed', $e->getMessage());
        }
    }

    /**
     * Handle product updates
     */
    public function on_product_updated($product_id) {
        $enabled = get_post_meta($product_id, '_gunbroker_enabled', true);
        if ($enabled === 'yes') {
            $this->queue_product_sync($product_id);
        }
    }

    /**
     * Handle stock changes - key feature for client
     */
    public function on_stock_changed($product) {
        $enabled = get_post_meta($product->get_id(), '_gunbroker_enabled', true);
        if ($enabled === 'yes') {
            $auto_end = get_option('gunbroker_auto_end_zero_stock', true);

            // If stock is 0 and auto-end is enabled, end the listing
            if ($product->get_stock_quantity() <= 0 && $auto_end) {
                $this->end_listing_if_out_of_stock($product->get_id());
            } else {
                // Update inventory on GunBroker
                $this->queue_product_sync($product->get_id());
            }
        }
    }

    /**
     * Sync all inventory
     */
    public function sync_all_inventory() {
        // Get all products with GunBroker sync enabled
        $products = get_posts(array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_key' => '_gunbroker_enabled',
            'meta_value' => 'yes',
            'post_status' => 'publish'
        ));

        foreach ($products as $product_post) {
            $this->queue_product_sync($product_post->ID);
        }
    }

    /**
     * End listing if product is out of stock
     */
    public function end_listing_if_out_of_stock($product_id) {
        $listing_id = $this->get_listing_id($product_id);
        if (!$listing_id) {
            return;
        }

        $api = new GunBroker_API();
        $result = $api->end_listing($listing_id);

        if (!is_wp_error($result)) {
            $this->update_listing_status($product_id, 'inactive');
            $this->log_sync_result($product_id, 'end', 'success', 'Listing ended due to no stock');
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

        $wpdb->replace(
            $wpdb->prefix . 'gunbroker_listings',
            array(
                'product_id' => $product_id,
                'gunbroker_id' => $gunbroker_id,
                'status' => 'active',
                'last_sync' => current_time('mysql'),
                'sync_data' => json_encode(array('last_action' => 'create'))
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
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
}