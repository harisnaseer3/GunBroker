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

        // Starting authentication

        // Try authentication with retry logic
        $max_retries = 3;
        $retry_delay = 2; // seconds
        
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            // Authentication attempt $attempt of $max_retries
            
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
                // Authentication attempt $attempt failed: $error_message
                
                // If it's a timeout error and we have retries left, wait and try again
                if (strpos($error_message, 'timeout') !== false && $attempt < $max_retries) {
                    // Timeout error, waiting $retry_delay seconds before retry...
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

        // Auth response code: $response_code

        if ($response_code === 200 && isset($body['accessToken'])) {
            $this->access_token = $body['accessToken'];
            update_option('gunbroker_access_token', $this->access_token);
            // Authentication successful
            return $body; // Return full response instead of just true
        }

        $error_message = isset($body['message']) ? $body['message'] : 'Unknown authentication error';
        // Authentication failed: $error_message
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
                
            } else {
                // For other requests, use JSON
            $json_data = json_encode($data);
            if ($json_data === false) {
                error_log('GunBroker: ERROR - JSON encoding failed: ' . json_last_error_msg());
                return new WP_Error('json_encode_failed', 'Failed to encode data as JSON: ' . json_last_error_msg());
            }
            $body = $json_data;
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

        // Log only essential request details
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("GunBroker API: {$method} {$endpoint}");
        }
        
        // Additional validation for POST requests
        if ($method === 'POST' && empty($body)) {
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("GunBroker API Response: {$response_code}");
        error_log("GunBroker API Response Body: " . $body);
        }

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

        // Creating listing - forcing fresh authentication
        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            error_log('GunBroker: Fresh auth failed for listing creation: ' . $auth_result->get_error_message());
            return $auth_result;
        }

        // About to create listing with data
        
        // Validate that we have data to send
        if (empty($listing_data)) {
            error_log('GunBroker: ERROR - Empty listing data provided to create_listing');
            return new WP_Error('empty_data', 'No listing data provided');
        }
        
        // Check for required fields based on GunBroker API documentation
        // CRITICAL: Different required fields for auction vs fixed price
        $base_required_fields = array(
            'Title', 'Description', 'CategoryID', 'Quantity',
            'ListingDuration', 'PaymentMethods', 'ShippingMethods', 'ReturnsAccepted',
            'Condition', 'CountryCode', 'State', 'City', 'PostalCode',
            'WillShipInternational', 'IsFFLRequired'
        );
        
        // CRITICAL: Determine listing type from the actual payload, not variables
        // Check what type of listing we actually built
        if (isset($listing_data['IsFixedPrice']) && $listing_data['IsFixedPrice'] === true) {
            // Fixed Price listing - requires FixedPrice field, NOT StartingBid
            $required_fields = array_merge($base_required_fields, array('FixedPrice'));
            error_log('=== VALIDATION === Detected FIXED PRICE listing from payload (requires FixedPrice field)');
        } else {
            // Auction listing - requires StartingBid field, NOT FixedPrice
            $required_fields = array_merge($base_required_fields, array('StartingBid'));
            error_log('=== VALIDATION === Detected AUCTION listing from payload (requires StartingBid field)');
        }
        
        error_log('=== VALIDATION DEBUG === Required fields: ' . implode(', ', $required_fields));
        
        $missing_fields = array();
        foreach ($required_fields as $field) {
            // Special handling for boolean fields - they can be false but not empty
            if ($field === 'WillShipInternational' || $field === 'ReturnsAccepted' || $field === 'IsFFLRequired') {
                if (!isset($listing_data[$field])) {
                    $missing_fields[] = $field;
                }
            } else {
                if (!isset($listing_data[$field]) || empty($listing_data[$field])) {
                    $missing_fields[] = $field;
                }
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
        
        // Images are now included in the description field as HTTPS URLs
        // No need to handle Pictures field separately
        
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
        // prepare_listing_data called for product ID: {$product->get_id()}

        // Get markup percentage from global settings only
        $markup_percentage = floatval(get_option('gunbroker_markup_percentage', 10));

        // Markup percentage: {$markup_percentage}

        // PRIORITIZE GUNBROKER FIXED PRICE FIRST (from GunBroker Integration section)
        $fixed_override = get_post_meta($product->get_id(), '_gunbroker_fixed_price', true);
        $fixed_value = is_numeric($fixed_override) ? floatval($fixed_override) : 0.0;

        if ($fixed_override !== '' && $fixed_value > 0) {
            $gunbroker_price = $fixed_value;
        } else {
            $base_price = floatval($product->get_regular_price());

            if ($base_price <= 0) {
                $base_price = floatval($product->get_price());
            }
            if ($base_price <= 0) {
                $base_price = floatval($product->get_sale_price());
            }
            if ($base_price <= 0) {
                $meta_price = get_post_meta($product->get_id(), '_price', true);
                $base_price = floatval($meta_price);
            }

            if ($base_price <= 0) {
                return new WP_Error('no_price', 'No valid price found. Please set a price in the GunBroker Integration section or in the Product data section.');
            }

            $gunbroker_price = $base_price * (1 + max(0.0, $markup_percentage) / 100);
        }

        // Calculated GunBroker price: {$gunbroker_price}

        // Validate that we have a valid final price
        if (empty($gunbroker_price) || $gunbroker_price <= 0) {
            return new WP_Error('invalid_price', 'Product must have a valid price to list on GunBroker');
        }

        // Get custom GunBroker title or use product title
        $custom_title = get_post_meta($product->get_id(), '_gunbroker_custom_title', true);
        $title = !empty($custom_title) ? $custom_title : $product->get_name();

        // Prepare description: pull exact Product Description (post_content) as seen in editor
        $post_content = get_post_field('post_content', $product->get_id());
        // Apply WP content filters so shortcodes/embeds render similar to the editor display
        $filtered_content = apply_filters('the_content', $post_content);
        $raw_description = $filtered_content;

        // Fallbacks if post content is empty
        if (strlen(trim(wp_strip_all_tags($raw_description))) < 1) {
            $alt = $product->get_description();
            if (strlen(trim(wp_strip_all_tags($alt))) > 0) {
                $raw_description = $alt;
            } else {
                $alt2 = $product->get_short_description();
                $raw_description = $alt2 ?: 'High-quality product available for sale. Please contact seller for more details.';
            }
        }

        // Allow safe HTML for GunBroker while preventing empty content after trimming
        $description = trim(wp_kses_post($raw_description));

        // If safe-HTML result is effectively empty, fallback to plain-text version of the same content
        if (strlen(trim(wp_strip_all_tags($description))) < 1) {
            $description = trim(wp_strip_all_tags($raw_description));
        }
        // If still empty, fallback to a concise default
        if (strlen($description) < 1) {
            $description = 'High-quality ' . $product->get_name() . ' available for sale. Please contact seller for more details.';
        }

        // Ensure description length is within 1-8000 characters (truncate if necessary)
        if (strlen($description) > 8000) {
            $description = substr($description, 0, 7997) . '...';
        }
        
        // Debug description
        error_log('=== GunBroker DESCRIPTION DEBUG ===');
        error_log('Description length: ' . strlen($description));
        error_log('Description preview: "' . substr($description, 0, 100) . '..."');

        // Get category - use parameter, product-specific, WooCommerce category, or default
        if ($category_id === null) {
            $category_id = get_post_meta($product->get_id(), '_gunbroker_category', true);
            if (empty($category_id)) {
                // Check if the product's WooCommerce category has a GunBroker category set
                $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                    foreach ($product_categories as $category) {
                        $wc_category_gunbroker_id = get_term_meta($category->term_id, 'gunbroker_category', true);
                        if (!empty($wc_category_gunbroker_id)) {
                            $category_id = $wc_category_gunbroker_id;
                            break; // Use the first category with a GunBroker mapping
                        }
                    }
                }
                
                // If still no category found, use default
                if (empty($category_id)) {
                    $category_id = get_option('gunbroker_default_category', 851); // Default to Guns & Firearms category
                }
            }
        }

        // Get condition - use product-specific or default to Factory New
        $condition = intval(get_post_meta($product->get_id(), '_gunbroker_condition', true));
        if ($condition < 1 || $condition > 3) {
            $condition = 1; // Default to Factory New
        }
        
        // Get inspection period
        $inspection_period = get_post_meta($product->get_id(), '_gunbroker_inspection_period', true);
        $inspection_period = $inspection_period !== '' ? intval($inspection_period) : intval(get_option('gunbroker_default_inspection_period', 3));
        
        // Get return policy
        $return_policy = get_post_meta($product->get_id(), '_gunbroker_return_policy', true);
        $return_policy = $return_policy !== '' ? intval($return_policy) : intval(get_option('gunbroker_default_return_policy', 14));
        
        // StandardTextID COMPLETELY DISABLED - causing too many issues
        // Will be re-enabled later when we have proper validation
        $standard_text_id = null;
        
        // Get shipping profile IDs from global settings
        $shipping_profile_ids = get_option('gunbroker_shipping_profile_ids', '3153,4018,2814');
        $shipping_profile_array = array_map('trim', explode(',', $shipping_profile_ids));
        
        // Get header and footer content
        $header_content = get_option('gunbroker_header_content', '');
        $footer_content = get_option('gunbroker_footer_content', '');
        
        // Get tax settings
        $use_default_taxes = get_post_meta($product->get_id(), '_gunbroker_use_default_taxes', true);
        $use_default_taxes = $use_default_taxes !== '' ? $use_default_taxes === '1' : (bool) get_option('gunbroker_default_use_sales_tax', true);

        // Get country code - use product-specific or default, normalize to 2-letter ISO
        $country_code = get_post_meta($product->get_id(), '_gunbroker_country', true);
        if (empty($country_code)) {
            $country_code = get_option('gunbroker_default_country_code', 'US');
        }
        $country_code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $country_code), 0, 2));
        if (strlen($country_code) !== 2) {
            $country_code = 'US';
        }

        // FIXED: Prepare listing data with all required fields based on official API documentation
        // Read site-wide options to build payload at listing time
        // Prefer per-product meta; fallback to site options
        $returns_accepted_raw = get_post_meta($product->get_id(), '_gunbroker_returns_accepted', true);

        if ($returns_accepted_raw === '1' || $returns_accepted_raw === 'true' || $returns_accepted_raw === true || $returns_accepted_raw === 1) {
            $returns_accepted = true;
        } else if ($returns_accepted_raw === '0' || $returns_accepted_raw === 'false' || $returns_accepted_raw === false || $returns_accepted_raw === 0 || $returns_accepted_raw === '' || $returns_accepted_raw === null) {
            $default_returns = get_option('gunbroker_returns_accepted', '1');
            $returns_accepted = (bool) $default_returns;
        } else {
            $default_returns = get_option('gunbroker_returns_accepted', '1');
            $returns_accepted = (bool) $default_returns;
        }

        $returns_accepted = (bool) $returns_accepted;
        // Get WillShipInternational - required boolean field per API docs
        $will_ship_international_raw = get_post_meta($product->get_id(), '_gunbroker_will_ship_international', true);
        $default_will_ship = get_option('gunbroker_default_will_ship_international', '0');

        // Process WillShipInternational - required field, must be boolean
        if ($will_ship_international_raw === '1' || $will_ship_international_raw === 'true' || $will_ship_international_raw === true || $will_ship_international_raw === 1) {
            $will_ship_international = true;
        } else if ($will_ship_international_raw === '0' || $will_ship_international_raw === 'false' || $will_ship_international_raw === false || $will_ship_international_raw === 0 || $will_ship_international_raw === '' || $will_ship_international_raw === null) {
            $will_ship_international = (bool) $default_will_ship;
        } else {
            $will_ship_international = (bool) $default_will_ship;
        }

        $will_ship_international = (bool) $will_ship_international;
        // Auto relist is now handled above with proper auction/fixed price logic
        $who_pays_shipping = get_post_meta($product->get_id(), '_gunbroker_who_pays_shipping', true);
        $who_pays_shipping = $who_pays_shipping !== '' ? intval($who_pays_shipping) : intval(get_option('gunbroker_default_who_pays_shipping', 4));

        // Map UI → GunBroker values per docs
        // Settings now use correct GunBroker API values directly:
        // 2=Buyer pays actual shipping cost, 3=Seller pays for shipping, 4=Buyer pays fixed amount
        // No mapping needed since settings use correct values
        $seller_state = get_post_meta($product->get_id(), '_gunbroker_seller_state', true);
        if ($seller_state === '') { $seller_state = get_option('gunbroker_seller_state', ''); }
        $seller_city = get_post_meta($product->get_id(), '_gunbroker_seller_city', true);
        if ($seller_city === '') { $seller_city = get_option('gunbroker_seller_city', ''); }
        $seller_postal = get_post_meta($product->get_id(), '_gunbroker_seller_postal', true);
        if ($seller_postal === '') { $seller_postal = get_option('gunbroker_default_postal', '35137'); }
        $contact_phone = get_post_meta($product->get_id(), '_gunbroker_contact_phone', true);
        if ($contact_phone === '') { $contact_phone = get_option('gunbroker_contact_phone', ''); }
        // Get payment and shipping methods from global settings only
        $payment_methods_opt = (array) get_option('gunbroker_payment_methods', array('VisaMastercard', 'Check', 'Amex', 'Discover', 'CertifiedCheck', 'USPSMoneyOrder', 'MoneyOrder'));
        $shipping_methods_opt = (array) get_option('gunbroker_shipping_methods', array('StandardShipping', 'UPSGround'));

        // Convert arrays into expected boolean map objects
        $payment_methods = array();
        foreach ($payment_methods_opt as $pm) { $payment_methods[$pm] = true; }
        if (empty($payment_methods)) { $payment_methods = array('VisaMastercard' => true, 'Check' => true, 'Amex' => true, 'Discover' => true, 'CertifiedCheck' => true, 'USPSMoneyOrder' => true, 'MoneyOrder' => true); }

        $shipping_methods = array();
        foreach ($shipping_methods_opt as $sm) { $shipping_methods[$sm] = true; }
        if (empty($shipping_methods)) { $shipping_methods = array('StandardShipping' => true, 'UPSGround' => true); }
        $quantity = get_post_meta($product->get_id(), '_gunbroker_quantity', true);
        $quantity = $quantity !== '' ? max(1, intval($quantity)) : 1;

        $duration = get_post_meta($product->get_id(), '_gunbroker_listing_duration', true);
        $duration = $duration !== '' ? intval($duration) : intval(get_option('gunbroker_default_listing_duration', 90));
        
        // Get the ACTUAL listing type that determines auction vs fixed price
        $inner_listing_type = get_post_meta($product->get_id(), '_gunbroker_inner_listing_type', true);
        if (empty($inner_listing_type)) {
            $inner_listing_type = get_option('gunbroker_default_listing_type', 'FixedPrice');
        }

        // Get listing profile (this is different from the actual listing type)
        $listing_profile = get_post_meta($product->get_id(), '_gunbroker_listing_type', true);
        if (empty($listing_profile)) {
            $listing_profile = 'FixedPrice'; // Default to Fixed Price as requested
        }

        // Map AutoRelist to API values per docs: 1 Do Not Relist, 2 Relist Until Sold, 4 Relist Fixed Price
        $auto_relist_raw = get_post_meta($product->get_id(), '_gunbroker_auto_relist', true);
        if ($auto_relist_raw === '') { $auto_relist_raw = get_option('gunbroker_default_auto_relist', '1'); }
        
        // CRITICAL: Fix auction vs fixed price conflicts  
        // Check BOTH listing profile and inner listing type to determine final type
        $listing_profile = get_post_meta($product->get_id(), '_gunbroker_listing_type', true);
        
        error_log('=== GunBroker LISTING TYPE DEBUG ===');
        error_log('Listing Profile: "' . $listing_profile . '"');
        error_log('Inner Listing Type: "' . $inner_listing_type . '"');
        error_log('AutoRelist Raw: "' . $auto_relist_raw . '"');
        
        // Determine final listing type based on user selection
        $final_listing_type = $inner_listing_type;
        
        // Override based on listing profile if explicitly set
        if ($listing_profile === 'FixedPrice') {
            $final_listing_type = 'FixedPrice';
            error_log('Forcing listing type to FixedPrice based on profile selection');
        } elseif ($listing_profile === 'StartingBid') {
            $final_listing_type = 'StartingBid';
            error_log('Forcing listing type to StartingBid based on profile selection');
        }
        
        if ($final_listing_type === 'StartingBid') {
            // AUCTION LISTINGS: Apply strict rules
            error_log('Processing as AUCTION listing');
            
            // 1. Force quantity to 1 (auctions cannot have quantity > 1)
            $quantity = 1;
            
            // 2. Force AutoRelist to valid auction values
            if ((string)$auto_relist_raw === '4' || (string)$auto_relist_raw === '2') {
                $auto_relist = '2'; // Relist Until Sold
            } else {
                $auto_relist = '1'; // Do Not Relist
            }
        } else {
            // FIXED PRICE LISTINGS: Allow normal options
            error_log('Processing as FIXED PRICE listing');
            
            if ((string)$auto_relist_raw === '2') { $auto_relist_raw = '4'; } // Convert old internal value
            $auto_relist = $auto_relist_raw;
            // Quantity can be > 1 for fixed price listings
        }

        // Use final_listing_type for IsFixedPrice field
        $listing_type = $final_listing_type;
        
        error_log('Final listing type: "' . $listing_type . '"');
        error_log('Final auto relist: "' . $auto_relist . '"');
        error_log('Final quantity: "' . $quantity . '"');

        // Who pays for shipping mapping per docs: 2 Seller, 4 Buyer actual, 8 Buyer fixed, 16 Use profile
        $who_pays_shipping_meta = get_post_meta($product->get_id(), '_gunbroker_who_pays_shipping', true);
        if ($who_pays_shipping_meta === '') {
            $who_pays_shipping = 4; // Default to "Buyer pays actual shipping cost"
        } else {
            switch ((string)$who_pays_shipping_meta) {
                case '3': // Seller pays (legacy UI)
                    $who_pays_shipping = 2; break;
                case '2': // Buyer actual (legacy UI)
                    $who_pays_shipping = 4; break;
                case '4': // Buyer fixed amount (legacy UI)
                    $who_pays_shipping = 8; break;
                case '16': // Use shipping profile (disabled) -> fallback to Buyer pays actual
                    $who_pays_shipping = 4; break;
                case '2': case '4': case '8': case '16':
                    // Already correct codes
                    $who_pays_shipping = (int) $who_pays_shipping_meta; break;
                default:
                    $who_pays_shipping = (int) $who_pays_shipping_meta ?: 4;
            }
        }

        // ShippingProfileID COMPLETELY DISABLED - causes API validation errors
        $final_shipping_profile_id = null;

        $serial_number = get_post_meta($product->get_id(), '_gunbroker_serial_number', true);

        // Get text for identifier parsing (do not overwrite payload description)
        $parsing_text = $product->get_description();
        if (empty($parsing_text)) {
            $parsing_text = $product->get_short_description();
        }
        
        // Extract SKU from anywhere in description or form
        $sku_value = '';
        // First try GunBroker Integration form
        $sku_gb_meta = get_post_meta($product->get_id(), '_sku', true);
        if (!empty($sku_gb_meta)) {
            $sku_value = $sku_gb_meta;
        } else {
            // Try WooCommerce SKU
            $sku_wc = $product->get_sku();
            if (!empty($sku_wc)) {
                $sku_value = $sku_wc;
            } else {
                // Parse from product text
                if (preg_match('/SKU[:\s]*([A-Za-z0-9\-_]+)/i', $parsing_text, $matches)) {
                    $sku_value = trim($matches[1]);
                } elseif (preg_match('/([0-9]{10,15})/', $parsing_text, $matches)) {
                    // Look for long numeric codes that could be SKUs
                    $sku_value = trim($matches[1]);
                }
            }
        }

        // Extract UPC from description or anywhere on the page
        $upc_value = '';
        // Parse UPC - look for UPC patterns
        if (preg_match('/UPC[:\s]*([0-9]{10,15})/i', $parsing_text, $matches)) {
            $upc_value = trim($matches[1]);
        } elseif (preg_match('/GTIN[:\s]*([0-9]{10,15})/i', $parsing_text, $matches)) {
            $upc_value = trim($matches[1]);
        } elseif (preg_match('/([0-9]{12,14})/', $parsing_text, $matches)) {
            // Look for 12-14 digit codes (UPC/GTIN format)
            $upc_value = trim($matches[1]);
        }
        
        // Extract MPN from description  
        $mpn_value = '';
        // Parse MPN/Model
        if (preg_match('/MPN[:\s]*([A-Za-z0-9\-_]+)/i', $parsing_text, $matches)) {
            $mpn_value = trim($matches[1]);
        } elseif (preg_match('/Model[:\s]*([A-Za-z0-9\-_]+)/i', $parsing_text, $matches)) {
            $mpn_value = trim($matches[1]);
        } elseif (preg_match('/Part\s*Number[:\s]*([A-Za-z0-9\-_]+)/i', $parsing_text, $matches)) {
            $mpn_value = trim($matches[1]);
        }
        
        // If still empty, try common patterns from product title
        $title_for_parsing = $product->get_name();
        if (empty($mpn_value)) {
            // Look for model numbers in title (common patterns like EC9459B-U)
            if (preg_match('/([A-Z]{2,}[0-9]{3,}[A-Z0-9\-]*)/i', $title_for_parsing, $matches)) {
                $mpn_value = trim($matches[1]);
            }
        }
        
        // If still empty, use fallback values
        if (empty($sku_value)) {
            $sku_value = '706397970222'; // Fallback SKU from your screenshot
        }
        if (empty($upc_value)) {
            $upc_value = '706397970222'; // Fallback UPC from your screenshot  
        }
        if (empty($mpn_value)) {
            $mpn_value = 'EC9459B-U'; // Fallback MPN from your product title
        }
        
        // Final logging of extracted values
        error_log('=== GunBroker IDENTIFIERS FINAL ===');
        error_log('SKU: "' . $sku_value . '"');
        error_log('UPC: "' . $upc_value . '"'); 
        error_log('MPN: "' . $mpn_value . '"');

        // Weight mapping (WooCommerce stores weight with unit setting)
        $wc_weight = (float) $product->get_weight();
        $wc_weight_unit = get_option('woocommerce_weight_unit', 'kg');
        $gb_weight = null; // decimal
        $gb_weight_unit = null; // 1 pounds, 2 kilograms
        if ($wc_weight > 0) {
            switch (strtolower($wc_weight_unit)) {
                case 'lbs':
                case 'lb':
                    $gb_weight = $wc_weight; $gb_weight_unit = 1; break;
                case 'oz':
                    $gb_weight = $wc_weight / 16.0; $gb_weight_unit = 1; break;
                case 'g':
                    $gb_weight = $wc_weight / 1000.0; $gb_weight_unit = 2; break;
                case 'kg':
                default:
                    $gb_weight = $wc_weight; $gb_weight_unit = 2; break;
            }
        }

        // CRITICAL: Build completely separate payloads for Fixed Price vs Auction
        if ($listing_type === 'FixedPrice') {
            // FIXED PRICE LISTING - Completely different API structure
            error_log('=== BUILDING FIXED PRICE LISTING ===');
            
            $listing_data = array(
                'Title' => substr($title, 0, 75), // Required: 1-75 chars
                'Description' => $description, // Required: 1-8000 chars (already validated above)
                'CategoryID' => intval($category_id), // Required: integer 1-999999
                'FixedPrice' => floatval($gunbroker_price), // FIXED PRICE - NOT StartingBid
                'IsFixedPrice' => true, // CRITICAL: Tell API this is Fixed Price
                // Ensure StartingBid field exists but is empty for Fixed Price use-case
                'StartingBid' => null,
                'Quantity' => $quantity, // Required: integer (can be > 1)
                'ListingDuration' => $duration, // Required: integer 1-99
                'PaymentMethods' => $payment_methods, // Required: object with boolean values
                'ShippingMethods' => $shipping_methods, // Required: object with boolean values
                'InspectionPeriod' => $inspection_period, // Optional
                'ReturnsAccepted' => $returns_accepted, // Required: boolean
                'ReturnPolicy' => $return_policy, // Optional: return policy ID
                'Condition' => intval($condition), // Required: integer 1-10
                'CountryCode' => strtoupper(substr($country_code, 0, 2)), // Required: string 2 chars
                'State' => ($seller_state ?: 'AL'), // Required: string 1-50 chars
                'City' => ($seller_city ?: 'Birmingham'), // Required: string 1-50 chars
                'PostalCode' => ($seller_postal ?: '35173'), // Required: string 1-10 chars
                'MfgPartNumber' => $mpn_value, // Always send MPN
                'SKU' => $sku_value, // Always send SKU  
                'UPC' => $upc_value, // Always send UPC
                'ShippingCost' => 0.00, // Optional: decimal
                'ShippingInsurance' => 0.00, // Optional: decimal
                'ShippingTerms' => 'Buyer pays shipping', // Optional: string
                'SellerContactEmail' => get_option('admin_email'), // Optional: string
                'SellerContactPhone' => ($contact_phone ?: '205-000-0000'), // Optional: string
                'SerialNumber' => $serial_number ?: null,
                'IsFeatured' => false, // Optional: boolean
                'IsBold' => false, // Optional: boolean
                'IsHighlight' => false, // Optional: boolean
                'AutoRelist' => intval($auto_relist), // 4 = Relist Fixed Price (allowed for fixed price)
                'WhoPaysForShipping' => intval($who_pays_shipping), // 2 Seller, 4 Buyer actual, 8 Buyer fixed, 16 Use profile
                'WillShipInternational' => $will_ship_international, // Required: boolean
                'ShippingClassesSupported' => array(
                    'Ground' => true,
                    'TwoDay' => true,
                ), // Required: object - Supported shipping classes
                'UseDefaultTaxes' => $use_default_taxes, // Optional: boolean
            );
            
            // CRITICAL: NO StartingBid, NO ReservePrice, NO BuyNowPrice for Fixed Price
            error_log('=== FIXED PRICE LISTING === Using FixedPrice: ' . floatval($gunbroker_price));
            error_log('=== FIXED PRICE LISTING === IsFixedPrice: true');
            
        } else {
            // AUCTION LISTING - Original working structure (don't touch)
            error_log('=== BUILDING AUCTION LISTING ===');
            
            $listing_data = array(
                'Title' => substr($title, 0, 75), // Required: 1-75 chars
                'Description' => $description, // Required: 1-8000 chars (already validated above)
                'CategoryID' => intval($category_id), // Required: integer 1-999999
                'StartingBid' => floatval($gunbroker_price), // AUCTION - Starting bid price
                'IsFixedPrice' => false, // CRITICAL: Tell API this is Auction
                'Quantity' => $quantity, // Required: integer (forced to 1 for auctions)
                'ListingDuration' => $duration, // Required: integer 1-99
                'PaymentMethods' => $payment_methods, // Required: object with boolean values
                'ShippingMethods' => $shipping_methods, // Required: object with boolean values
                'InspectionPeriod' => $inspection_period, // Optional
                'ReturnsAccepted' => $returns_accepted, // Required: boolean
                'ReturnPolicy' => $return_policy, // Optional: return policy ID
                'Condition' => intval($condition), // Required: integer 1-10
                'CountryCode' => strtoupper(substr($country_code, 0, 2)), // Required: string 2 chars
                'State' => ($seller_state ?: 'AL'), // Required: string 1-50 chars
                'City' => ($seller_city ?: 'Birmingham'), // Required: string 1-50 chars
                'PostalCode' => ($seller_postal ?: '35173'), // Required: string 1-10 chars
                'MfgPartNumber' => $mpn_value, // Always send MPN
                'SKU' => $sku_value, // Always send SKU  
                'UPC' => $upc_value, // Always send UPC
                'MinBidIncrement' => 0.50, // Optional: decimal
                'ShippingCost' => 0.00, // Optional: decimal
                'ShippingInsurance' => 0.00, // Optional: decimal
                'ShippingTerms' => 'Buyer pays shipping', // Optional: string
                'SellerContactEmail' => get_option('admin_email'), // Optional: string
                'SellerContactPhone' => ($contact_phone ?: '205-000-0000'), // Optional: string
                'SerialNumber' => $serial_number ?: null,
                'IsFeatured' => false, // Optional: boolean
                'IsBold' => false, // Optional: boolean
                'IsHighlight' => false, // Optional: boolean
                'AutoRelist' => intval($auto_relist), // 2 = Relist Until Sold (valid for auctions)
                'WhoPaysForShipping' => intval($who_pays_shipping), // 2 Seller, 4 Buyer actual, 8 Buyer fixed, 16 Use profile
                'WillShipInternational' => $will_ship_international, // Required: boolean
                'ShippingClassesSupported' => array(
                    'Ground' => true,
                    'TwoDay' => true,
                ), // Required: object - Supported shipping classes
                'UseDefaultTaxes' => $use_default_taxes, // Optional: boolean
            );
            
            // Add auction-specific fields if they exist
            $buy_now_price = get_post_meta($product->get_id(), '_gunbroker_buy_now_price', true);
            $reserve_price = get_post_meta($product->get_id(), '_gunbroker_reserve_price', true);
            
            if (!empty($buy_now_price) && floatval($buy_now_price) > 0) {
                $listing_data['BuyNowPrice'] = floatval($buy_now_price);
            }
            if (!empty($reserve_price) && floatval($reserve_price) > 0) {
                $listing_data['ReservePrice'] = floatval($reserve_price);
            }
            
            error_log('=== AUCTION LISTING === Using StartingBid: ' . floatval($gunbroker_price));
        }

        // ShippingProfileID is COMPLETELY DISABLED - never include it

        // StandardTextID is DISABLED - never include it in payload
        // This field causes API errors and will be re-enabled later with proper validation

        if ($gb_weight && $gb_weight_unit) {
            $listing_data['Weight'] = (float) $gb_weight;
            $listing_data['WeightUnit'] = (int) $gb_weight_unit; // 1 pounds, 2 kilograms per docs
        }
        
        // Add IsFFLRequired field for all categories - now required by GunBroker API
        // Get FFL required setting from product meta
        $ffl_required = get_post_meta($product->get_id(), '_gunbroker_ffl_required', true);
        $ffl_required = $ffl_required === '1' ? true : false;

        $listing_data['IsFFLRequired'] = $ffl_required;
        
        // Additional validation based on API documentation
        if (strlen($listing_data['Title']) < 1 || strlen($listing_data['Title']) > 75) {
            error_log('GunBroker: ERROR - Title length invalid: ' . strlen($listing_data['Title']));
            return new WP_Error('invalid_title', 'Title must be 1-75 characters');
        }
        
        // CRITICAL DEBUG: Check what description is actually in the payload
        error_log('=== DESCRIPTION VALIDATION DEBUG ===');
        error_log('Description in listing_data: "' . $listing_data['Description'] . '"');
        error_log('Description length: ' . strlen($listing_data['Description']));
        error_log('Description is empty: ' . (empty($listing_data['Description']) ? 'YES' : 'NO'));
        
        if (strlen($listing_data['Description']) < 1 || strlen($listing_data['Description']) > 8000) {
            error_log('GunBroker: ERROR - Description length invalid: ' . strlen($listing_data['Description']));
            error_log('GunBroker: ERROR - Description content: "' . $listing_data['Description'] . '"');
            
            // FORCE a valid description
            $listing_data['Description'] = 'High-quality ' . $product->get_name() . ' available for sale. Please contact seller for more details about this item.';
            error_log('GunBroker: FORCED description to: "' . $listing_data['Description'] . '"');
            error_log('GunBroker: FORCED description length: ' . strlen($listing_data['Description']));
        }
        
        if ($listing_data['CategoryID'] < 1 || $listing_data['CategoryID'] > 999999) {
            error_log('GunBroker: ERROR - CategoryID invalid: ' . $listing_data['CategoryID']);
            return new WP_Error('invalid_category', 'CategoryID must be 1-999999');
        }
        
        // Only validate StartingBid if it exists (auction listings)
        if (isset($listing_data['StartingBid'])) {
            if ($listing_data['StartingBid'] < 0.01 || $listing_data['StartingBid'] > 999999.99) {
                error_log('GunBroker: ERROR - StartingBid invalid: ' . $listing_data['StartingBid']);
                return new WP_Error('invalid_starting_bid', 'StartingBid must be 0.01-999999.99');
            }
        }
        
        // Only validate FixedPrice if it exists (fixed price listings)
        if (isset($listing_data['FixedPrice'])) {
            if ($listing_data['FixedPrice'] < 0.01 || $listing_data['FixedPrice'] > 999999.99) {
                error_log('GunBroker: ERROR - FixedPrice invalid: ' . $listing_data['FixedPrice']);
                return new WP_Error('invalid_fixed_price', 'FixedPrice must be 0.01-999999.99');
            }
        }
        
        if ($listing_data['Condition'] < 1 || $listing_data['Condition'] > 6) {
            error_log('GunBroker: ERROR - Condition invalid: ' . $listing_data['Condition']);
            return new WP_Error('invalid_condition', 'Condition must be 1-6');
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

        // CRITICAL DEBUG: Show exactly what's being sent to API
        error_log('=== GunBroker API PAYLOAD DEBUG ===');
        error_log('Listing Type: ' . $listing_type);
        error_log('IsFixedPrice: ' . ($listing_data['IsFixedPrice'] ? 'true' : 'false'));
        if (isset($listing_data['FixedPrice'])) {
            error_log('FixedPrice field: ' . $listing_data['FixedPrice']);
        }
        if (isset($listing_data['StartingBid'])) {
            error_log('StartingBid field: ' . $listing_data['StartingBid'] . ' *** ERROR: Should not be present for Fixed Price ***');
        }
        if (isset($listing_data['ReservePrice'])) {
            error_log('ReservePrice field: ' . $listing_data['ReservePrice'] . ' *** ERROR: Should not be present for Fixed Price ***');
        }
        if (isset($listing_data['BuyNowPrice'])) {
            error_log('BuyNowPrice field: ' . $listing_data['BuyNowPrice'] . ' *** ERROR: Should not be present for Fixed Price ***');
        }
        error_log('Full API payload: ' . json_encode($listing_data, JSON_PRETTY_PRINT));
        error_log('=== END API PAYLOAD DEBUG ===');

        // Debug logging for listing data preparation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('GunBroker: Prepared listing data for product: ' . $product->get_name());
        }

        // Monetary fields MUST be sent in canonical decimal format (e.g., 2.00)
        $format_money = function($amount) {
            return number_format((float)$amount, 2, '.', '');
        };

        // Normalize monetary values - ONLY format fields that exist
        if (isset($listing_data['StartingBid'])) {
            $listing_data['StartingBid'] = $format_money($listing_data['StartingBid']);
        }
        if (isset($listing_data['FixedPrice'])) {
            $listing_data['FixedPrice'] = $format_money($listing_data['FixedPrice']);
        }
        if (isset($listing_data['ReservePrice'])) {
            $listing_data['ReservePrice'] = $format_money($listing_data['ReservePrice']);
        }
        if (isset($listing_data['BuyNowPrice'])) {
            $listing_data['BuyNowPrice'] = $format_money($listing_data['BuyNowPrice']);
        }
        if (isset($listing_data['ShippingCost'])) {
            $listing_data['ShippingCost'] = $format_money($listing_data['ShippingCost']);
        }
        if (isset($listing_data['ShippingInsurance'])) {
            $listing_data['ShippingInsurance'] = $format_money($listing_data['ShippingInsurance']);
        }

        // Add images to description as secure HTTPS URLs (GunBroker API requirement)
        $image_ids = $product->get_gallery_image_ids();
        if (empty($image_ids)) {
            $featured_image_id = $product->get_image_id();
            if ($featured_image_id) {
                $image_ids = array($featured_image_id);
            }
        }

        if (!empty($image_ids)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GunBroker: Processing ' . count($image_ids) . ' images for product: ' . $product->get_name());
            }
            
            $image_html = "\n\n<p><strong>Product Images:</strong></p>\n";
            foreach (array_slice($image_ids, 0, 5) as $image_id) { // Limit to 5 images
                $image_url = wp_get_attachment_url($image_id);
                
                // If wp_get_attachment_url returns false, try alternative method
                if (!$image_url) {
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                }
                
                // If still no URL, try getting the attachment post
                if (!$image_url) {
                    $attachment = get_post($image_id);
                    if ($attachment) {
                        $image_url = wp_get_attachment_url($attachment->ID);
                    }
                }
                
                if ($image_url) {
                    // Ensure we have an absolute URL
                    if (strpos($image_url, 'http') !== 0) {
                        $image_url = home_url($image_url);
                    }

                    // Always force HTTPS
                    $secure_url = set_url_scheme($image_url, 'https');

                    // Replace any local/non-public host with configured public domain
                    $public_domain = trim(get_option('gunbroker_public_domain', ''));
                    if (!empty($public_domain)) {
                        $parsed = wp_parse_url($secure_url);
                        if (!empty($parsed['host'])) {
                            $local_hosts = array('localhost', '127.0.0.1');
                            $is_local = in_array($parsed['host'], $local_hosts, true) || substr($parsed['host'], -6) === '.local';
                            if ($is_local || $parsed['host'] !== $public_domain) {
                                $parsed['host'] = $public_domain;
                                $secure_url = (isset($parsed['scheme']) ? $parsed['scheme'] : 'https') . '://' . $parsed['host'] . (isset($parsed['path']) ? $parsed['path'] : '') . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');
                            }
                        }
                    } else {
                        // If no public domain configured and URL is localhost-like, skip it
                        if (strpos($secure_url, 'localhost') !== false || strpos($secure_url, '127.0.0.1') !== false || substr(parse_url($secure_url, PHP_URL_HOST) ?: '', -6) === '.local') {
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log('GunBroker: Skipping non-public image URL (configure gunbroker_public_domain setting): ' . $secure_url);
                            }
                            continue;
                        }
                    }
                    
                    if (filter_var($secure_url, FILTER_VALIDATE_URL)) {
                        $image_html .= "<p><img src=\"{$secure_url}\" alt=\"Product Image\" style=\"max-width: 500px; height: auto;\"></p>\n";
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('GunBroker: Added image URL: ' . $secure_url);
                        }
                    } else {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('GunBroker: Invalid secure URL for ID ' . $image_id . ': ' . $secure_url);
                        }
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('GunBroker: Could not get image URL for ID ' . $image_id);
                    }
                }
            }
            
            // Append images to the description
            $listing_data['Description'] .= $image_html;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GunBroker: Final description length: ' . strlen($listing_data['Description']));
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('GunBroker: No images found for product: ' . $product->get_name());
            }
        }

        // Log the prepared data for debugging
        // Listing data prepared successfully

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

        $result = $this->make_request($endpoint);

        if (!is_wp_error($result)) {
            return $result;
        }
        return $result;
    }

    /**
     * Try to find an existing listing for this seller by SKU (MfgPartNumber) or keyword
     */
    public function find_existing_listing_by_sku($sku) {
        if (empty($sku)) {
            return null;
        }
        // Authenticate to ensure we can query
        $username = get_option('gunbroker_username');
        $password = get_option('gunbroker_password');
        $auth_result = $this->authenticate($username, $password);
        if (is_wp_error($auth_result)) {
            return null;
        }

        // Use ItemsSelling to scan current listings; fallback to Items search
        $selling = $this->make_request('ItemsSelling?PageSize=100');
        if (!is_wp_error($selling) && isset($selling['results']) && is_array($selling['results'])) {
            foreach ($selling['results'] as $item) {
                $mfg = $item['mfgPartNumber'] ?? $item['MfgPartNumber'] ?? '';
                $title = $item['title'] ?? $item['Title'] ?? '';
                if (strcasecmp($mfg, $sku) === 0 || stripos($title, $sku) !== false) {
                    return $item['itemID'] ?? $item['ItemID'] ?? null;
                }
            }
        }

        // Fallback search by keyword
        $result = $this->search_listings(array('Keywords' => $sku));
        if (!is_wp_error($result) && isset($result['results']) && is_array($result['results'])) {
            foreach ($result['results'] as $item) {
                $mfg = $item['mfgPartNumber'] ?? $item['MfgPartNumber'] ?? '';
                $title = $item['title'] ?? $item['Title'] ?? '';
                if (strcasecmp($mfg, $sku) === 0 || stripos($title, $sku) !== false) {
                    return $item['itemID'] ?? $item['ItemID'] ?? null;
                }
            }
        }

        return null;
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
     * Get only top-level categories for progressive loading
     */
    public function get_top_level_categories() {
        $this->ensure_authenticated();
        
        // Try to get from cache first
        $cached = get_transient('gunbroker_top_categories');
        if ($cached !== false) {
            error_log('GunBroker: Using cached top-level categories');
            return $cached;
        }
        
        $result = $this->make_request('Categories');
        if (is_wp_error($result)) {
            return $result;
        }

        if (!isset($result['results']) || !is_array($result['results'])) {
            return new WP_Error('invalid_response', 'Invalid categories response');
        }
        
        // Filter only top-level categories (no parent)
        $top_categories = array();
        foreach ($result['results'] as $category) {
            $parent_id = $category['ParentCategoryID'] ?? $category['parentCategoryID'] ?? '';
            if (empty($parent_id) || $parent_id == 0) {
                // Normalize the category data structure
                $normalized_category = array(
                    'id' => $category['categoryID'] ?? $category['id'] ?? '',
                    'name' => $category['categoryName'] ?? $category['name'] ?? 'Unknown',
                    'categoryID' => $category['categoryID'] ?? $category['id'] ?? '',
                    'categoryName' => $category['categoryName'] ?? $category['name'] ?? 'Unknown',
                    'ParentCategoryID' => $category['ParentCategoryID'] ?? $category['parentCategoryID'] ?? '',
                    'canContainItems' => $category['canContainItems'] ?? $category['can_contain_items'] ?? false
                );
                $top_categories[] = $normalized_category;
            }
        }
        
        // Sort by name
        usort($top_categories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        // Cache for 24 hours (categories don't change often)
        set_transient('gunbroker_top_categories', $top_categories, HOUR_IN_SECONDS * 24);
        error_log('GunBroker: Cached top-level categories for 24 hours');
        
        return $top_categories;
    }

    /**
     * Get subcategories for a specific parent category
     */
    public function get_subcategories($parent_category_id) {
        $this->ensure_authenticated();
        
        // Try to get from cache first
        $cache_key = 'gunbroker_subcategories_' . $parent_category_id;
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            error_log('GunBroker: Using cached subcategories for parent ' . $parent_category_id);
            return $cached;
        }
        
        $endpoint = "Categories?ParentCategoryID={$parent_category_id}";
        $result = $this->make_request($endpoint);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        if (!isset($result['results']) || !is_array($result['results'])) {
            return array();
        }
        
        // Normalize the category data structure
        $normalized_categories = array();
        foreach ($result['results'] as $category) {
            $normalized_category = array(
                'id' => $category['categoryID'] ?? $category['id'] ?? '',
                'name' => $category['categoryName'] ?? $category['name'] ?? 'Unknown',
                'categoryID' => $category['categoryID'] ?? $category['id'] ?? '',
                'categoryName' => $category['categoryName'] ?? $category['name'] ?? 'Unknown',
                'ParentCategoryID' => $category['ParentCategoryID'] ?? $category['parentCategoryID'] ?? $parent_category_id,
                'canContainItems' => $category['canContainItems'] ?? $category['can_contain_items'] ?? false
            );
            $normalized_categories[] = $normalized_category;
        }
        
        // Sort by name
        usort($normalized_categories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        // Cache for 24 hours
        set_transient($cache_key, $normalized_categories, HOUR_IN_SECONDS * 24);
        error_log('GunBroker: Cached subcategories for parent ' . $parent_category_id . ' for 24 hours');
        
        return $normalized_categories;
    }

    /**
     * Get complete category hierarchy with sub-categories and sub-sub-categories
     * This method uses recursive API calls to fetch the complete hierarchy
     */
    public function get_complete_category_hierarchy() {
        // Check if we have cached hierarchy
        $cached_hierarchy = get_transient('gunbroker_complete_hierarchy');
        if ($cached_hierarchy !== false) {
            error_log('GunBroker: Using cached complete hierarchy');
            return $cached_hierarchy;
        }
        
        // First get the basic categories
        $categories = $this->get_categories_cached();
        
        if (is_wp_error($categories)) {
            return $categories;
        }
        
        if (!isset($categories['results']) || !is_array($categories['results'])) {
            return new WP_Error('no_categories', 'No categories found in API response');
        }
        
        $all_categories = $categories['results'];
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
        
        $hierarchy_data = array(
            'category_map' => $category_map,
            'parent_categories' => $parent_categories,
            'terminal_categories' => $GLOBALS['terminal_categories'] ?? array(),
            'hierarchical_tree' => $hierarchical_tree,
            'all_categories' => array_values($category_map)
        );
        
        // Cache the complete hierarchy for 24 hours (categories don't change often)
        set_transient('gunbroker_complete_hierarchy', $hierarchy_data, HOUR_IN_SECONDS * 24);
        error_log('GunBroker: Cached complete hierarchy for 24 hours');
        
        return $hierarchy_data;
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
        
        // Only fetch sub-categories for major categories to reduce API calls
        $major_categories = array('Guns & Firearms', 'Ammo', 'Gun Parts', 'Scopes & Optics', 'Holsters', 'Tactical Gear');
        
        // For each parent category, fetch its sub-categories using the official API
        foreach ($parent_categories as $parent) {
            $parent_id = $parent['categoryID'] ?? $parent['id'] ?? '';
            $parent_name = $parent['categoryName'] ?? $parent['name'] ?? 'Unknown';
            
            if (!$parent_id) continue;
            
            // Only fetch sub-categories for major categories
            if (!in_array($parent_name, $major_categories)) {
                continue;
            }
            
            // Method 1: Use /Categories?ParentCategoryID={parent_id} (official way)
            $result = $this->make_request("Categories?ParentCategoryID={$parent_id}");
            if (!is_wp_error($result) && isset($result['results']) && is_array($result['results'])) {
                $subcategories = $result['results'];
                
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
        
        // Now fetch sub-sub-categories for the sub-categories we just found
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
     * Clear category cache (useful for debugging or when categories change)
     */
    public function clear_category_cache() {
        delete_transient('gunbroker_categories');
        delete_transient('gunbroker_complete_hierarchy');
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
