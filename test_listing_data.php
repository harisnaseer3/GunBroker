<?php
/**
 * Test and validate GunBroker listing data format
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

echo "<h1>GunBroker Listing Data Validation Test</h1>\n";

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

// Get a test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>❌ No products found. Please create a WooCommerce product first.</p>\n";
    exit;
}

$product = $products[0];
echo "<p>✅ Using product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</p>\n";

// Prepare listing data
echo "<h2>2. Listing Data Preparation</h2>\n";
$listing_data = $api->prepare_listing_data($product);

echo "<h3>Prepared Listing Data:</h3>\n";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars(json_encode($listing_data, JSON_PRETTY_PRINT));
echo "</pre>\n";

// Validate required fields
echo "<h2>3. Field Validation</h2>\n";

$required_fields = array(
    'Title' => 'Item title (max 75 chars)',
    'Description' => 'Item description',
    'CategoryID' => 'Category ID (integer)',
    'StartingBid' => 'Starting bid amount (decimal)',
    'Condition' => 'Item condition (integer: 1=New, 2=New Old Stock, 3=Used)',
    'CountryCode' => 'Country code (2 chars)',
    'State' => 'State/Province',
    'City' => 'City',
    'ZipCode' => 'ZIP/Postal code'
);

$validation_errors = array();

foreach ($required_fields as $field => $description) {
    if (!isset($listing_data[$field]) || empty($listing_data[$field])) {
        $validation_errors[] = "Missing required field: $field ($description)";
        echo "<p style='color: red;'>❌ Missing: $field</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Present: $field = " . htmlspecialchars($listing_data[$field]) . "</p>\n";
    }
}

// Check field formats
echo "<h3>Field Format Validation:</h3>\n";

// Check Title length
if (strlen($listing_data['Title']) > 75) {
    $validation_errors[] = "Title too long: " . strlen($listing_data['Title']) . " chars (max 75)";
    echo "<p style='color: red;'>❌ Title too long: " . strlen($listing_data['Title']) . " chars</p>\n";
} else {
    echo "<p style='color: green;'>✅ Title length OK: " . strlen($listing_data['Title']) . " chars</p>\n";
}

// Check CategoryID
if (!is_numeric($listing_data['CategoryID']) || $listing_data['CategoryID'] <= 0) {
    $validation_errors[] = "Invalid CategoryID: " . $listing_data['CategoryID'];
    echo "<p style='color: red;'>❌ Invalid CategoryID: " . $listing_data['CategoryID'] . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ CategoryID OK: " . $listing_data['CategoryID'] . "</p>\n";
}

// Check StartingBid
if (!is_numeric($listing_data['StartingBid']) || $listing_data['StartingBid'] <= 0) {
    $validation_errors[] = "Invalid StartingBid: " . $listing_data['StartingBid'];
    echo "<p style='color: red;'>❌ Invalid StartingBid: " . $listing_data['StartingBid'] . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ StartingBid OK: $" . $listing_data['StartingBid'] . "</p>\n";
}

// Check Condition
$valid_conditions = array(1, 2, 3);
if (!in_array($listing_data['Condition'], $valid_conditions)) {
    $validation_errors[] = "Invalid Condition: " . $listing_data['Condition'] . " (must be 1, 2, or 3)";
    echo "<p style='color: red;'>❌ Invalid Condition: " . $listing_data['Condition'] . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ Condition OK: " . $listing_data['Condition'] . "</p>\n";
}

// Check CountryCode
if (strlen($listing_data['CountryCode']) !== 2) {
    $validation_errors[] = "Invalid CountryCode: " . $listing_data['CountryCode'] . " (must be 2 chars)";
    echo "<p style='color: red;'>❌ Invalid CountryCode: " . $listing_data['CountryCode'] . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ CountryCode OK: " . $listing_data['CountryCode'] . "</p>\n";
}

// Summary
echo "<h2>4. Validation Summary</h2>\n";

if (empty($validation_errors)) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ All validations passed! Listing data looks good.</p>\n";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Validation errors found:</p>\n";
    echo "<ul>\n";
    foreach ($validation_errors as $error) {
        echo "<li style='color: red;'>$error</li>\n";
    }
    echo "</ul>\n";
}

// Test the actual API call
echo "<h2>5. API Call Test</h2>\n";

if (empty($validation_errors)) {
    echo "<p>Attempting to create listing with validated data...</p>\n";
    
    $create_result = $api->create_listing($listing_data);
    
    if (is_wp_error($create_result)) {
        echo "<p style='color: red;'>❌ Listing creation failed: " . $create_result->get_error_message() . "</p>\n";
        
        $error_data = $create_result->get_error_data();
        if ($error_data && isset($error_data['body'])) {
            echo "<h3>Error Response Details:</h3>\n";
            echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
            echo htmlspecialchars(json_encode($error_data['body'], JSON_PRETTY_PRINT));
            echo "</pre>\n";
        }
    } else {
        echo "<p style='color: green;'>✅ Listing created successfully!</p>\n";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(json_encode($create_result, JSON_PRETTY_PRINT));
        echo "</pre>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Skipping API test due to validation errors</p>\n";
}

echo "<h2>6. Recommendations</h2>\n";
echo "<ul>\n";
echo "<li>Check the WordPress debug log for detailed error information</li>\n";
echo "<li>Verify your GunBroker account has listing permissions</li>\n";
echo "<li>Make sure the category ID is valid for your account</li>\n";
echo "<li>Check that all required fields are properly filled</li>\n";
echo "</ul>\n";
?>
