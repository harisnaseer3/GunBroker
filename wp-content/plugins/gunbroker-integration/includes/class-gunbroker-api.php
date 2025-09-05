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

        error_log('GunBroker: Starting authentication for user: ' . $username);

        $response = wp_remote_post($this->base_url . 'Users/AccessToken', array(
            'headers' => array(
                'X-DevKey' => $this->dev_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'Username' => $username,
                'Password' => $password
            )),
            'timeout' => 30,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            error_log('GunBroker: Authentication request failed: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        error_log('GunBroker: Auth response code: ' . $response_code);
        error_log('GunBroker: Auth response body: ' . wp_remote_retrieve_body($response));

        if ($response_code === 200 && isset($body['accessToken'])) {
            $this->access_token = $body['accessToken'];
            update_option('gunbroker_access_token', $this->access_token);
            error_log('GunBroker: Authentication successful, token: ' . substr($this->access_token, 0, 10) . '...');
            return true;
        }

        $error_message = isset($body['message']) ? $body['message'] : 'Unknown authentication error';
        error_log('GunBroker: Authentication failed: ' . $error_message);
        return new WP_Error('auth_failed', 'Authentication failed: ' . $error_message);
    }

    /**
     * Ensure we have a valid access token
     */
    private function ensure_authenticated() {
        if (empty($this->access_token)) {
            $username = get_option('gunbroker_username');
            $password = get_option('gunbroker_password');

            if (empty($username) || empty($password)) {
                return new WP_Error('no_credentials', 'GunBroker credentials not configured');
            }

            return $this->authenticate($username, $password);
        }
        return true;
    }

    /**
     * Make API request to GunBroker
     */
    public function make_request($endpoint, $method = 'GET', $data = array()) {
        if (empty($this->dev_key)) {
            return new WP_Error('not_authenticated', 'Developer Key not configured');
        }

        // Ensure we're authenticated
        $auth_check = $this->ensure_authenticated();
        if (is_wp_error($auth_check)) {
            return $auth_check;
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
            'sslverify' => true
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            $args['body'] = json_encode($data);
        }

        error_log("GunBroker API Request: {$method} {$url}");
        if (!empty($data)) {
            error_log("GunBroker API Data: " . json_encode($data, JSON_PRETTY_PRINT));
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
        error_log("GunBroker API Response: {$response_code}");
        error_log("GunBroker API Response Body: " . $body);

        if ($response_code >= 200 && $response_code < 300) {
            return $decoded_body;
        }

        // Handle authentication errors - try to re-authenticate once
        if ($response_code === 401) {
            error_log('GunBroker: Got 401, attempting re-authentication');
            $username = get_option('gunbroker_username');
            $password = get_option('gunbroker_password');

            $reauth_result = $this->authenticate($username, $password);
            if (!is_wp_error($reauth_result)) {
                // Retry the original request with new token
                $args['headers']['X-AccessToken'] = $this->access_token;
                $retry_response = wp_remote_request($url, $args);

                if (!is_wp_error($retry_response)) {
                    $retry_code = wp_remote_retrieve_response_code($retry_response);
                    if ($retry_code >= 200 && $retry_code < 300) {
                        return json_decode(wp_remote_retrieve_body($retry_response), true);
                    }
                }
            }

            return new WP_Error('auth_failed', 'Authentication failed - access token expired and re-authentication failed');
        }

        $error_message = 'GunBroker API Error (HTTP ' . $response_code . ')';
        if (isset($decoded_body['message'])) {
            $error_message .= ': ' . $decoded_body['message'];
        } elseif (isset($decoded_body['error'])) {
            $error_message .= ': ' . $decoded_body['error'];
        } elseif (isset($decoded_body['errorMessage'])) {
            $error_message .= ': ' . $decoded_body['errorMessage'];
        }

        error_log('GunBroker API Error: ' . $error_message);

        return new WP_Error(
            'api_error',
            $error_message,
            array('status' => $response_code, 'body' => $decoded_body)
        );
    }

    /**
     * Create a new listing on GunBroker
     */
    // Add this to your class-gunbroker-api.php file
    public function create_listing($listing_data) {
        // Force fresh authentication before creating listings
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        error_log('GunBroker: Creating listing - forcing fresh authentication');
        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            error_log('GunBroker: Fresh auth failed for listing creation: ' . $auth_result->get_error_message());
            return $auth_result;
        }

        error_log('GunBroker: About to create listing with data: ' . json_encode($listing_data, JSON_PRETTY_PRINT));
        return $this->make_request('Items', 'POST', $listing_data);
    }

    /**
     * Update existing listing
     */
    public function update_listing($gunbroker_id, $listing_data) {
        // Force fresh authentication for updates too
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

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

        // Test with a simple API call to verify permissions
        $test_result = $this->make_request('Users/GetUserInfo');
        if (is_wp_error($test_result)) {
            return $test_result;
        }

        return true;
    }

    /**
     * Log errors
     */
    private function log_error($title, $message) {
        error_log("GunBroker API Error - {$title}: {$message}");
    }

    /**
     * Prepare listing data from WooCommerce product
     */
    public function prepare_listing_data($product) {
        // Get markup percentage - check for product-specific override first
        $product_markup = get_post_meta($product->get_id(), '_gunbroker_custom_markup', true);
        $markup_percentage = !empty($product_markup) ? floatval($product_markup) : get_option('gunbroker_markup_percentage', 10);

        $base_price = floatval($product->get_regular_price());
        if ($base_price <= 0) {
            $base_price = floatval($product->get_price());
        }

        $gunbroker_price = $base_price * (1 + $markup_percentage / 100);

        // Get custom GunBroker title or use product title
        $custom_title = get_post_meta($product->get_id(), '_gunbroker_custom_title', true);
        $title = !empty($custom_title) ? $custom_title : $product->get_name();

        // Prepare description
        $description = $product->get_description();
        if (empty($description)) {
            $description = $product->get_short_description();
        }
        if (empty($description)) {
            $description = 'No description available';
        }

        // Get category - use product-specific or default
        $category_id = get_post_meta($product->get_id(), '_gunbroker_category', true);
        if (empty($category_id)) {
            $category_id = get_option('gunbroker_default_category', 3022); // Default firearms category
        }

        // Prepare minimal listing data
        $listing_data = array(
            'Title' => substr($title, 0, 75), // GunBroker title limit
            'Description' => $description,
            'CategoryID' => intval($category_id),
            'BuyNowPrice' => number_format($gunbroker_price, 2, '.', ''),
            'Quantity' => max(1, intval($product->get_stock_quantity()) ?: 1),
            'ListingDuration' => intval(get_option('gunbroker_listing_duration', 7)),
            'PaymentMethods' => array('Check', 'MoneyOrder', 'CreditCard'),
            'ShippingMethods' => array('StandardShipping'),
            'InspectionPeriod' => 'ThreeDays',
            'ReturnsAccepted' => true,
            'Condition' => 'New'
        );

        // Add images if available (optional - remove if causing issues)
        $image_ids = $product->get_gallery_image_ids();
        if (empty($image_ids)) {
            $featured_image_id = $product->get_image_id();
            if ($featured_image_id) {
                $image_ids = array($featured_image_id);
            }
        }

        if (!empty($image_ids)) {
            $listing_data['Pictures'] = array();
            foreach (array_slice($image_ids, 0, 5) as $image_id) { // Limit to 5 images
                $image_url = wp_get_attachment_url($image_id);
                if ($image_url && filter_var($image_url, FILTER_VALIDATE_URL)) {
                    $listing_data['Pictures'][] = $image_url;
                }
            }
        }

        // Log the prepared data for debugging
        error_log('GunBroker Listing Data Prepared: ' . json_encode($listing_data, JSON_PRETTY_PRINT));

        return apply_filters('gunbroker_listing_data', $listing_data, $product);
    }

    /**
     * Get access token for debugging
     */
    public function get_access_token() {
        return $this->access_token;
    }
}