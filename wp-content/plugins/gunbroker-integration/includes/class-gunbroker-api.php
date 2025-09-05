<?php

class GunBroker_API {

    private $dev_key;
    private $access_token;
    private $base_url;
    private $is_sandbox;

    public function __construct() {
        $this->dev_key = get_option('gunbroker_dev_key');
        $this->access_token = get_option('gunbroker_access_token');
        $this->is_sandbox = get_option('gunbroker_sandbox_mode', true);

        // Use sandbox for testing, production for live
        $this->base_url = $this->is_sandbox
            ? 'https://api.gunbroker.com/v1/'
            : 'https://api.gunbroker.com/v1/';
    }

    /**
     * Authenticate with GunBroker API
     */
    public function authenticate($username, $password) {
        if (empty($this->dev_key)) {
            return new WP_Error('missing_dev_key', 'GunBroker Developer Key is required');
        }

        $response = wp_remote_post($this->base_url . 'Users/AccessToken', array(
            'headers' => array(
                'X-DevKey' => $this->dev_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'Username' => $username,
                'Password' => $password
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code === 200 && isset($body['accessToken'])) {
            $this->access_token = $body['accessToken'];
            update_option('gunbroker_access_token', $this->access_token);
            return true;
        }

        return new WP_Error('auth_failed', 'Authentication failed: ' . $body['message'] ?? 'Unknown error');
    }

    /**
     * Make API request to GunBroker
     */
    public function make_request($endpoint, $method = 'GET', $data = array()) {
        if (empty($this->dev_key)) {
            return new WP_Error('not_authenticated', 'Developer Key not configured');
        }

        if (empty($this->access_token)) {
            return new WP_Error('not_authenticated', 'Not authenticated with GunBroker API - call authenticate() first');
        }

        $url = $this->base_url . $endpoint;
        $args = array(
            'method' => $method,
            'headers' => array(
                'X-DevKey' => $this->dev_key,
                'X-AccessToken' => $this->access_token,
                'Content-Type' => 'application/json',
                'User-Agent' => 'WordPress-GunBroker-Plugin/1.0'
            ),
            'timeout' => 30,
            'sslverify' => false  // Disable SSL verification for local development
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }

        error_log("GunBroker API Request: {$method} {$url}");
        if (!empty($data)) {
            error_log("GunBroker API Data: " . json_encode($data));
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->log_error('API Request Failed', $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        // Log the response for debugging
        error_log("GunBroker API Response: {$response_code} - " . $body);

        if ($response_code >= 200 && $response_code < 300) {
            return $decoded_body;
        }

        // Handle authentication errors
        if ($response_code === 401) {
            return new WP_Error('auth_failed', 'Authentication failed - access token may be expired');
        }

        $error_message = 'GunBroker API Error (HTTP ' . $response_code . ')';
        if (isset($decoded_body['message'])) {
            $error_message .= ': ' . $decoded_body['message'];
        } elseif (isset($decoded_body['error'])) {
            $error_message .= ': ' . $decoded_body['error'];
        }

        return new WP_Error(
            'api_error',
            $error_message,
            array('status' => $response_code, 'body' => $decoded_body)
        );
    }

    /**
     * Get the base URL (for debugging)
     */
    public function get_base_url() {
        return $this->base_url;
    }

    /**
     * Create a new listing on GunBroker
     */
    public function create_listing($listing_data) {
        return $this->make_request('Items', 'POST', $listing_data);
    }

    /**
     * Update existing listing
     */
    public function update_listing($gunbroker_id, $listing_data) {
        return $this->make_request("Items/{$gunbroker_id}", 'PUT', $listing_data);
    }

    /**
     * End a listing
     */
    public function end_listing($gunbroker_id) {
        return $this->make_request("Items/{$gunbroker_id}", 'DELETE');
    }

    /**
     * Get listing details
     */
    public function get_listing($gunbroker_id) {
        return $this->make_request("Items/{$gunbroker_id}");
    }

    /**
     * Search listings
     */
    public function search_listings($params = array()) {
        $query_string = http_build_query($params);
        return $this->make_request("Items?" . $query_string);
    }

    /**
     * Check if API credentials are valid
     */
    public function test_connection() {
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        if (empty($this->dev_key)) {
            return new WP_Error('no_dev_key', 'Developer Key is missing');
        }

        if (empty($username) || empty($password)) {
            return new WP_Error('no_credentials', 'Username or password is missing');
        }

        // Try to authenticate
        $auth_result = $this->authenticate($username, $password);

        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        return true;
    }

    /**
     * Log API calls for debugging
     */
    private function log_api_call($endpoint, $method, $status_code, $response) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'GunBroker API: %s %s - Status: %d - Response: %s',
                $method,
                $endpoint,
                $status_code,
                json_encode($response)
            ));
        }
    }

    /**
     * Log errors
     */
    private function log_error($title, $message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("GunBroker API Error - {$title}: {$message}");
        }
    }

    /**
     * Prepare listing data from WooCommerce product
     */
    public function prepare_listing_data($product) {
        $markup_percentage = get_option('gunbroker_markup_percentage', 10);
        $base_price = floatval($product->get_regular_price());
        $gunbroker_price = $base_price * (1 + $markup_percentage / 100);

        // Get custom GunBroker title or use product title
        $custom_title = get_post_meta($product->get_id(), '_gunbroker_custom_title', true);
        $title = !empty($custom_title) ? $custom_title : $product->get_name();

        $listing_data = array(
            'Title' => $title,
            'Description' => $product->get_description() ?: $product->get_short_description() ?: 'No description available',
            'CategoryID' => get_post_meta($product->get_id(), '_gunbroker_category', true) ?: 3022, // Default firearms category
            'BuyNowPrice' => number_format($gunbroker_price, 2, '.', ''),
            'Quantity' => max(1, $product->get_stock_quantity() ?: 1),
            'ListingDuration' => get_option('gunbroker_listing_duration', 7), // 7 days default
            'PaymentMethods' => array('Check', 'MoneyOrder', 'CreditCard'),
            'ShippingMethods' => array('StandardShipping'),
            'InspectionPeriod' => 'ThreeDays',
            'ReturnsAccepted' => true,
            'Condition' => 'New', // You might want to make this configurable
        );

        // Add images if available
        $image_ids = $product->get_gallery_image_ids();
        if (empty($image_ids)) {
            // Try featured image if no gallery images
            $featured_image_id = $product->get_image_id();
            if ($featured_image_id) {
                $image_ids = array($featured_image_id);
            }
        }

        if (!empty($image_ids)) {
            $listing_data['Pictures'] = array();
            foreach ($image_ids as $image_id) {
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    $listing_data['Pictures'][] = $image_url;
                }
            }
        }

        // Log the prepared data for debugging
        error_log('GunBroker Listing Data Prepared: ' . json_encode($listing_data));

        return apply_filters('gunbroker_listing_data', $listing_data, $product);
    }
}