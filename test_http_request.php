<?php
// Test HTTP request directly
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>HTTP Request Test</h1>\n";

// Test data
$test_data = array(
    'Title' => 'Test Item',
    'Description' => 'This is a test item',
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
    'SellerContactEmail' => 'test@example.com',
    'SellerContactPhone' => '555-123-4567',
    'IsFixedPrice' => false,
    'IsFeatured' => false,
    'IsBold' => false,
    'IsHighlight' => false,
    'IsReservePrice' => false
);

echo "<h2>Test Data:</h2>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars(json_encode($test_data, JSON_PRETTY_PRINT));
echo "</pre>\n";

// Test JSON encoding
$json_data = json_encode($test_data);
if ($json_data === false) {
    echo "<p style='color: red;'>❌ JSON encoding failed: " . json_last_error_msg() . "</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ JSON encoding successful</p>\n";
echo "<p><strong>JSON Length:</strong> " . strlen($json_data) . " characters</p>\n";

// Test HTTP request to a test endpoint
echo "<h2>HTTP Request Test:</h2>\n";

$url = 'https://httpbin.org/post'; // Test endpoint that echoes back what we send
$args = array(
    'headers' => array(
        'Content-Type' => 'application/json',
        'User-Agent' => 'WordPress-GunBroker-Integration/1.0.1'
    ),
    'body' => $json_data,
    'timeout' => 30,
    'sslverify' => true
);

echo "<p>Testing POST request to: $url</p>\n";
echo "<p>Request body length: " . strlen($json_data) . " characters</p>\n";

$response = wp_remote_post($url, $args);

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ HTTP request failed: " . $response->get_error_message() . "</p>\n";
} else {
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    echo "<p style='color: green;'>✅ HTTP request successful</p>\n";
    echo "<p><strong>Response Code:</strong> $response_code</p>\n";
    
    if ($response_code === 200) {
        $response_data = json_decode($response_body, true);
        if (isset($response_data['json'])) {
            echo "<p style='color: green;'>✅ Data was received correctly by the server</p>\n";
            echo "<h3>Server received data:</h3>\n";
            echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
            echo htmlspecialchars(json_encode($response_data['json'], JSON_PRETTY_PRINT));
            echo "</pre>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Server response doesn't contain expected data</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Unexpected response code: $response_code</p>\n";
    }
}

echo "<h2>Conclusion:</h2>\n";
echo "<p>This test verifies that WordPress HTTP functions can properly send JSON data.</p>\n";
echo "<p>If this test passes, the issue is likely with the GunBroker API endpoint or authentication.</p>\n";
?>
