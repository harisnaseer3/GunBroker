<?php
// Test product price and data
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Product Price Test</h1>\n";

// Get all products
$products = wc_get_products(array('limit' => 10, 'status' => 'publish'));

if (empty($products)) {
    echo "<p style='color: red;'>No products found</p>\n";
    exit;
}

echo "<h2>Found " . count($products) . " products:</h2>\n";

foreach ($products as $product) {
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>\n";
    echo "<h3>" . $product->get_name() . " (ID: " . $product->get_id() . ")</h3>\n";
    
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_price();
    $price = $product->get_price();
    
    echo "<p><strong>Regular Price:</strong> " . ($regular_price ?: 'Not set') . "</p>\n";
    echo "<p><strong>Sale Price:</strong> " . ($sale_price ?: 'Not set') . "</p>\n";
    echo "<p><strong>Current Price:</strong> " . ($price ?: 'Not set') . "</p>\n";
    echo "<p><strong>Stock Quantity:</strong> " . ($product->get_stock_quantity() ?: 'Not set') . "</p>\n";
    
    // Test price calculation
    $base_price = floatval($product->get_regular_price());
    if ($base_price <= 0) {
        $base_price = floatval($product->get_price());
    }
    
    $markup_percentage = 10;
    $gunbroker_price = $base_price * (1 + $markup_percentage / 100);
    
    echo "<p><strong>Base Price (floatval):</strong> " . $base_price . "</p>\n";
    echo "<p><strong>GunBroker Price (10% markup):</strong> " . $gunbroker_price . "</p>\n";
    
    if ($base_price <= 0) {
        echo "<p style='color: red;'>❌ This product has no valid price!</p>\n";
    } else {
        echo "<p style='color: green;'>✅ This product has a valid price</p>\n";
    }
    
    echo "</div>\n";
}

echo "<h2>Recommendations:</h2>\n";
echo "<ul>\n";
echo "<li>Make sure your products have prices set in WooCommerce</li>\n";
echo "<li>Check that the price is greater than 0</li>\n";
echo "<li>Try setting a regular price if only sale price is set</li>\n";
echo "</ul>\n";
?>
