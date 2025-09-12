<?php
/**
 * Simple test script for GunBroker listing
 * Run this from your WordPress admin or via browser
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-sync.php');

echo "<h1>GunBroker Listing Test</h1>\n";

// Check configuration
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ Plugin not configured. Please configure in WordPress admin first.</p>\n";
    exit;
}

echo "<p>✅ Plugin is configured</p>\n";

// Test API connection
$api = new GunBroker_API();
$auth_result = $api->authenticate($username, $password);

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
}

echo "<p>✅ Authentication successful</p>\n";

// Get a test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>❌ No products found. Please create a WooCommerce product first.</p>\n";
    exit;
}

$product = $products[0];
echo "<p>✅ Using product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</p>\n";

// Prepare listing data
$listing_data = $api->prepare_listing_data($product);

echo "<h2>Listing Data:</h2>\n";
echo "<pre>" . json_encode($listing_data, JSON_PRETTY_PRINT) . "</pre>\n";

// Test listing creation
echo "<h2>Testing Listing Creation...</h2>\n";
$create_result = $api->create_listing($listing_data);

if (is_wp_error($create_result)) {
    echo "<p style='color: red;'>❌ Listing creation failed: " . $create_result->get_error_message() . "</p>\n";
    
    $error_data = $create_result->get_error_data();
    if ($error_data) {
        echo "<h3>Error Details:</h3>\n";
        echo "<pre>" . print_r($error_data, true) . "</pre>\n";
    }
} else {
    echo "<p style='color: green;'>✅ Listing created successfully!</p>\n";
    echo "<pre>" . print_r($create_result, true) . "</pre>\n";
}
?>
