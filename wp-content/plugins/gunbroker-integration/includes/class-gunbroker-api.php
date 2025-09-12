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
     * Test network connectivity to GunBroker API
     */
    private function test_connectivity() {
        $test_url = $this->base_url . 'GunBrokerTime';
        
        $response = wp_remote_get($test_url, array(
            'timeout' => 10,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            error_log('GunBroker: Connectivity test failed: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    /**
     * Authenticate with GunBroker API
     */
    public function authenticate($username, $password) {
        if (empty($this->dev_key)) {
            return new WP_Error('missing_dev_key', 'GunBroker Developer Key is required');
        }

        error_log('GunBroker: Starting authentication for user: ' . $username);

        // Try authentication with retry logic
        $max_retries = 3;
        $retry_delay = 2; // seconds
        
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            error_log("GunBroker: Authentication attempt $attempt of $max_retries");
            
            $response = wp_remote_post($this->base_url . 'Users/AccessToken', array(
                'headers' => array(
                    'X-DevKey' => $this->dev_key,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'WordPress-GunBroker-Integration/1.0.1'
                ),
                'body' => json_encode(array(
                    'Username' => $username,
                    'Password' => $password
                )),
                'timeout' => 60, // Increased from 30 to 60 seconds
                'sslverify' => true,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking' => true,
                'cookies' => array()
            ));

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log("GunBroker: Authentication attempt $attempt failed: $error_message");
                
                // If it's a timeout error and we have retries left, wait and try again
                if (strpos($error_message, 'timeout') !== false && $attempt < $max_retries) {
                    error_log("GunBroker: Timeout error, waiting $retry_delay seconds before retry...");
                    sleep($retry_delay);
                    $retry_delay *= 2; // Exponential backoff
                    continue;
                }
                
                return $response;
            } else {
                // Success, break out of retry loop
                break;
            }
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
    public function ensure_authenticated() {
        if (empty($this->access_token)) {
            $username = get_option('gunbroker_username');
            $password = get_option('gunbroker_password');

            if (empty($username) || empty($password)) {
                return new WP_Error('no_credentials', 'GunBroker credentials not configured');
            }

            return $this->authenticate($username, $password);
        }
        
        // Skip token validation for now to avoid connectivity issues
        // Just return true if we have a token
        
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
        
        // Prepare headers - use multipart/form-data for Items POST, json for others
        $is_items_post = ($endpoint === 'Items' && $method === 'POST');
        
        if ($is_items_post) {
            // For Items POST, we'll use multipart/form-data
            $headers = array(
                'X-DevKey' => $this->dev_key,
                'X-AccessToken' => $this->access_token,
                'User-Agent' => 'WordPress-GunBroker-Integration/1.0.1'
                // Don't set Content-Type - let WordPress handle multipart boundary
            );
        } else {
            // For other requests, use JSON
            $headers = array(
                'X-DevKey' => $this->dev_key,
                'X-AccessToken' => $this->access_token,
                'Content-Type' => 'application/json',
                'User-Agent' => 'WordPress-GunBroker-Integration/1.0.1'
            );
        }
        
        // Prepare body for POST/PUT requests
        $body = '';
        if (!empty($data) && in_array($method, array('POST', 'PUT'))) {
            if ($is_items_post) {
                // For Items POST, use multipart/form-data format
                $json_data = json_encode($data);
                if ($json_data === false) {
                    error_log('GunBroker: ERROR - JSON encoding failed: ' . json_last_error_msg());
                    return new WP_Error('json_encode_failed', 'Failed to encode data as JSON: ' . json_last_error_msg());
                }
                
                // Create multipart form data
                $boundary = wp_generate_password(16, false);
                $body = $this->create_multipart_body($data, $boundary);
                $headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
                
                error_log("GunBroker: Multipart data length: " . strlen($body) . " characters");
            } else {
                // For other requests, use JSON
                $json_data = json_encode($data);
                if ($json_data === false) {
                    error_log('GunBroker: ERROR - JSON encoding failed: ' . json_last_error_msg());
                    return new WP_Error('json_encode_failed', 'Failed to encode data as JSON: ' . json_last_error_msg());
                }
                $body = $json_data;
                error_log("GunBroker: JSON data length: " . strlen($json_data) . " characters");
            }
        }
        
        // Use consistent argument structure for all methods
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60,
            'sslverify' => true,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'cookies' => array()
        );

        error_log("GunBroker API Request: {$method} {$url}");
        if (!empty($data)) {
            error_log("GunBroker API Data: " . json_encode($data, JSON_PRETTY_PRINT));
        }

        // Log the complete request details
        error_log("GunBroker: Complete request args: " . json_encode($args, JSON_PRETTY_PRINT));
        error_log("GunBroker: Request URL: " . $url);
        error_log("GunBroker: Request method: " . $method);
        error_log("GunBroker: Request body: " . $body);
        error_log("GunBroker: Request body length: " . strlen($body));
        error_log("GunBroker: Request body is empty: " . (empty($body) ? 'YES' : 'NO'));
        error_log("GunBroker: Dev Key: " . substr($this->dev_key, 0, 10) . "...");
        error_log("GunBroker: Access Token: " . substr($this->access_token, 0, 10) . "...");
        
        // Additional validation for POST requests
        if ($method === 'POST' && empty($body)) {
            error_log("GunBroker: ERROR - POST request with empty body!");
            return new WP_Error('empty_post_body', 'POST request cannot have empty body');
        }
        
        // Use wp_remote_request for all methods with consistent arguments
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('GunBroker: wp_remote_request failed: ' . $response->get_error_message());
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

        // Handle 400 Bad Request errors with detailed logging
        if ($response_code === 400) {
            error_log('GunBroker API 400 Error Details:');
            error_log('Request URL: ' . $url);
            error_log('Request Method: ' . $method);
            error_log('Request Headers: ' . json_encode($args['headers'], JSON_PRETTY_PRINT));
            if (isset($args['body'])) {
                error_log('Request Body: ' . $args['body']);
            }
            error_log('Response Code: ' . $response_code);
            error_log('Response Body: ' . $body);
            
            $error_message = 'GunBroker API Error (HTTP 400 - Bad Request)';
            if (isset($decoded_body['message'])) {
                $error_message .= ': ' . $decoded_body['message'];
            } elseif (isset($decoded_body['error'])) {
                $error_message .= ': ' . $decoded_body['error'];
            } elseif (isset($decoded_body['errorMessage'])) {
                $error_message .= ': ' . $decoded_body['errorMessage'];
            } else {
                $error_message .= ': ' . $body;
            }
            
            return new WP_Error('api_400_error', $error_message, array('status' => 400, 'body' => $decoded_body));
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
        
        // Validate that we have data to send
        if (empty($listing_data)) {
            error_log('GunBroker: ERROR - Empty listing data provided to create_listing');
            return new WP_Error('empty_data', 'No listing data provided');
        }
        
        // Check for required fields
        $required_fields = array('Title', 'Description', 'CategoryID', 'StartingBid', 'Condition', 'CountryCode');
        $missing_fields = array();
        foreach ($required_fields as $field) {
            if (!isset($listing_data[$field]) || empty($listing_data[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log('GunBroker: ERROR - Missing required fields: ' . implode(', ', $missing_fields));
            return new WP_Error('missing_fields', 'Missing required fields: ' . implode(', ', $missing_fields));
        }
        
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
     * Create multipart form data body for GunBroker Items POST
     */
    public function create_multipart_body($data, $boundary) {
        $body = '';
        
        // Add the JSON data as a form field named 'data' (exactly as GunBroker expects)
        $json_data = json_encode($data);
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"data\"\r\n\r\n";
        $body .= $json_data . "\r\n";
        
        // Add any images if they exist in the data
        if (isset($data['Pictures']) && is_array($data['Pictures'])) {
            foreach ($data['Pictures'] as $index => $image_url) {
                if (filter_var($image_url, FILTER_VALIDATE_URL)) {
                    // For now, we'll just add the URL as a text field
                    // In a full implementation, you'd download and include the actual image data
                    $body .= "--{$boundary}\r\n";
                    $body .= "Content-Disposition: form-data; name=\"picture\"\r\n\r\n";
                    $body .= $image_url . "\r\n";
                }
            }
        }
        
        $body .= "--{$boundary}--\r\n";
        
        return $body;
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
    public function prepare_listing_data($product, $category_id = null) {
        error_log('GunBroker: prepare_listing_data called for product ID: ' . $product->get_id());
        
        // Get markup percentage - check for product-specific override first
        $product_markup = get_post_meta($product->get_id(), '_gunbroker_custom_markup', true);
        $markup_percentage = !empty($product_markup) ? floatval($product_markup) : get_option('gunbroker_markup_percentage', 10);
        
        error_log('GunBroker: Markup percentage: ' . $markup_percentage);

        $base_price = floatval($product->get_regular_price());
        error_log('GunBroker: Regular price: ' . $base_price);
        
        if ($base_price <= 0) {
            $base_price = floatval($product->get_price());
            error_log('GunBroker: Using sale price: ' . $base_price);
        }

        $gunbroker_price = $base_price * (1 + $markup_percentage / 100);
        error_log('GunBroker: Calculated GunBroker price: ' . $gunbroker_price);
        
        // Validate that we have a valid price
        if (empty($base_price) || $base_price <= 0) {
            error_log('GunBroker: ERROR - Product has no price or invalid price: ' . $base_price);
            return new WP_Error('invalid_price', 'Product must have a valid price to list on GunBroker');
        }
        
        if (empty($gunbroker_price) || $gunbroker_price <= 0) {
            error_log('GunBroker: ERROR - Calculated GunBroker price is invalid: ' . $gunbroker_price);
            return new WP_Error('invalid_gunbroker_price', 'Unable to calculate valid GunBroker price');
        }

        // Get custom GunBroker title or use product title
        $custom_title = get_post_meta($product->get_id(), '_gunbroker_custom_title', true);
        $title = !empty($custom_title) ? $custom_title : $product->get_name();

        // Prepare description - strip HTML tags for GunBroker
        $description = $product->get_description();
        if (empty($description)) {
            $description = $product->get_short_description();
        }
        if (empty($description)) {
            $description = 'No description available';
        }
        
        // Strip HTML tags and clean up description for GunBroker
        $description = wp_strip_all_tags($description);
        $description = trim($description);
        if (empty($description)) {
            $description = 'No description available';
        }

        // Get category - use parameter, product-specific, or default
        if ($category_id === null) {
            $category_id = get_post_meta($product->get_id(), '_gunbroker_category', true);
            if (empty($category_id)) {
                $category_id = get_option('gunbroker_default_category', 3022); // Default firearms category
            }
        }

        // Get condition - use product-specific or default
        $condition = get_post_meta($product->get_id(), '_gunbroker_condition', true);
        if (empty($condition)) {
            $condition = 1; // Default to Factory New (1=Factory New, 2=New Old Stock, 3=Used)
        }

        // Get country code - use product-specific or default
        $country_code = get_post_meta($product->get_id(), '_gunbroker_country', true);
        if (empty($country_code)) {
            $country_code = get_option('gunbroker_default_country', 'US'); // Default to US
        }

        // FIXED: Prepare listing data with all required fields based on official API documentation
        $listing_data = array(
            'Title' => substr($title, 0, 75), // Required: 1-75 chars
            'Description' => substr($description, 0, 8000), // Required: 1-8000 chars
            'CategoryID' => intval($category_id), // Required: integer 1-999999
            'StartingBid' => floatval($gunbroker_price), // Required: decimal 0.01-999999.99 (use float, not string)
            'Quantity' => max(1, intval($product->get_stock_quantity()) ?: 1), // Required: integer 1-999999
            'ListingDuration' => intval(get_option('gunbroker_listing_duration', 7)), // Required: integer 1-99
            'PaymentMethods' => array('Check' => true, 'MoneyOrder' => true, 'CreditCard' => true), // Required: object with boolean values
            'ShippingMethods' => array('StandardShipping' => true, 'UPSGround' => true), // Required: object with boolean values
            // 'InspectionPeriod' => '3', // Optional: removed to avoid enum issues
            'ReturnsAccepted' => true, // Required: boolean
            'Condition' => intval($condition), // Required: integer 1-10
            'CountryCode' => strtoupper(substr($country_code, 0, 2)), // Required: string 2 chars
            'State' => 'TX', // Required: string 1-50 chars
            'City' => 'Austin', // Required: string 1-50 chars
            'PostalCode' => '78701', // Required: string 1-10 chars
            'MfgPartNumber' => $product->get_sku() ?: 'N/A', // Required when using IsFFLRequired
            'MinBidIncrement' => 0.50, // Optional: decimal
            'ShippingCost' => 0.00, // Optional: decimal
            'ShippingInsurance' => 0.00, // Optional: decimal
            'ShippingTerms' => 'Buyer pays shipping', // Optional: string
            'SellerContactEmail' => get_option('admin_email'), // Optional: string
            'SellerContactPhone' => '555-123-4567', // Optional: string - provide default phone
            'IsFixedPrice' => false, // Optional: boolean
            'IsFeatured' => false, // Optional: boolean
            'IsBold' => false, // Optional: boolean
            'IsHighlight' => false, // Optional: boolean
            'IsReservePrice' => false, // Optional: boolean
            'AutoRelist' => 1, // Required when using IsFFLRequired: 1 = Do Not Relist
            // 'IsFFLRequired' => false, // Conditional: Only for certain categories
            'WhoPaysForShipping' => 2, // Required: integer - 2 = Seller pays for shipping
            'WillShipInternational' => false, // Required: boolean - Will ship internationally
            'ShippingClassesSupported' => array(
                'Ground' => true,
                'TwoDay' => true,
        
            ) // Required: object - Supported shipping classes
        );
        
        // Add IsFFLRequired field conditionally based on category type
        // Based on testing, certain categories reject IsFFLRequired field
        $categories_that_reject_ffl = array(851, 2338, 3022, 3023, 3024, 3025); // Categories that reject IsFFLRequired
        
        if (!in_array($category_id, $categories_that_reject_ffl)) {
            $listing_data['IsFFLRequired'] = false; // Only include for categories that support it
        }
        // Note: IsFFLRequired is excluded for categories that reject it
        
        // Additional validation based on API documentation
        if (strlen($listing_data['Title']) < 1 || strlen($listing_data['Title']) > 75) {
            error_log('GunBroker: ERROR - Title length invalid: ' . strlen($listing_data['Title']));
            return new WP_Error('invalid_title', 'Title must be 1-75 characters');
        }
        
        if (strlen($listing_data['Description']) < 1 || strlen($listing_data['Description']) > 8000) {
            error_log('GunBroker: ERROR - Description length invalid: ' . strlen($listing_data['Description']));
            return new WP_Error('invalid_description', 'Description must be 1-8000 characters');
        }
        
        if ($listing_data['CategoryID'] < 1 || $listing_data['CategoryID'] > 999999) {
            error_log('GunBroker: ERROR - CategoryID invalid: ' . $listing_data['CategoryID']);
            return new WP_Error('invalid_category', 'CategoryID must be 1-999999');
        }
        
        if ($listing_data['StartingBid'] < 0.01 || $listing_data['StartingBid'] > 999999.99) {
            error_log('GunBroker: ERROR - StartingBid invalid: ' . $listing_data['StartingBid']);
            return new WP_Error('invalid_starting_bid', 'StartingBid must be 0.01-999999.99');
        }
        
        if ($listing_data['Condition'] < 1 || $listing_data['Condition'] > 10) {
            error_log('GunBroker: ERROR - Condition invalid: ' . $listing_data['Condition']);
            return new WP_Error('invalid_condition', 'Condition must be 1-10');
        }
        
        // Validate required objects are not empty
        if (empty($listing_data['PaymentMethods']) || !is_array($listing_data['PaymentMethods'])) {
            error_log('GunBroker: ERROR - PaymentMethods is empty or not an object');
            return new WP_Error('invalid_payment_methods', 'PaymentMethods must be a non-empty object');
        }
        
        if (empty($listing_data['ShippingMethods']) || !is_array($listing_data['ShippingMethods'])) {
            error_log('GunBroker: ERROR - ShippingMethods is empty or not an object');
            return new WP_Error('invalid_shipping_methods', 'ShippingMethods must be a non-empty object');
        }

        // Debug logging for listing data preparation
        error_log('GunBroker: Prepared listing data: ' . json_encode($listing_data, JSON_PRETTY_PRINT));
        error_log('GunBroker: Product ID: ' . $product->get_id());
        error_log('GunBroker: Product Name: ' . $product->get_name());
        error_log('GunBroker: Product Price: ' . $product->get_price());
        error_log('GunBroker: Calculated GunBroker Price: ' . $gunbroker_price);
        error_log('GunBroker: Category ID: ' . $category_id);
        error_log('GunBroker: Condition: ' . $condition);
        error_log('GunBroker: Country Code: ' . $country_code);

        // Add BuyNowPrice as optional if you want both auction and buy-now
        $buy_now_enabled = get_option('gunbroker_enable_buy_now', true);
        if ($buy_now_enabled) {
            $listing_data['BuyNowPrice'] = floatval($gunbroker_price * 1.1); // 10% higher than starting bid
        }

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
     * Get complete category hierarchy with sub-categories and sub-sub-categories
     * This method uses recursive API calls to fetch the complete hierarchy
     */
    public function get_complete_category_hierarchy() {
        // First get the basic categories
        $categories = $this->get_categories_cached();
        
        if (is_wp_error($categories)) {
            return $categories;
        }
        
        if (!isset($categories['results']) || !is_array($categories['results'])) {
            return new WP_Error('no_categories', 'No categories found in API response');
        }
        
        $all_categories = $categories['results'];
        error_log("GunBroker: Initial categories fetched: " . count($all_categories));
        
        // Now recursively fetch sub-categories for each parent category
        $this->fetch_subcategories_recursively($all_categories);
        
        // Remove duplicates based on category ID
        $unique_categories = array();
        $seen_ids = array();
        
        foreach ($all_categories as $category) {
            $cat_id = $category['categoryID'] ?? $category['id'] ?? '';
            if ($cat_id && !in_array($cat_id, $seen_ids)) {
                $unique_categories[] = $category;
                $seen_ids[] = $cat_id;
            }
        }
        
        $all_categories = $unique_categories;
        error_log("GunBroker: Total unique categories after recursive fetching: " . count($all_categories));
        
        // Build a complete category map
        $category_map = array();
        $parent_categories = array();
        
        foreach ($all_categories as $category) {
            $cat_id = $category['categoryID'] ?? $category['id'] ?? '';
            $cat_name = $category['categoryName'] ?? $category['name'] ?? 'Unknown';
            $parent_id = $category['parentCategoryID'] ?? $category['parentId'] ?? '';
            $can_contain_items = $category['canContainItems'] ?? $category['can_contain_items'] ?? false;
            
            if (!$cat_id) continue;
            
            $cat_data = array(
                'id' => $cat_id,
                'name' => $cat_name,
                'parent_id' => $parent_id,
                'can_contain_items' => $can_contain_items,
                'children' => array(),
                'level' => 0
            );
            
            $category_map[$cat_id] = $cat_data;
            
            if ($parent_id == '' || $parent_id == 0 || $parent_id == '0') {
                $parent_categories[] = $cat_data;
            }
        }
        
        error_log("GunBroker: Parent categories: " . count($parent_categories));
        error_log("GunBroker: Total categories in map: " . count($category_map));
        
        // Build hierarchy by linking children to parents
        foreach ($category_map as $cat_id => $category) {
            $parent_id = $category['parent_id'];
            if ($parent_id && isset($category_map[$parent_id])) {
                $category_map[$parent_id]['children'][] = $cat_id;
            }
        }
        
        // Calculate levels and find terminal categories
        $terminal_categories = array();
        
        function calculate_levels($category_map, $cat_id, $level = 0) {
            if (!isset($category_map[$cat_id])) return;
            
            $category_map[$cat_id]['level'] = $level;
            
            // Check if this category can contain items (terminal category)
            $can_contain_items = $category_map[$cat_id]['can_contain_items'] ?? false;
            $has_children = !empty($category_map[$cat_id]['children']);
            
            if ($can_contain_items && !$has_children) {
                // This is a terminal category that can contain items
                $GLOBALS['terminal_categories'][] = $category_map[$cat_id];
            } else if ($has_children) {
                // This is a parent category, calculate levels for children
                foreach ($category_map[$cat_id]['children'] as $child_id) {
                    calculate_levels($category_map, $child_id, $level + 1);
                }
            }
        }
        
        // Start with root categories
        foreach ($parent_categories as $parent) {
            calculate_levels($category_map, $parent['id'], 0);
        }
        
        // Build hierarchical tree structure
        function build_tree($category_map, $parent_id = null, $level = 0) {
            $tree = array();
            
            foreach ($category_map as $cat_id => $category) {
                if (($parent_id === null && ($category['parent_id'] == '' || $category['parent_id'] == 0 || $category['parent_id'] == '0')) ||
                    ($parent_id !== null && $category['parent_id'] == $parent_id)) {
                    
                    $category['level'] = $level;
                    $category['children'] = build_tree($category_map, $cat_id, $level + 1);
                    $tree[] = $category;
                }
            }
            
            return $tree;
        }
        
        $hierarchical_tree = build_tree($category_map);
        
        error_log("GunBroker: Terminal categories found: " . count($GLOBALS['terminal_categories'] ?? array()));
        
        return array(
            'category_map' => $category_map,
            'parent_categories' => $parent_categories,
            'terminal_categories' => $GLOBALS['terminal_categories'] ?? array(),
            'hierarchical_tree' => $hierarchical_tree,
            'all_categories' => array_values($category_map)
        );
    }

    /**
     * Recursively fetch sub-categories for each parent category
     * Uses the official GunBroker API endpoints as per documentation
     */
    private function fetch_subcategories_recursively(&$all_categories) {
        $parent_categories = array();
        
        // First, identify parent categories (those without parentCategoryID or with parentCategoryID = 0)
        foreach ($all_categories as $category) {
            $parent_id = $category['parentCategoryID'] ?? $category['parentId'] ?? '';
            if ($parent_id == '' || $parent_id == 0 || $parent_id == '0') {
                $parent_categories[] = $category;
            }
        }
        
        error_log("GunBroker: Found " . count($parent_categories) . " parent categories to fetch sub-categories for");
        
        // For each parent category, fetch its sub-categories using the official API
        foreach ($parent_categories as $parent) {
            $parent_id = $parent['categoryID'] ?? $parent['id'] ?? '';
            $parent_name = $parent['categoryName'] ?? $parent['name'] ?? 'Unknown';
            
            if (!$parent_id) continue;
            
            // Method 1: Use /Categories?ParentCategoryID={parent_id} (official way)
            $result = $this->make_request("Categories?ParentCategoryID={$parent_id}");
            if (!is_wp_error($result) && isset($result['results']) && is_array($result['results'])) {
                $subcategories = $result['results'];
                error_log("GunBroker: Found " . count($subcategories) . " sub-categories for {$parent_name} (ID: {$parent_id}) using ParentCategoryID parameter");
                
                // Add sub-categories to our list
                foreach ($subcategories as $subcategory) {
                    $sub_id = $subcategory['categoryID'] ?? $subcategory['id'] ?? '';
                    if ($sub_id) {
                        // Set the parent ID for this sub-category
                        $subcategory['parentCategoryID'] = $parent_id;
                        $all_categories[] = $subcategory;
                    }
                }
            } else {
                // Method 2: Fallback to /Categories/{categoryID} to get SubCategories array
                $result = $this->make_request("Categories/{$parent_id}");
                if (!is_wp_error($result) && isset($result['subCategories']) && is_array($result['subCategories'])) {
                    $subcategories = $result['subCategories'];
                    error_log("GunBroker: Found " . count($subcategories) . " sub-categories for {$parent_name} (ID: {$parent_id}) using Categories/{categoryID} endpoint");
                    
                    // Add sub-categories to our list
                    foreach ($subcategories as $subcategory) {
                        $sub_id = $subcategory['categoryID'] ?? $subcategory['id'] ?? '';
                        if ($sub_id) {
                            // Set the parent ID for this sub-category
                            $subcategory['parentCategoryID'] = $parent_id;
                            $all_categories[] = $subcategory;
                        }
                    }
                }
            }
        }
        
        // Now recursively fetch sub-sub-categories for the sub-categories we just found
        $new_categories = array_slice($all_categories, count($all_categories) - 50); // Get the last 50 categories (likely the new sub-categories)
        foreach ($new_categories as $category) {
            $parent_id = $category['parentCategoryID'] ?? $category['parentId'] ?? '';
            if ($parent_id && $parent_id != '0') {
                // This is a sub-category, try to fetch its sub-categories
                $cat_id = $category['categoryID'] ?? $category['id'] ?? '';
                $cat_name = $category['categoryName'] ?? $category['name'] ?? 'Unknown';
                
                if (!$cat_id) continue;
                
                // Method 1: Use /Categories?ParentCategoryID={cat_id} (official way)
                $result = $this->make_request("Categories?ParentCategoryID={$cat_id}");
                if (!is_wp_error($result) && isset($result['results']) && is_array($result['results'])) {
                    $sub_subcategories = $result['results'];
                    error_log("GunBroker: Found " . count($sub_subcategories) . " sub-sub-categories for {$cat_name} (ID: {$cat_id}) using ParentCategoryID parameter");
                    
                    // Add sub-sub-categories to our list
                    foreach ($sub_subcategories as $sub_subcategory) {
                        $sub_sub_id = $sub_subcategory['categoryID'] ?? $sub_subcategory['id'] ?? '';
                        if ($sub_sub_id) {
                            // Set the parent ID for this sub-sub-category
                            $sub_subcategory['parentCategoryID'] = $cat_id;
                            $all_categories[] = $sub_subcategory;
                        }
                    }
                } else {
                    // Method 2: Fallback to /Categories/{categoryID} to get SubCategories array
                    $result = $this->make_request("Categories/{$cat_id}");
                    if (!is_wp_error($result) && isset($result['subCategories']) && is_array($result['subCategories'])) {
                        $sub_subcategories = $result['subCategories'];
                        error_log("GunBroker: Found " . count($sub_subcategories) . " sub-sub-categories for {$cat_name} (ID: {$cat_id}) using Categories/{categoryID} endpoint");
                        
                        // Add sub-sub-categories to our list
                        foreach ($sub_subcategories as $sub_subcategory) {
                            $sub_sub_id = $sub_subcategory['categoryID'] ?? $sub_subcategory['id'] ?? '';
                            if ($sub_sub_id) {
                                // Set the parent ID for this sub-sub-category
                                $sub_subcategory['parentCategoryID'] = $cat_id;
                                $all_categories[] = $sub_subcategory;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get organized categories for dropdown (terminal categories only)
     */
    public function get_organized_categories() {
        $hierarchy = $this->get_complete_category_hierarchy();
        
        if (is_wp_error($hierarchy)) {
            return $hierarchy;
        }
        
        return array(
            'parent_categories' => $hierarchy['parent_categories'],
            'child_categories' => array_filter($hierarchy['all_categories'], function($cat) {
                return $cat['parent_id'] != '' && $cat['parent_id'] != 0;
            }),
            'terminal_categories' => $hierarchy['terminal_categories'],
            'all_categories' => $hierarchy['all_categories']
        );
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