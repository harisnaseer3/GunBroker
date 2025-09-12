<?php
// Final solution test - exclude IsFFLRequired for firearms category
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>🎯 Final Solution Test - Exclude IsFFLRequired for Firearms</h1>\n";

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

// Test the API class
$api = new GunBroker_API();

// Test authentication
echo "<h3>1. Authentication</h3>\n";
$auth_result = $api->authenticate(get_option('gunbroker_username'), get_option('gunbroker_password'));

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Auth failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
}

// Create test data for firearms category WITHOUT IsFFLRequired
echo "<h3>2. Test Firearms Category (3022) - NO IsFFLRequired</h3>\n";
$test_data = array(
    'Title' => 'Final Solution Test - Firearms No IsFFLRequired',
    'Description' => 'Testing firearms category 3022 WITHOUT IsFFLRequired field to avoid API rejection.',
    'CategoryID' => 3022, // Firearms category
    'StartingBid' => 199.99,
    'Quantity' => 1,
    'ListingDuration' => 7,
    'PaymentMethods' => array(
        'Check' => true,
        'MoneyOrder' => true,
        'CreditCard' => true
    ),
    'ShippingMethods' => array(
        'StandardShipping' => true,
        'UPSGround' => true
    ),
    'ReturnsAccepted' => true,
    'Condition' => 1, // Factory New
    'CountryCode' => 'US',
    'State' => 'AL',
    'City' => 'Birmingham',
    'PostalCode' => '35203',
    'MfgPartNumber' => 'FINAL-SOLUTION-SKU-123',
    'MinBidIncrement' => 5.0,
    'ShippingCost' => 15.0,
    'ShippingInsurance' => 0.0,
    'ShippingTerms' => 'Buyer pays shipping',
    'SellerContactEmail' => get_option('admin_email'),
    'SellerContactPhone' => '205-555-0123',
    'IsFixedPrice' => false,
    'IsFeatured' => false,
    'IsBold' => false,
    'IsHighlight' => false,
    'IsReservePrice' => false,
    'AutoRelist' => 1, // Do Not Relist
    // IsFFLRequired is NOT included for firearms category
    'WhoPaysForShipping' => 2, // Seller pays for shipping
    'WillShipInternational' => false,
    'ShippingClassesSupported' => array(
        'Ground' => true,
        'TwoDay' => true,
        'Overnight' => true
    )
);

echo "<p>✅ Test data created WITHOUT IsFFLRequired for firearms category</p>\n";
echo "<p><strong>CategoryID:</strong> " . $test_data['CategoryID'] . " (Firearms)</p>\n";
echo "<p><strong>IsFFLRequired field present:</strong> " . (isset($test_data['IsFFLRequired']) ? "❌ Yes (should not be)" : "✅ No (correct)") . "</p>\n";

// Test the API call
echo "<h3>3. API Call Test</h3>\n";
echo "<p>Testing firearms category WITHOUT IsFFLRequired field...</p>\n";

$result = $api->create_listing($test_data);

if (is_wp_error($result)) {
    echo "<p style='color: red;'>❌ API call failed: " . $result->get_error_message() . "</p>\n";
    
    $error_data = $result->get_error_data();
    if ($error_data && isset($error_data['body'])) {
        echo "<h4>Error Details:</h4>\n";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(json_encode($error_data['body'], JSON_PRETTY_PRINT));
        echo "</pre>\n";
    }
} else {
    echo "<p style='color: green; font-size: 20px; font-weight: bold;'>🎉 SUCCESS! Firearms without IsFFLRequired working!</p>\n";
    echo "<h4>Response:</h4>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
    echo "</pre>\n";
}

echo "<h3>4. Test Summary</h3>\n";
if (is_wp_error($result)) {
    echo "<div style='background: #ffebee; padding: 20px; border: 2px solid #f44336; border-radius: 8px;'>\n";
    echo "<h4 style='color: #d32f2f;'>❌ Test Failed</h4>\n";
    echo "<p>There's still an issue. Check the error details above.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: #e8f5e8; padding: 20px; border: 2px solid #4caf50; border-radius: 8px;'>\n";
    echo "<h4 style='color: #2e7d32;'>🎉 SUCCESS!</h4>\n";
    echo "<p><strong>Your GunBroker API integration is working perfectly!</strong></p>\n";
    echo "<p>✅ IsFFLRequired field excluded for firearms category</p>\n";
    echo "<p>✅ API call successful</p>\n";
    echo "<p><strong>You can now create listings on GunBroker!</strong></p>\n";
    echo "</div>\n";
}

echo "<h3>5. Final Implementation</h3>\n";
echo "<pre style='background: #f0f0f0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>\n";
echo "// Add IsFFLRequired field conditionally - API rejects it for firearms category 3022\n";
echo "// Exclude for firearms, include for other categories\n";
echo "if (\$category_id != 3022) {\n";
echo "    \$listing_data['IsFFLRequired'] = false; // Include for non-firearms categories\n";
echo "}\n";
echo "// Note: IsFFLRequired is excluded for firearms category 3022 due to API restriction\n";
echo "</pre>\n";

echo "<h3>6. Key Insights</h3>\n";
echo "<ul>\n";
echo "<li>✅ API rejects IsFFLRequired field for firearms category 3022</li>\n";
echo "<li>✅ Solution: Exclude IsFFLRequired field for firearms category</li>\n";
echo "<li>✅ Include IsFFLRequired field for other categories</li>\n";
echo "<li>✅ This handles the API's inconsistent behavior</li>\n";
echo "</ul>\n";

echo "<p><em>Final solution test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
