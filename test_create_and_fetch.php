<?php
/**
 * Test creating a listing and then fetching it
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-sync.php');

echo "<h1>GunBroker Create & Fetch Test</h1>\n";

// Check configuration
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ Plugin not configured</p>\n";
    exit;
}

$api = new GunBroker_API();
$sync = new GunBroker_Sync();

// Test authentication
echo "<h2>1. Authentication Test</h2>\n";
$auth_result = $api->authenticate($username, $password);

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
}

// Get a test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>❌ No products found. Please create a WooCommerce product first.</p>\n";
    exit;
}

$product = $products[0];
echo "<p>✅ Using product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</p>\n";

// Enable GunBroker sync for this product
update_post_meta($product->get_id(), '_gunbroker_enabled', 'yes');
echo "<p>✅ Enabled GunBroker sync for product</p>\n";

// Try to sync the product
echo "<h2>2. Creating Listing</h2>\n";
$sync_result = $sync->sync_single_product($product->get_id());

if (is_wp_error($sync_result)) {
    echo "<p style='color: red;'>❌ Sync failed: " . $sync_result->get_error_message() . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ Product synced successfully</p>\n";
}

// Wait a moment for the listing to be processed
echo "<p>Waiting 3 seconds for listing to be processed...</p>\n";
sleep(3);

// Now try to fetch user listings
echo "<h2>3. Fetching User Listings</h2>\n";
$user_listings = $api->get_user_listings();

if (is_wp_error($user_listings)) {
    echo "<p style='color: red;'>❌ Failed to fetch listings: " . $user_listings->get_error_message() . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ Successfully fetched listings</p>\n";
    echo "<h3>Raw Response:</h3>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars(json_encode($user_listings, JSON_PRETTY_PRINT));
    echo "</pre>\n";
    
    // Check for listings in different possible keys
    $possible_keys = array('results', 'Items', 'data', 'listings', 'items');
    $found_listings = false;
    
    foreach ($possible_keys as $key) {
        if (isset($user_listings[$key]) && is_array($user_listings[$key]) && count($user_listings[$key]) > 0) {
            echo "<p style='color: green;'>✅ Found listings in key: '$key' (count: " . count($user_listings[$key]) . ")</p>\n";
            $found_listings = true;
            break;
        }
    }
    
    if (!$found_listings) {
        echo "<p style='color: orange;'>⚠️ No listings found in any expected key</p>\n";
        echo "<p>Available keys in response: " . implode(', ', array_keys($user_listings)) . "</p>\n";
    }
}

// Test the admin AJAX function
echo "<h2>4. Testing Admin AJAX Function</h2>\n";

// Simulate the AJAX request
$_POST['listing_type'] = 'user';
$_POST['nonce'] = wp_create_nonce('gunbroker_ajax_nonce');

// Capture the output
ob_start();
$admin = new GunBroker_Admin();
$admin->fetch_gunbroker_listings_ajax();
$ajax_output = ob_get_clean();

echo "<h3>AJAX Response:</h3>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($ajax_output);
echo "</pre>\n";
?>
