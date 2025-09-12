<?php
/**
 * Test different GunBroker API endpoints to find the right one for user listings
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

echo "<h1>GunBroker API Endpoints Test</h1>\n";

// Check configuration
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ Plugin not configured</p>\n";
    exit;
}

$api = new GunBroker_API();

// Test authentication
echo "<h2>1. Authentication Test</h2>\n";
$auth_result = $api->authenticate($username, $password);

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
}

// Test different endpoints that might return user listings
echo "<h2>2. Testing User Listing Endpoints</h2>\n";

$endpoints_to_test = array(
    'ItemsSelling' => 'ItemsSelling (Current)',
    'ItemsSelling?PageSize=5' => 'ItemsSelling with PageSize',
    'Items?IncludeSellers=' . urlencode($username) => 'Items with IncludeSellers',
    'Items?SellerName=' . urlencode($username) => 'Items with SellerName',
    'Items?PageSize=5&IncludeSellers=' . urlencode($username) => 'Items with IncludeSellers and PageSize'
);

foreach ($endpoints_to_test as $endpoint => $description) {
    echo "<h3>Testing: $description</h3>\n";
    echo "<p><strong>Endpoint:</strong> $endpoint</p>\n";
    
    $result = $api->make_request($endpoint);
    
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Error: " . $result->get_error_message() . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Success</p>\n";
        echo "<p><strong>Count:</strong> " . ($result['count'] ?? 'N/A') . "</p>\n";
        echo "<p><strong>Results:</strong> " . (isset($result['results']) ? count($result['results']) : 'N/A') . " items</p>\n";
        
        if (isset($result['results']) && count($result['results']) > 0) {
            echo "<p style='color: green;'>🎉 Found listings!</p>\n";
            echo "<h4>First Item:</h4>\n";
            echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px;'>";
            echo htmlspecialchars(json_encode($result['results'][0], JSON_PRETTY_PRINT));
            echo "</pre>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ No listings found</p>\n";
        }
        
        echo "<details><summary>Full Response</summary><pre style='background: #f5f5f5; padding: 10px; font-size: 11px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
        echo "</pre></details>\n";
    }
    
    echo "<hr>\n";
}

// Test creating a listing first
echo "<h2>3. Test Creating a Listing First</h2>\n";

// Get a test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>❌ No products found. Please create a WooCommerce product first.</p>\n";
} else {
    $product = $products[0];
    echo "<p>✅ Using product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</p>\n";
    
    // Enable GunBroker sync
    update_post_meta($product->get_id(), '_gunbroker_enabled', 'yes');
    echo "<p>✅ Enabled GunBroker sync for product</p>\n";
    
    // Try to create a listing
    echo "<p>Attempting to create listing...</p>\n";
    
    $sync = new GunBroker_Sync();
    $sync_result = $sync->sync_single_product($product->get_id());
    
    if (is_wp_error($sync_result)) {
        echo "<p style='color: red;'>❌ Listing creation failed: " . $sync_result->get_error_message() . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Listing created successfully!</p>\n";
        
        // Wait a moment
        echo "<p>Waiting 3 seconds for listing to be processed...</p>\n";
        sleep(3);
        
        // Now try to fetch it
        echo "<h3>Fetching the listing we just created:</h3>\n";
        $user_listings = $api->get_user_listings();
        
        if (is_wp_error($user_listings)) {
            echo "<p style='color: red;'>❌ Failed to fetch listings: " . $user_listings->get_error_message() . "</p>\n";
        } else {
            echo "<p style='color: green;'>✅ Successfully fetched listings</p>\n";
            echo "<p><strong>Count:</strong> " . ($user_listings['count'] ?? 'N/A') . "</p>\n";
            echo "<p><strong>Results:</strong> " . (isset($user_listings['results']) ? count($user_listings['results']) : 'N/A') . " items</p>\n";
            
            if (isset($user_listings['results']) && count($user_listings['results']) > 0) {
                echo "<p style='color: green;'>🎉 Found the listing we just created!</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠️ Still no listings found after creation</p>\n";
            }
        }
    }
}
?>
