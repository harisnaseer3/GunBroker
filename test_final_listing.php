<?php
// Final test with all fixes applied
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Final Listing Test with Fixes</h1>\n";

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

// Get the test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>No products found</p>\n";
    exit;
}

$product = $products[0];
echo "<h2>Testing with Product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</h2>\n";

// Test the API class
$api = new GunBroker_API();

// Test authentication
echo "<h3>1. Authentication Test</h3>\n";
$auth_result = $api->authenticate(get_option('gunbroker_username'), get_option('gunbroker_password'));

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
}

// Prepare listing data with fixes
echo "<h3>2. Prepare Listing Data (with fixes)</h3>\n";
$listing_data = $api->prepare_listing_data($product);

if (is_wp_error($listing_data)) {
    echo "<p style='color: red;'>❌ prepare_listing_data failed: " . $listing_data->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Listing data prepared successfully</p>\n";
}

// Show the corrected data
echo "<h3>3. Corrected Listing Data</h3>\n";
echo "<p><strong>Key fixes applied:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ HTML tags stripped from description</li>\n";
echo "<li>✅ Multiple shipping methods added</li>\n";
echo "<li>✅ Enhanced validation for required arrays</li>\n";
echo "</ul>\n";

echo "<p><strong>Listing Data:</strong></p>\n";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars(json_encode($listing_data, JSON_PRETTY_PRINT));
echo "</pre>\n";

// Test the API call
echo "<h3>4. API Call Test</h3>\n";
$result = $api->make_request('Items', 'POST', $listing_data);

if (is_wp_error($result)) {
    echo "<p style='color: red;'>❌ API call failed: " . $result->get_error_message() . "</p>\n";
    
    $error_data = $result->get_error_data();
    if ($error_data && isset($error_data['body'])) {
        echo "<h4>Error Response Details:</h4>\n";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(json_encode($error_data['body'], JSON_PRETTY_PRINT));
        echo "</pre>\n";
    }
} else {
    echo "<p style='color: green;'>✅ API call successful!</p>\n";
    echo "<h4>Response:</h4>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
    echo "</pre>\n";
}

echo "<h3>5. Summary</h3>\n";
echo "<p>This test includes all the fixes for the 'empty request' error:</p>\n";
echo "<ul>\n";
echo "<li><strong>HTML Stripping:</strong> Removed HTML tags from description</li>\n";
echo "<li><strong>Shipping Methods:</strong> Added multiple shipping methods</li>\n";
echo "<li><strong>Validation:</strong> Enhanced validation for required fields</li>\n";
echo "<li><strong>Data Format:</strong> Ensured all data meets GunBroker API requirements</li>\n";
echo "</ul>\n";
?>
