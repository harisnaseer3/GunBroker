<?php
/**
 * Debug script to check GunBroker API response format
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

echo "<h1>GunBroker API Response Debug</h1>\n";

// Check configuration
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ Plugin not configured. Please configure in WordPress admin first.</p>\n";
    exit;
}

$api = new GunBroker_API();

// Test authentication
echo "<h2>1. Testing Authentication</h2>\n";
$auth_result = $api->authenticate($username, $password);

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
}

// Test different endpoints to see what they return
echo "<h2>2. Testing Different Endpoints</h2>\n";

$endpoints_to_test = array(
    'Users/AccountInfo' => 'Account Info',
    'ItemsSelling' => 'User Listings (ItemsSelling)',
    'Items?PageSize=5' => 'Public Items Search',
    'Categories' => 'Categories List'
);

foreach ($endpoints_to_test as $endpoint => $description) {
    echo "<h3>Testing: $description ($endpoint)</h3>\n";
    
    $result = $api->make_request($endpoint);
    
    if (is_wp_error($result)) {
        echo "<p style='color: red;'>❌ Error: " . $result->get_error_message() . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Success</p>\n";
        echo "<h4>Raw Response:</h4>\n";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
        echo "</pre>\n";
        
        // Check for common data keys
        echo "<h4>Data Structure Analysis:</h4>\n";
        echo "<ul>\n";
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                echo "<li><strong>$key</strong>: Array with " . count($value) . " items</li>\n";
            } else {
                echo "<li><strong>$key</strong>: " . gettype($value) . " = " . htmlspecialchars($value) . "</li>\n";
            }
        }
        echo "</ul>\n";
    }
    
    echo "<hr>\n";
}

// Test the specific user listings endpoint
echo "<h2>3. Testing User Listings Specifically</h2>\n";
$user_listings = $api->get_user_listings();

if (is_wp_error($user_listings)) {
    echo "<p style='color: red;'>❌ User listings failed: " . $user_listings->get_error_message() . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ User listings successful</p>\n";
    echo "<h4>User Listings Response:</h4>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars(json_encode($user_listings, JSON_PRETTY_PRINT));
    echo "</pre>\n";
    
    // Check if there are any listings
    $has_listings = false;
    $listings_count = 0;
    
    if (isset($user_listings['results']) && is_array($user_listings['results'])) {
        $has_listings = true;
        $listings_count = count($user_listings['results']);
    } elseif (isset($user_listings['Items']) && is_array($user_listings['Items'])) {
        $has_listings = true;
        $listings_count = count($user_listings['Items']);
    } elseif (isset($user_listings['data']) && is_array($user_listings['data'])) {
        $has_listings = true;
        $listings_count = count($user_listings['data']);
    }
    
    if ($has_listings) {
        echo "<p style='color: green;'>✅ Found $listings_count listings</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No listings found in response</p>\n";
        echo "<p>This might be normal if you haven't created any listings yet, or if the API response format is different than expected.</p>\n";
    }
}
?>
