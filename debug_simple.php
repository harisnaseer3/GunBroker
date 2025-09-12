<?php
// Simple debug script to test listing data
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Simple GunBroker Debug</h1>\n";

// Get a test product
$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "<p style='color: red;'>No products found</p>\n";
    exit;
}

$product = $products[0];
echo "<h2>Product Info:</h2>\n";
echo "<p><strong>ID:</strong> " . $product->get_id() . "</p>\n";
echo "<p><strong>Name:</strong> " . $product->get_name() . "</p>\n";
echo "<p><strong>Price:</strong> " . $product->get_price() . "</p>\n";
echo "<p><strong>Stock:</strong> " . $product->get_stock_quantity() . "</p>\n";

// Check if product has a price
if (empty($product->get_price()) || $product->get_price() <= 0) {
    echo "<p style='color: red;'>❌ Product has no price or price is 0!</p>\n";
    echo "<p>This will cause the listing data to be invalid.</p>\n";
} else {
    echo "<p style='color: green;'>✅ Product has a valid price</p>\n";
}

// Check category
$category_id = get_post_meta($product->get_id(), '_gunbroker_category', true);
if (empty($category_id)) {
    $category_id = get_option('gunbroker_default_category', 3022);
    echo "<p><strong>Using default category:</strong> $category_id</p>\n";
} else {
    echo "<p><strong>Product category:</strong> $category_id</p>\n";
}

// Check condition
$condition = get_post_meta($product->get_id(), '_gunbroker_condition', true);
if (empty($condition)) {
    $condition = 1;
    echo "<p><strong>Using default condition:</strong> $condition</p>\n";
} else {
    echo "<p><strong>Product condition:</strong> $condition</p>\n";
}

// Check country
$country_code = get_post_meta($product->get_id(), '_gunbroker_country', true);
if (empty($country_code)) {
    $country_code = get_option('gunbroker_default_country', 'US');
    echo "<p><strong>Using default country:</strong> $country_code</p>\n";
} else {
    echo "<p><strong>Product country:</strong> $country_code</p>\n";
}

// Test basic listing data
$title = $product->get_name();
$description = $product->get_description() ?: 'No description available';
$price = floatval($product->get_price());
$markup = floatval(get_option('gunbroker_markup_percentage', 10));
$gunbroker_price = $price * (1 + $markup / 100);

echo "<h2>Calculated Values:</h2>\n";
echo "<p><strong>Title:</strong> " . substr($title, 0, 75) . "</p>\n";
echo "<p><strong>Description:</strong> " . substr($description, 0, 100) . "...</p>\n";
echo "<p><strong>Original Price:</strong> $" . number_format($price, 2) . "</p>\n";
echo "<p><strong>Markup:</strong> " . $markup . "%</p>\n";
echo "<p><strong>GunBroker Price:</strong> $" . number_format($gunbroker_price, 2) . "</p>\n";

// Test JSON encoding
$test_data = array(
    'Title' => substr($title, 0, 75),
    'Description' => $description,
    'CategoryID' => intval($category_id),
    'StartingBid' => number_format($gunbroker_price, 2, '.', ''),
    'Quantity' => max(1, intval($product->get_stock_quantity()) ?: 1),
    'Condition' => intval($condition),
    'CountryCode' => strtoupper(substr($country_code, 0, 2))
);

echo "<h2>Test Listing Data:</h2>\n";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars(json_encode($test_data, JSON_PRETTY_PRINT));
echo "</pre>\n";

// Test JSON encoding
$json_data = json_encode($test_data);
if ($json_data === false) {
    echo "<p style='color: red;'>❌ JSON encoding failed: " . json_last_error_msg() . "</p>\n";
} else {
    echo "<p style='color: green;'>✅ JSON encoding successful</p>\n";
    echo "<p><strong>JSON Length:</strong> " . strlen($json_data) . " characters</p>\n";
}

echo "<h2>Recommendations:</h2>\n";
echo "<ul>\n";
if (empty($product->get_price()) || $product->get_price() <= 0) {
    echo "<li style='color: red;'>Set a price for your product in WooCommerce</li>\n";
}
echo "<li>Make sure the category ID ($category_id) is valid for your GunBroker account</li>\n";
echo "<li>Check that all required fields are properly set</li>\n";
echo "</ul>\n";
?>
