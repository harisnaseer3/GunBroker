<?php
// Check existing products and their data
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>Product Data Check</h1>\n";

// Get all products
$products = wc_get_products(array('limit' => 10, 'status' => 'publish'));

if (empty($products)) {
    echo "<p style='color: red;'>No products found</p>\n";
    exit;
}

echo "<h2>Found " . count($products) . " products:</h2>\n";

foreach ($products as $product) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9;'>\n";
    echo "<h3>" . $product->get_name() . " (ID: " . $product->get_id() . ")</h3>\n";
    
    // Check basic product data
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_price();
    $stock_quantity = $product->get_stock_quantity();
    $description = $product->get_description();
    $short_description = $product->get_short_description();
    
    echo "<p><strong>Regular Price:</strong> " . ($regular_price ?: 'Not set') . "</p>\n";
    echo "<p><strong>Sale Price:</strong> " . ($sale_price ?: 'Not set') . "</p>\n";
    echo "<p><strong>Stock Quantity:</strong> " . ($stock_quantity ?: 'Not set') . "</p>\n";
    echo "<p><strong>Description Length:</strong> " . strlen($description) . " chars</p>\n";
    echo "<p><strong>Short Description Length:</strong> " . strlen($short_description) . " chars</p>\n";
    
    // Check if product has valid data for GunBroker
    $has_price = !empty($sale_price) && $sale_price > 0;
    $has_description = !empty($description) || !empty($short_description);
    $has_stock = $stock_quantity > 0;
    
    echo "<div style='margin: 10px 0;'>\n";
    if ($has_price) {
        echo "<p style='color: green;'>✅ Has valid price</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No valid price - This will cause listing to fail!</p>\n";
    }
    
    if ($has_description) {
        echo "<p style='color: green;'>✅ Has description</p>\n";
    } else {
        echo "<p style='color: red;'>❌ No description - This will cause listing to fail!</p>\n";
    }
    
    if ($has_stock) {
        echo "<p style='color: green;'>✅ Has stock</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No stock - Will use quantity 1</p>\n";
    }
    echo "</div>\n";
    
    // Test price calculation
    $base_price = floatval($product->get_regular_price());
    if ($base_price <= 0) {
        $base_price = floatval($product->get_price());
    }
    
    $markup_percentage = 10;
    $gunbroker_price = $base_price * (1 + $markup_percentage / 100);
    
    echo "<p><strong>Calculated GunBroker Price:</strong> $" . number_format($gunbroker_price, 2) . "</p>\n";
    
    if ($gunbroker_price <= 0) {
        echo "<p style='color: red;'>❌ Invalid GunBroker price - This will cause 'empty request' error!</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Valid GunBroker price</p>\n";
    }
    
    echo "</div>\n";
}

echo "<h2>Recommendations:</h2>\n";
echo "<ul>\n";
echo "<li><strong>If your products have no price:</strong> Edit them in WooCommerce and set a price (e.g., $100)</li>\n";
echo "<li><strong>If your products have no description:</strong> Add a description in the product editor</li>\n";
echo "<li><strong>For a fresh start:</strong> Create a new product with all required fields</li>\n";
echo "</ul>\n";

echo "<h2>Quick Fix Steps:</h2>\n";
echo "<ol>\n";
echo "<li>Go to WooCommerce → Products</li>\n";
echo "<li>Edit your 'test rifle 1' product</li>\n";
echo "<li>Set a Regular Price (e.g., $100)</li>\n";
echo "<li>Add a Product Description</li>\n";
echo "<li>Save the product</li>\n";
echo "<li>Try listing again</li>\n";
echo "</ol>\n";
?>
