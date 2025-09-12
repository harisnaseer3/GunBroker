<?php
// Debug exactly what's being sent to GunBroker
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>GunBroker Request Debug</h1>\n";

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

// Prepare listing data
echo "<h3>2. Prepare Listing Data</h3>\n";
$listing_data = $api->prepare_listing_data($product);

if (is_wp_error($listing_data)) {
    echo "<p style='color: red;'>❌ prepare_listing_data failed: " . $listing_data->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Listing data prepared successfully</p>\n";
}

// Test JSON encoding
echo "<h3>3. JSON Encoding Test</h3>\n";
$json_data = json_encode($listing_data);
if ($json_data === false) {
    echo "<p style='color: red;'>❌ JSON encoding failed: " . json_last_error_msg() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ JSON encoding successful</p>\n";
    echo "<p><strong>JSON Length:</strong> " . strlen($json_data) . " characters</p>\n";
}

// Show the exact JSON being sent
echo "<h3>4. Exact JSON Being Sent</h3>\n";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($json_data);
echo "</pre>\n";

// Test the actual API call with detailed logging
echo "<h3>5. GunBroker API Call Test</h3>\n";

// Enable WordPress debug logging
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

// Clear any existing debug log
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    file_put_contents($debug_log, '');
}

echo "<p>Attempting to create listing with detailed logging...</p>\n";
echo "<p>Check the debug log for detailed request information.</p>\n";

$result = $api->create_listing($listing_data);

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

// Show debug log contents
echo "<h3>6. Debug Log Contents</h3>\n";
if (file_exists($debug_log)) {
    $log_contents = file_get_contents($debug_log);
    if (!empty($log_contents)) {
        echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars($log_contents);
        echo "</pre>\n";
    } else {
        echo "<p>No debug log entries found.</p>\n";
    }
} else {
    echo "<p>Debug log file not found.</p>\n";
}

echo "<h3>7. Manual cURL Test</h3>\n";
echo "<p>Testing with manual cURL to verify the request format:</p>\n";

$dev_key = get_option('gunbroker_dev_key');
$access_token = get_option('gunbroker_access_token');
$base_url = get_option('gunbroker_sandbox_mode', true) ? 'https://api.sandbox.gunbroker.com/v1/' : 'https://api.gunbroker.com/v1/';

$curl_url = $base_url . 'Items';
$curl_headers = array(
    'X-DevKey: ' . $dev_key,
    'X-AccessToken: ' . $access_token,
    'Content-Type: application/json',
    'User-Agent: WordPress-GunBroker-Integration/1.0.1'
);

echo "<p><strong>cURL URL:</strong> $curl_url</p>\n";
echo "<p><strong>cURL Headers:</strong></p>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
foreach ($curl_headers as $header) {
    echo htmlspecialchars($header) . "\n";
}
echo "</pre>\n";
echo "<p><strong>cURL Body:</strong></p>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($json_data);
echo "</pre>\n";

// Test with cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $curl_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$curl_response = curl_exec($ch);
$curl_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "<p style='color: red;'>❌ cURL Error: $curl_error</p>\n";
} else {
    echo "<p style='color: green;'>✅ cURL request completed</p>\n";
    echo "<p><strong>HTTP Code:</strong> $curl_http_code</p>\n";
    echo "<p><strong>Response:</strong></p>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($curl_response);
    echo "</pre>\n";
}
?>
