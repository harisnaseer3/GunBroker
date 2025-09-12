<?php
/**
 * Test network connectivity to GunBroker API
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>GunBroker Network Connectivity Test</h1>\n";

// Check configuration
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');
$sandbox = get_option('gunbroker_sandbox_mode', true);

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ Plugin not configured</p>\n";
    exit;
}

echo "<p>✅ Plugin is configured</p>\n";
echo "<p><strong>Sandbox Mode:</strong> " . ($sandbox ? 'Yes' : 'No') . "</p>\n";

// Determine the correct API URL
$api_url = $sandbox ? 'https://api.sandbox.gunbroker.com/v1/' : 'https://api.gunbroker.com/v1/';
echo "<p><strong>API URL:</strong> $api_url</p>\n";

// Test 1: Basic connectivity test
echo "<h2>1. Basic Connectivity Test</h2>\n";

$test_endpoints = array(
    'GunBrokerTime' => 'GunBroker Time (No Auth Required)',
    'Categories' => 'Categories (No Auth Required)',
    'Users/AccessToken' => 'Authentication Endpoint'
);

foreach ($test_endpoints as $endpoint => $description) {
    echo "<h3>Testing: $description</h3>\n";
    echo "<p><strong>URL:</strong> $api_url$endpoint</p>\n";
    
    $start_time = microtime(true);
    
    $response = wp_remote_get($api_url . $endpoint, array(
        'timeout' => 30,
        'sslverify' => true,
        'headers' => array(
            'User-Agent' => 'WordPress-GunBroker-Integration/1.0.1'
        )
    ));
    
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    if (is_wp_error($response)) {
        echo "<p style='color: red;'>❌ Error: " . $response->get_error_message() . "</p>\n";
        echo "<p><strong>Error Code:</strong> " . $response->get_error_code() . "</p>\n";
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        echo "<p style='color: green;'>✅ Success</p>\n";
        echo "<p><strong>Response Code:</strong> $response_code</p>\n";
        echo "<p><strong>Response Time:</strong> {$response_time}ms</p>\n";
        echo "<p><strong>Response Size:</strong> " . strlen($body) . " bytes</p>\n";
        
        if ($response_code >= 200 && $response_code < 300) {
            echo "<p style='color: green;'>✅ Valid response</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Unexpected response code</p>\n";
        }
        
        // Show first 200 characters of response
        echo "<details><summary>Response Preview</summary><pre style='background: #f5f5f5; padding: 10px; font-size: 12px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(substr($body, 0, 500));
        echo "</pre></details>\n";
    }
    
    echo "<hr>\n";
}

// Test 2: DNS Resolution Test
echo "<h2>2. DNS Resolution Test</h2>\n";

$hosts_to_test = array(
    'api.sandbox.gunbroker.com',
    'api.gunbroker.com',
    'www.sandbox.gunbroker.com',
    'www.gunbroker.com'
);

foreach ($hosts_to_test as $host) {
    echo "<h3>Testing DNS Resolution: $host</h3>\n";
    
    $start_time = microtime(true);
    $ip = gethostbyname($host);
    $end_time = microtime(true);
    $dns_time = round(($end_time - $start_time) * 1000, 2);
    
    if ($ip === $host) {
        echo "<p style='color: red;'>❌ DNS resolution failed</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Resolved to: $ip</p>\n";
        echo "<p><strong>DNS Resolution Time:</strong> {$dns_time}ms</p>\n";
    }
}

// Test 3: cURL Test
echo "<h2>3. cURL Test</h2>\n";

if (function_exists('curl_init')) {
    echo "<p>✅ cURL is available</p>\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . 'GunBrokerTime');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress-GunBroker-Integration/1.0.1');
    
    $start_time = microtime(true);
    $result = curl_exec($ch);
    $end_time = microtime(true);
    $curl_time = round(($end_time - $start_time) * 1000, 2);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    
    curl_close($ch);
    
    if ($error) {
        echo "<p style='color: red;'>❌ cURL Error: $error</p>\n";
    } else {
        echo "<p style='color: green;'>✅ cURL Success</p>\n";
        echo "<p><strong>HTTP Code:</strong> $http_code</p>\n";
        echo "<p><strong>Total Time:</strong> {$curl_time}ms</p>\n";
        echo "<p><strong>Connect Time:</strong> " . round($info['connect_time'] * 1000, 2) . "ms</p>\n";
        echo "<p><strong>Name Lookup Time:</strong> " . round($info['namelookup_time'] * 1000, 2) . "ms</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ cURL is not available</p>\n";
}

// Test 4: Server Configuration
echo "<h2>4. Server Configuration</h2>\n";

echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>\n";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>\n";
echo "<p><strong>OpenSSL Version:</strong> " . (extension_loaded('openssl') ? OPENSSL_VERSION_TEXT : 'Not available') . "</p>\n";
echo "<p><strong>cURL Version:</strong> " . (function_exists('curl_version') ? curl_version()['version'] : 'Not available') . "</p>\n";

// Check if allow_url_fopen is enabled
echo "<p><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled') . "</p>\n";

// Check if fsockopen is available
echo "<p><strong>fsockopen:</strong> " . (function_exists('fsockopen') ? 'Available' : 'Not available') . "</p>\n";

echo "<h2>5. Recommendations</h2>\n";
echo "<ul>\n";
echo "<li>If DNS resolution is failing, check your server's DNS settings</li>\n";
echo "<li>If cURL timeouts occur, try increasing the timeout values</li>\n";
echo "<li>If SSL verification fails, check your server's SSL configuration</li>\n";
echo "<li>Contact your hosting provider if network connectivity issues persist</li>\n";
echo "</ul>\n";
?>
