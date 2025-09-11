<?php
/**
 * Debug script for GunBroker listing issues
 * Run this from your WordPress root directory
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

echo "<h1>GunBroker Listing Debug</h1>\n";

// Check if plugin is configured
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');
$sandbox = get_option('gunbroker_sandbox_mode', true);

echo "<h2>1. Configuration Check</h2>\n";
echo "Dev Key: " . (empty($dev_key) ? "❌ Missing" : "✅ Set (" . substr($dev_key, 0, 8) . "...)") . "<br>\n";
echo "Username: " . (empty($username) ? "❌ Missing" : "✅ Set") . "<br>\n";
echo "Password: " . (empty($password) ? "❌ Missing" : "✅ Set") . "<br>\n";
echo "Sandbox Mode: " . ($sandbox ? "✅ Enabled" : "❌ Disabled") . "<br>\n";

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ Plugin not properly configured. Please configure in WordPress admin first.</p>\n";
    exit;
}

// Test API connection
echo "<h2>2. API Connection Test</h2>\n";
$api = new GunBroker_API();

// Test authentication
echo "Testing authentication...<br>\n";
$auth_result = $api->authenticate($username, $password);

if (is_wp_error($auth_result)) {
    echo "❌ Authentication failed: " . $auth_result->get_error_message() . "<br>\n";
    exit;
} else {
    echo "✅ Authentication successful<br>\n";
}

// Test a simple API call
echo "Testing API call to Users/AccountInfo...<br>\n";
$account_info = $api->make_request('Users/AccountInfo');

if (is_wp_error($account_info)) {
    echo "❌ API call failed: " . $account_info->get_error_message() . "<br>\n";
} else {
    echo "✅ API call successful<br>\n";
    echo "<pre>" . print_r($account_info, true) . "</pre>\n";
}

// Test with a sample product
echo "<h2>3. Sample Product Listing Data</h2>\n";

// Get a sample product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "❌ No products found. Please create a WooCommerce product first.<br>\n";
    exit;
}

$product = $products[0];
echo "Using product: " . $product->get_name() . " (ID: " . $product->get_id() . ")<br>\n";

// Prepare listing data
$listing_data = $api->prepare_listing_data($product);

echo "<h3>Current Listing Data Format:</h3>\n";
echo "<pre>" . json_encode($listing_data, JSON_PRETTY_PRINT) . "</pre>\n";

// Check for required fields based on GunBroker API docs
echo "<h3>Required Fields Check:</h3>\n";
$required_fields = array(
    'Title' => 'Item title',
    'Description' => 'Item description', 
    'CategoryID' => 'Category ID',
    'StartingBid' => 'Starting bid amount',
    'Condition' => 'Item condition',
    'ListingDuration' => 'Listing duration in days',
    'PaymentMethods' => 'Accepted payment methods',
    'CountryCode' => 'Two-character ISO country code'
);

$missing_fields = array();
foreach ($required_fields as $field => $description) {
    if (!isset($listing_data[$field]) || empty($listing_data[$field])) {
        $missing_fields[] = $field;
        echo "❌ Missing: $field ($description)<br>\n";
    } else {
        echo "✅ Present: $field<br>\n";
    }
}

// Check for issues with current format
echo "<h3>Format Issues Found:</h3>\n";
$issues = array();

// Check if we're using BuyNowPrice instead of StartingBid
if (isset($listing_data['BuyNowPrice']) && !isset($listing_data['StartingBid'])) {
    $issues[] = "Using 'BuyNowPrice' instead of 'StartingBid' - GunBroker API requires StartingBid";
}

// Check if Condition is string instead of number
if (isset($listing_data['Condition']) && is_string($listing_data['Condition'])) {
    $issues[] = "Condition should be a number (1=Factory New, 2=New Old Stock, 3=Used) not string";
}

// Check if CountryCode is missing
if (!isset($listing_data['CountryCode'])) {
    $issues[] = "Missing CountryCode - required field for GunBroker API";
}

// Check if PaymentMethods format is correct
if (isset($listing_data['PaymentMethods']) && is_array($listing_data['PaymentMethods'])) {
    $valid_payment_methods = array('Check', 'MoneyOrder', 'CreditCard', 'PayPal');
    foreach ($listing_data['PaymentMethods'] as $method) {
        if (!in_array($method, $valid_payment_methods)) {
            $issues[] = "Invalid payment method: $method";
        }
    }
}

if (empty($issues)) {
    echo "✅ No format issues found<br>\n";
} else {
    foreach ($issues as $issue) {
        echo "❌ $issue<br>\n";
    }
}

// Test the actual listing creation
echo "<h2>4. Test Listing Creation</h2>\n";
echo "Attempting to create listing...<br>\n";

$create_result = $api->create_listing($listing_data);

if (is_wp_error($create_result)) {
    echo "❌ Listing creation failed: " . $create_result->get_error_message() . "<br>\n";
    
    // Show additional error details if available
    $error_data = $create_result->get_error_data();
    if ($error_data) {
        echo "<h4>Error Details:</h4>\n";
        echo "<pre>" . print_r($error_data, true) . "</pre>\n";
    }
} else {
    echo "✅ Listing created successfully!<br>\n";
    echo "<pre>" . print_r($create_result, true) . "</pre>\n";
}

echo "<h2>5. Recommendations</h2>\n";
echo "<ul>\n";
echo "<li>Make sure you're using the correct sandbox credentials</li>\n";
echo "<li>Check that your GunBroker account has listing permissions</li>\n";
echo "<li>Verify the API endpoint is correct: " . ($sandbox ? 'https://api.sandbox.gunbroker.com/v1/Items' : 'https://api.gunbroker.com/v1/Items') . "</li>\n";
echo "<li>Ensure all required fields are present and properly formatted</li>\n";
echo "</ul>\n";
?>
