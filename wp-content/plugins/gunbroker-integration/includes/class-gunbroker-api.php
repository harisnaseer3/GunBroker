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
        if (empty($this->dev_key) || empty($this->access_token)) {
            return new WP_Error('not_authenticated', 'Not authenticated with GunBroker API');
        }

        $url = $this->base_url . $endpoint;
        $args = array(
            'method' => $method,
            'headers' => array(
                'X-DevKey' => $this->dev_key,
                'X-AccessToken' => $this->access_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
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
        $this->log_api_call($endpoint, $method, $response_code, $decoded_body);

        if ($response_code >= 200 && $response_code < 300) {
            return $decoded_body;
        }

        return new WP_Error(
            'api_error',
            'GunBroker API Error: ' . ($decoded_body['message'] ?? 'Unknown error'),
            array('status' => $response_code, 'body' => $decoded_body)
        );
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
        if (empty($this->dev_key) || empty($this->access_token)) {
            return false;
        }

        $response = $this->make_request('Users/Profile');
        return !is_wp_error($response);
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
            'Description' => $product->get_description() ?: $product->get_short_description(),
            'CategoryID' => get_post_meta($product->get_id(), '_gunbroker_category', true) ?: 3022, // Default firearms category
            'BuyNowPrice' => number_format($gunbroker_price, 2, '.', ''),
            'Quantity' => $product->get_stock_quantity() ?: 1,
            'ListingDuration' => get_option('gunbroker_listing_duration', 7), // 7 days default
            'PaymentMethods' => array('Check', 'MoneyOrder', 'CreditCard'),
            'ShippingMethods' => array('StandardShipping'),
            'InspectionPeriod' => 'ThreeDays',
            'ReturnsAccepted' => true,
            'Condition' => 'New', // You might want to make this configurable
        );

        // Add images if available
        $image_ids = $product->get_gallery_image_ids();
        if (!empty($image_ids)) {
            $listing_data['Pictures'] = array();
            foreach ($image_ids as $image_id) {
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url) {
                    $listing_data['Pictures'][] = $image_url;
                }
            }
        }

        return apply_filters('gunbroker_listing_data', $listing_data, $product);
    }
}