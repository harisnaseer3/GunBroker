<?php
// Simple listing test
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Simple Listing Test</h1>\n";

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

// Test creating a listing with minimal data
echo "<h3>2. Create Listing with Minimal Data</h3>\n";

$minimal_listing_data = array(
    'Title' => 'Test Item Simple',
    'Description' => 'This is a simple test item',
    'CategoryID' => 3022,
    'StartingBid' => '10.00',
    'Quantity' => 1,
    'ListingDuration' => 7,
    'PaymentMethods' => array('Check', 'MoneyOrder'),
    'ShippingMethods' => array('StandardShipping'),
    'InspectionPeriod' => 'ThreeDays',
    'ReturnsAccepted' => true,
    'Condition' => 1,
    'CountryCode' => 'US',
    'State' => 'TX',
    'City' => 'Austin',
    'ZipCode' => '78701',
    'MinBidIncrement' => 0.50,
    'ShippingCost' => 0.00,
    'ShippingInsurance' => 0.00,
    'ShippingTerms' => 'Buyer pays shipping',
    'SellerContactEmail' => get_option('admin_email'),
    'SellerContactPhone' => '555-123-4567',
    'IsFixedPrice' => false,
    'IsFeatured' => false,
    'IsBold' => false,
    'IsHighlight' => false,
    'IsReservePrice' => false
);

echo "<p><strong>Minimal Listing Data:</strong></p>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars(json_encode($minimal_listing_data, JSON_PRETTY_PRINT));
echo "</pre>\n";

// Test the API call
echo "<h3>3. API Call Test</h3>\n";
$result = $api->make_request('Items', 'POST', $minimal_listing_data);

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

echo "<h3>4. Next Steps</h3>\n";
echo "<p>If this works, the issue was with the complex listing data. If it fails, the issue is with the API call itself.</p>\n";
?>
