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

        // FIXED: Use correct URLs for sandbox vs production
        $this->base_url = $this->is_sandbox
            ? 'https://api.sandbox.gunbroker.com/v1/'
            : 'https://api.gunbroker.com/v1/';
    }

    public function get_website_url() {
        return $this->is_sandbox
            ? 'https://www.sandbox.gunbroker.com/'
            : 'https://www.gunbroker.com/';
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
            return $body; // Return full response instead of just true
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
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
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
        return $this->make_request("Items", 'DELETE', array('ItemID' => $gunbroker_id));
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

        // Test with a simple API call - use working endpoint
        $test_result = $this->make_request('Users/AccountInfo');
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

    /**
     * FIXED: Get user's listings from GunBroker using working endpoint
     */
    public function get_user_listings($params = array()) {
        // Force fresh authentication
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        // Build query parameters for ItemsSelling
        $default_params = array(
            'PageSize' => 25,
            'PageIndex' => 1
        );
        $params = array_merge($default_params, $params);
        $query_string = http_build_query($params);

        $endpoint = 'ItemsSelling' . ($query_string ? '?' . $query_string : '');

        error_log('GunBroker: Getting user listings via: ' . $endpoint);
        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            error_log('GunBroker: Successfully got user listings');
            return $result;
        }

        error_log('GunBroker: ItemsSelling failed: ' . $result->get_error_message());
        return $result;
    }

    /**
     * FIXED: Search GunBroker items using working endpoint
     */
    // 2. Updated search_gunbroker_items method - uses Items endpoint with filters
    public function search_gunbroker_items($search_params = array()) {
        // Force fresh authentication
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        // Build query parameters based on API documentation
        $default_params = array(
            'PageSize' => 25,
            'PageIndex' => 1,
            'Sort' => 1, // Sort by ending soonest
            'View' => 1  // Standard view
        );

        $params = array_merge($default_params, $search_params);

        // Map common search parameters to API format
        if (isset($search_params['Keywords'])) {
            $params['Keywords'] = $search_params['Keywords'];
        }
        if (isset($search_params['CategoryID'])) {
            $params['CategoryID'] = $search_params['CategoryID'];
        }
        if (isset($search_params['Condition'])) {
            $params['Condition'] = $search_params['Condition'];
        }
        if (isset($search_params['BuyNowOnly'])) {
            $params['BuyNowOnly'] = $search_params['BuyNowOnly'];
        }

        $query_string = http_build_query($params);
        $endpoint = 'Items?' . $query_string;

        error_log('GunBroker: Searching items via: ' . $endpoint);
        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            error_log('GunBroker: Successfully searched items');
            return $result;
        }

        error_log('GunBroker: Items search failed: ' . $result->get_error_message());
        return $result;
    }


    /**
     * NEW: Get sold orders using working endpoint
     */
    public function get_sold_orders($params = array()) {
        // Force fresh authentication
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        // Build query parameters
        $default_params = array(
            'PageSize' => 50,
            'PageIndex' => 1
        );
        $params = array_merge($default_params, $params);
        $query_string = http_build_query($params);

        // Use OrdersSold endpoint for actual order data (not ItemsSold)
        $endpoint = 'OrdersSold' . ($query_string ? '?' . $query_string : '');

        error_log('GunBroker: Getting sold orders via: ' . $endpoint);
        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            error_log('GunBroker: Successfully got sold orders');
            return $result;
        }

        error_log('GunBroker: OrdersSold failed: ' . $result->get_error_message());
        return $result;
    }

    /**
     * NEW: Get categories with caching
     */
    public function get_categories_cached() {
        $cached = get_transient('gunbroker_categories');

        if ($cached !== false) {
            return $cached;
        }

        $result = $this->make_request('Categories');

        if (!is_wp_error($result)) {
            set_transient('gunbroker_categories', $result, HOUR_IN_SECONDS * 24); // Cache for 24 hours
        }

        return $result;
    }

    /**
     * NEW: Get specific item details
     */
    public function get_item_details($item_id) {
        $auth_result = $this->ensure_authenticated();
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        $endpoint = "Items/{$item_id}";

        error_log('GunBroker: Getting item details for ID: ' . $item_id);
        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            error_log('GunBroker: Successfully got item details');
            return $result;
        }

        error_log('GunBroker: Get item details failed: ' . $result->get_error_message());
        return $result;
    }
}

// FIXED: Endpoint Discovery Class with correct sandbox URL
class GunBroker_Endpoint_Discovery {

    private $api_url;
    private $dev_key;
    private $token;

    public function __construct($dev_key, $token) {
        $this->dev_key = $dev_key;
        $this->token = $token;

        // Use correct URL based on sandbox mode
        $is_sandbox = get_option('gunbroker_sandbox_mode', true);
        $this->api_url = $is_sandbox
            ? 'https://api.sandbox.gunbroker.com/v1/'
            : 'https://api.gunbroker.com/v1/';
    }

    /**
     * Test different endpoint patterns based on GunBroker API documentation
     */
    public function discover_working_endpoints() {
        $endpoints_to_test = array(
            // Public listing endpoints (no auth needed)
            'Items/Featured' => 'GET',
            'Items/Browse' => 'GET',
            'Items/Search' => 'GET',
            'Items/Search?Keywords=glock&PageSize=5' => 'GET',
            'Items/Browse?CategoryID=851&PageSize=5' => 'GET',

            // Try without authentication first
            'Items?PageSize=5' => 'GET',
            'Items/Active?PageSize=5' => 'GET',
            'Items/Recent?PageSize=5' => 'GET',

            // Different category approaches
            'Categories/851/Items?PageSize=5' => 'GET',
            'Categories/Browse?CategoryID=851&PageSize=5' => 'GET',

            // User-specific endpoints (with auth)
            'Users/Self' => 'GET',
            'Users/Me' => 'GET',
            'User/Profile' => 'GET',
            'User/Items' => 'GET',
            'User/Selling' => 'GET',

            // Alternative formats
            'ItemsSearch?Keywords=rifle&PageSize=5' => 'GET',
            'Browse?CategoryID=851&PageSize=5' => 'GET',
            'Search?Keywords=pistol&PageSize=5' => 'GET',
        );

        $results = array();

        foreach ($endpoints_to_test as $endpoint => $method) {
            // Test without authentication first
            $result_no_auth = $this->test_endpoint($endpoint, $method, false);

            // Test with authentication
            $result_with_auth = $this->test_endpoint($endpoint, $method, true);

            $results[$endpoint] = array(
                'no_auth' => $result_no_auth,
                'with_auth' => $result_with_auth
            );

            // Add delay to avoid rate limiting
            sleep(1);
        }

        return $results;
    }

    /**
     * Test individual endpoint
     */
    public function test_endpoint($endpoint, $method = 'GET', $use_auth = true) {
        $url = $this->api_url . $endpoint;

        $headers = array(
            'X-DevKey: ' . $this->dev_key,
            'Content-Type: application/json'
        );

        if ($use_auth && $this->token) {
            $headers[] = 'X-AccessToken: ' . $this->token;
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return array(
            'success' => ($http_code >= 200 && $http_code < 300),
            'http_code' => $http_code,
            'error' => $error,
            'data_preview' => $response ? substr($response, 0, 500) . '...' : null
        );
    }

    // 6. Test all product endpoints
    public function test_all_product_endpoints() {
        $endpoints_to_test = array(
            'Items?PageSize=5' => 'Search items with limit',
            'ItemsSelling?PageSize=5' => 'User active listings',
            'ItemsSold?PageSize=5' => 'User sold items',
            'ItemsEnded?PageSize=5' => 'User ended listings',
            'Categories' => 'Categories list'
        );

        $results = array();

        foreach ($endpoints_to_test as $endpoint => $description) {
            error_log('GunBroker: Testing endpoint: ' . $endpoint);

            $result = $this->make_request($endpoint);

            $results[$endpoint] = array(
                'description' => $description,
                'success' => !is_wp_error($result),
                'error' => is_wp_error($result) ? $result->get_error_message() : null,
                'has_results' => !is_wp_error($result) && isset($result['results']) && is_array($result['results']),
                'result_count' => !is_wp_error($result) && isset($result['results']) ? count($result['results']) : 0,
                'data_preview' => is_wp_error($result) ? null : substr(json_encode($result), 0, 200) . '...'
            );

            // Small delay to avoid rate limiting
            sleep(1);
        }

        return $results;
    }

    // 3. Get sold items (for order management)
    public function get_sold_items($params = array()) {
        // Force fresh authentication
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        // Build query parameters
        $default_params = array(
            'PageSize' => 50,
            'PageIndex' => 1
        );
        $params = array_merge($default_params, $params);
        $query_string = http_build_query($params);

        $endpoint = 'ItemsSold' . ($query_string ? '?' . $query_string : '');

        error_log('GunBroker: Getting sold items via: ' . $endpoint);
        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            error_log('GunBroker: Successfully got sold items');
            return $result;
        }

        error_log('GunBroker: ItemsSold failed: ' . $result->get_error_message());
        return $result;
    }

    // 4. Get ended listings
    public function get_ended_listings($params = array()) {
        // Force fresh authentication
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');

        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return $auth_result;
        }

        // Build query parameters
        $default_params = array(
            'PageSize' => 25,
            'PageIndex' => 1
        );
        $params = array_merge($default_params, $params);
        $query_string = http_build_query($params);

        $endpoint = 'ItemsEnded' . ($query_string ? '?' . $query_string : '');

        error_log('GunBroker: Getting ended listings via: ' . $endpoint);
        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            error_log('GunBroker: Successfully got ended listings');
            return $result;
        }

        error_log('GunBroker: ItemsEnded failed: ' . $result->get_error_message());
        return $result;
    }
}