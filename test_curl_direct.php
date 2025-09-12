<?php
// Test direct cURL to GunBroker API
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Direct cURL Test to GunBroker</h1>\n";

// Get credentials
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');
$is_sandbox = get_option('gunbroker_sandbox_mode', true);

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ GunBroker credentials not configured</p>\n";
    exit;
}

$base_url = $is_sandbox ? 'https://api.sandbox.gunbroker.com/v1/' : 'https://api.gunbroker.com/v1/';

echo "<h2>Configuration:</h2>\n";
echo "<p><strong>Base URL:</strong> $base_url</p>\n";
echo "<p><strong>Sandbox Mode:</strong> " . ($is_sandbox ? 'Yes' : 'No') . "</p>\n";

// Step 1: Authenticate
echo "<h2>Step 1: Authentication</h2>\n";

$auth_url = $base_url . 'Users/AccessToken';
$auth_data = json_encode(array(
    'Username' => $username,
    'Password' => $password
));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $auth_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-DevKey: ' . $dev_key,
    'Content-Type: application/json',
    'User-Agent: WordPress-GunBroker-Integration/1.0.1'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$auth_response = curl_exec($ch);
$auth_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$auth_error = curl_error($ch);
curl_close($ch);

if ($auth_error) {
    echo "<p style='color: red;'>❌ Authentication cURL Error: $auth_error</p>\n";
    exit;
}

echo "<p><strong>Auth Response Code:</strong> $auth_http_code</p>\n";
echo "<p><strong>Auth Response:</strong></p>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($auth_response);
echo "</pre>\n";

if ($auth_http_code !== 200) {
    echo "<p style='color: red;'>❌ Authentication failed</p>\n";
    exit;
}

$auth_data = json_decode($auth_response, true);
if (!isset($auth_data['accessToken'])) {
    echo "<p style='color: red;'>❌ No access token in response</p>\n";
    exit;
}

$access_token = $auth_data['accessToken'];
echo "<p style='color: green;'>✅ Authentication successful</p>\n";
echo "<p><strong>Access Token:</strong> " . substr($access_token, 0, 20) . "...</p>\n";

// Step 2: Create listing
echo "<h2>Step 2: Create Listing</h2>\n";

// Get test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>No products found</p>\n";
    exit;
}

$product = $products[0];

// Prepare listing data (simplified)
$listing_data = array(
    'Title' => 'Test Item via cURL',
    'Description' => 'This is a test item created via direct cURL',
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

$listing_json = json_encode($listing_data);
echo "<p><strong>Listing Data Length:</strong> " . strlen($listing_json) . " characters</p>\n";
echo "<p><strong>Listing Data:</strong></p>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
echo htmlspecialchars($listing_json);
echo "</pre>\n";

// Make the listing request
$listing_url = $base_url . 'Items';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $listing_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $listing_json);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-DevKey: ' . $dev_key,
    'X-AccessToken: ' . $access_token,
    'Content-Type: application/json',
    'User-Agent: WordPress-GunBroker-Integration/1.0.1'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

// Enable verbose output for debugging
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$listing_response = curl_exec($ch);
$listing_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$listing_error = curl_error($ch);

// Get verbose output
rewind($verbose);
$verbose_output = stream_get_contents($verbose);
fclose($verbose);

curl_close($ch);

echo "<h3>cURL Verbose Output:</h3>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;'>";
echo htmlspecialchars($verbose_output);
echo "</pre>\n";

if ($listing_error) {
    echo "<p style='color: red;'>❌ Listing cURL Error: $listing_error</p>\n";
} else {
    echo "<p><strong>Listing Response Code:</strong> $listing_http_code</p>\n";
    echo "<p><strong>Listing Response:</strong></p>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($listing_response);
    echo "</pre>\n";
    
    if ($listing_http_code === 200) {
        echo "<p style='color: green;'>✅ Listing created successfully!</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Listing failed with HTTP $listing_http_code</p>\n";
    }
}

echo "<h2>Conclusion:</h2>\n";
echo "<p>This test bypasses WordPress HTTP functions entirely and uses cURL directly.</p>\n";
echo "<p>If this works, the issue is with WordPress HTTP functions. If it fails, the issue is with GunBroker API or our data format.</p>\n";
?>
