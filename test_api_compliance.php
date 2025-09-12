<?php
// Test API compliance with GunBroker documentation
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>GunBroker API Compliance Test</h1>\n";

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

// Get a test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>No products found. Please create a WooCommerce product first.</p>\n";
    exit;
}

$product = $products[0];
echo "<h2>Testing Product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</h2>\n";

// Check if product has a price
if (empty($product->get_price()) || $product->get_price() <= 0) {
    echo "<p style='color: red;'>❌ Product has no price! Please set a price for this product.</p>\n";
    echo "<p>Go to WooCommerce → Products → Edit this product and set a price (e.g., $100)</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ Product has a valid price: $" . $product->get_price() . "</p>\n";

// Test the API class
$api = new GunBroker_API();

// Test authentication first
echo "<h2>1. Authentication Test</h2>\n";
$dev_key = get_option('gunbroker_dev_key');
$username = get_option('gunbroker_username');
$password = get_option('gunbroker_password');

if (empty($dev_key) || empty($username) || empty($password)) {
    echo "<p style='color: red;'>❌ GunBroker credentials not configured</p>\n";
    exit;
}

$auth_result = $api->authenticate($username, $password);
if (is_wp_error($auth_result)) {
    echo "<p style='color: red;'>❌ Authentication failed: " . $auth_result->get_error_message() . "</p>\n";
    exit;
} else {
    echo "<p style='color: green;'>✅ Authentication successful</p>\n";
}

// Test listing data preparation
echo "<h2>2. Listing Data Preparation Test</h2>\n";
$listing_data = $api->prepare_listing_data($product);

if (is_wp_error($listing_data)) {
    echo "<p style='color: red;'>❌ Listing data preparation failed: " . $listing_data->get_error_message() . "</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ Listing data prepared successfully</p>\n";

// Validate against API requirements
echo "<h2>3. API Requirements Validation</h2>\n";

$requirements = array(
    'Title' => array('type' => 'string', 'min' => 1, 'max' => 75, 'required' => true),
    'Description' => array('type' => 'string', 'min' => 1, 'max' => 8000, 'required' => true),
    'CategoryID' => array('type' => 'integer', 'min' => 1, 'max' => 999999, 'required' => true),
    'StartingBid' => array('type' => 'decimal', 'min' => 0.01, 'max' => 999999.99, 'required' => true),
    'Quantity' => array('type' => 'integer', 'min' => 1, 'max' => 999999, 'required' => true),
    'ListingDuration' => array('type' => 'integer', 'min' => 1, 'max' => 99, 'required' => true),
    'PaymentMethods' => array('type' => 'array', 'min' => 1, 'max' => 10, 'required' => true),
    'ShippingMethods' => array('type' => 'array', 'min' => 1, 'max' => 10, 'required' => true),
    'InspectionPeriod' => array('type' => 'string', 'min' => 1, 'max' => 20, 'required' => true),
    'ReturnsAccepted' => array('type' => 'boolean', 'required' => true),
    'Condition' => array('type' => 'integer', 'min' => 1, 'max' => 10, 'required' => true),
    'CountryCode' => array('type' => 'string', 'min' => 2, 'max' => 2, 'required' => true),
    'State' => array('type' => 'string', 'min' => 1, 'max' => 50, 'required' => true),
    'City' => array('type' => 'string', 'min' => 1, 'max' => 50, 'required' => true),
    'ZipCode' => array('type' => 'string', 'min' => 1, 'max' => 10, 'required' => true)
);

$validation_errors = array();

foreach ($requirements as $field => $requirement) {
    if (!isset($listing_data[$field])) {
        if ($requirement['required']) {
            $validation_errors[] = "Missing required field: $field";
            echo "<p style='color: red;'>❌ Missing: $field</p>\n";
        }
        continue;
    }
    
    $value = $listing_data[$field];
    $valid = true;
    $error_msg = "";
    
    // Check type and range
    switch ($requirement['type']) {
        case 'string':
            if (!is_string($value)) {
                $valid = false;
                $error_msg = "Not a string";
            } else {
                $length = strlen($value);
                if ($length < $requirement['min'] || $length > $requirement['max']) {
                    $valid = false;
                    $error_msg = "Length $length not in range {$requirement['min']}-{$requirement['max']}";
                }
            }
            break;
            
        case 'integer':
            if (!is_int($value) && !is_numeric($value)) {
                $valid = false;
                $error_msg = "Not an integer";
            } else {
                $int_value = intval($value);
                if ($int_value < $requirement['min'] || $int_value > $requirement['max']) {
                    $valid = false;
                    $error_msg = "Value $int_value not in range {$requirement['min']}-{$requirement['max']}";
                }
            }
            break;
            
        case 'decimal':
            if (!is_numeric($value)) {
                $valid = false;
                $error_msg = "Not a decimal";
            } else {
                $float_value = floatval($value);
                if ($float_value < $requirement['min'] || $float_value > $requirement['max']) {
                    $valid = false;
                    $error_msg = "Value $float_value not in range {$requirement['min']}-{$requirement['max']}";
                }
            }
            break;
            
        case 'boolean':
            if (!is_bool($value)) {
                $valid = false;
                $error_msg = "Not a boolean";
            }
            break;
            
        case 'array':
            if (!is_array($value)) {
                $valid = false;
                $error_msg = "Not an array";
            } else {
                $count = count($value);
                if ($count < $requirement['min'] || $count > $requirement['max']) {
                    $valid = false;
                    $error_msg = "Array count $count not in range {$requirement['min']}-{$requirement['max']}";
                }
            }
            break;
    }
    
    if ($valid) {
        echo "<p style='color: green;'>✅ $field: " . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "</p>\n";
    } else {
        $validation_errors[] = "$field: $error_msg";
        echo "<p style='color: red;'>❌ $field: $error_msg (Value: " . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . ")</p>\n";
    }
}

// Summary
echo "<h2>4. Validation Summary</h2>\n";
if (empty($validation_errors)) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ All validations passed! Listing data is compliant with GunBroker API requirements.</p>\n";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Validation errors found:</p>\n";
    echo "<ul>\n";
    foreach ($validation_errors as $error) {
        echo "<li style='color: red;'>$error</li>\n";
    }
    echo "</ul>\n";
}

// Show the final listing data
echo "<h2>5. Final Listing Data</h2>\n";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars(json_encode($listing_data, JSON_PRETTY_PRINT));
echo "</pre>\n";

echo "<h2>6. Next Steps</h2>\n";
if (empty($validation_errors)) {
    echo "<p style='color: green;'>✅ Your listing data is ready! Try listing the product again.</p>\n";
} else {
    echo "<p style='color: red;'>❌ Fix the validation errors above before trying to list the product.</p>\n";
}
?>
