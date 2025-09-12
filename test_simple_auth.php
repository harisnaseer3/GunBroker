<?php
// Simple authentication test
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Simple Authentication Test</h1>\n";

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

// Test the API class
$api = new GunBroker_API();

// Test authentication
echo "<h2>Authentication Test</h2>\n";
$auth_result = $api->authenticate(get_option('gunbroker_username'), get_option('gunbroker_password'));

if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
    echo "<p><strong>Response:</strong></p>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars(json_encode($auth_result, JSON_PRETTY_PRINT));
    echo "</pre>\n";
}

echo "<h2>Next Steps:</h2>\n";
echo "<p>If authentication works now, try the debug script again:</p>\n";
echo "<p><a href='debug_gunbroker_request.php'>Run Debug Script</a></p>\n";
?>
