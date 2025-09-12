<?php
// Test existing product listing data preparation
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Test Existing Product</h1>\n";

// Load the GunBroker plugin classes
require_once('wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php');

// Get the first product (your test rifle 1)
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>No products found</p>\n";
    exit;
}

$product = $products[0];
echo "<h2>Testing Product: " . $product->get_name() . " (ID: " . $product->get_id() . ")</h2>\n";

// Show product details
echo "<h3>Product Details:</h3>\n";
echo "<p><strong>Name:</strong> " . $product->get_name() . "</p>\n";
echo "<p><strong>Regular Price:</strong> " . ($product->get_regular_price() ?: 'Not set') . "</p>\n";
echo "<p><strong>Sale Price:</strong> " . ($product->get_price() ?: 'Not set') . "</p>\n";
echo "<p><strong>Description:</strong> " . ($product->get_description() ?: 'Not set') . "</p>\n";
echo "<p><strong>Short Description:</strong> " . ($product->get_short_description() ?: 'Not set') . "</p>\n";

// Test the API class
$api = new GunBroker_API();

echo "<h3>Testing prepare_listing_data method:</h3>\n";

try {
    $listing_data = $api->prepare_listing_data($product);
    
    if (is_wp_error($listing_data)) {
        echo "<p style='color: red;'>❌ prepare_listing_data returned an error:</p>\n";
        echo "<p style='color: red;'><strong>Error Code:</strong> " . $listing_data->get_error_code() . "</p>\n";
        echo "<p style='color: red;'><strong>Error Message:</strong> " . $listing_data->get_error_message() . "</p>\n";
        
        // This is likely the cause of the "empty request" error
        echo "<p style='color: red;'><strong>This is why you're getting 'empty request' error!</strong></p>\n";
        echo "<p>The sync process tries to send this error as listing data, which results in an empty request.</p>\n";
        
    } else {
        echo "<p style='color: green;'>✅ prepare_listing_data returned valid data</p>\n";
        echo "<h4>Listing Data:</h4>\n";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(json_encode($listing_data, JSON_PRETTY_PRINT));
        echo "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception occurred: " . $e->getMessage() . "</p>\n";
}

echo "<h3>Solution:</h3>\n";
echo "<p>If you see an error above, you need to fix your product data:</p>\n";
echo "<ol>\n";
echo "<li>Go to WooCommerce → Products</li>\n";
echo "<li>Edit your 'test rifle 1' product</li>\n";
echo "<li>Set a <strong>Regular Price</strong> (e.g., $100)</li>\n";
echo "<li>Add a <strong>Product Description</strong></li>\n";
echo "<li>Save the product</li>\n";
echo "<li>Try listing again</li>\n";
echo "</ol>\n";

echo "<p><strong>OR create a new product:</strong></p>\n";
echo "<ol>\n";
echo "<li>Go to WooCommerce → Products → Add New</li>\n";
echo "<li>Set a product name (e.g., 'Test Handgun 2')</li>\n";
echo "<li>Set a Regular Price (e.g., $100)</li>\n";
echo "<li>Add a Product Description</li>\n";
echo "<li>Publish the product</li>\n";
echo "<li>Try listing the new product</li>\n";
echo "</ol>\n";
?>
