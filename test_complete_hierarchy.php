<?php
require_once 'wp-config.php';
require_once 'wp-content/plugins/gunbroker-integration/includes/class-gunbroker-api.php';

// Initialize the API
$api = new GunBroker_API();

echo "<h1>Complete Category Hierarchy Test</h1>";

// Test authentication first
$auth_result = $api->ensure_authenticated();
if (is_wp_error($auth_result)) {
    echo "❌ Authentication failed: " . $auth_result->get_error_message();
    exit;
}
echo "✅ Authentication successful<br><br>";

// Test complete category hierarchy
echo "<h2>1. Testing Complete Category Hierarchy</h2>";
$hierarchy = $api->get_complete_category_hierarchy();

if (is_wp_error($hierarchy)) {
    echo "❌ Failed to get complete hierarchy: " . $hierarchy->get_error_message();
    exit;
}

echo "✅ Complete hierarchy fetched successfully<br><br>";

// Show hierarchy statistics
echo "<h3>Hierarchy Statistics:</h3>";
echo "<ul>";
echo "<li><strong>Total Categories:</strong> " . count($hierarchy['all_categories']) . "</li>";
echo "<li><strong>Parent Categories:</strong> " . count($hierarchy['parent_categories']) . "</li>";
echo "<li><strong>Terminal Categories:</strong> " . count($hierarchy['terminal_categories']) . "</li>";
echo "</ul>";

// Show hierarchical tree structure
echo "<h3>Hierarchical Tree Structure:</h3>";
function display_tree($categories, $level = 0) {
    $indent = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level);
    
    foreach ($categories as $category) {
        $level_indicator = str_repeat("├─ ", $level);
        if ($level == 0) $level_indicator = "• ";
        
        echo "<div style='margin-left: " . ($level * 20) . "px;'>";
        echo $level_indicator . "<strong>{$category['name']}</strong> (ID: {$category['id']}, Level: {$category['level']})";
        
        if (empty($category['children'])) {
            echo " <span style='color: green;'>[TERMINAL]</span>";
        } else {
            echo " <span style='color: blue;'>[PARENT - " . count($category['children']) . " children]</span>";
        }
        echo "</div>";
        
        if (!empty($category['children'])) {
            display_tree($category['children'], $level + 1);
        }
    }
}

display_tree($hierarchy['hierarchical_tree']);

// Show terminal categories in a table
echo "<h3>Terminal Categories (Can be used for listings):</h3>";
if (!empty($hierarchy['terminal_categories'])) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Name</th><th>Parent ID</th><th>Level</th></tr>";
    
    foreach ($hierarchy['terminal_categories'] as $cat) {
        echo "<tr>";
        echo "<td>{$cat['id']}</td>";
        echo "<td>{$cat['name']}</td>";
        echo "<td>{$cat['parent_id']}</td>";
        echo "<td>{$cat['level']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No terminal categories found.</p>";
}

// Test with a real product using different category levels
echo "<h2>2. Testing with Different Category Levels</h2>";

$products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
if (empty($products)) {
    echo "❌ No WooCommerce products found. Please create a product first.<br>";
} else {
    $product = $products[0];
    echo "✅ Found product: " . $product->get_name() . " (ID: " . $product->get_id() . ")<br><br>";
    
    // Test with different levels of categories
    $test_categories = array();
    
    // Get one parent category
    if (!empty($hierarchy['parent_categories'])) {
        $test_categories[] = $hierarchy['parent_categories'][0];
    }
    
    // Get one terminal category
    if (!empty($hierarchy['terminal_categories'])) {
        $test_categories[] = $hierarchy['terminal_categories'][0];
    }
    
    foreach ($test_categories as $test_cat) {
        echo "<h4>Testing Category: {$test_cat['name']} (ID: {$test_cat['id']}, Level: {$test_cat['level']})</h4>";
        
        // Prepare listing data
        $listing_data = $api->prepare_listing_data($product, $test_cat['id']);
        
        if (is_wp_error($listing_data)) {
            echo "❌ Failed to prepare listing data: " . $listing_data->get_error_message() . "<br>";
            continue;
        }
        
        echo "✅ Listing data prepared successfully<br>";
        echo "<strong>CategoryID used:</strong> " . $listing_data['CategoryID'] . "<br>";
        
        // Check IsFFLRequired field
        if (isset($listing_data['IsFFLRequired'])) {
            echo "<strong>IsFFLRequired:</strong> " . ($listing_data['IsFFLRequired'] ? 'true' : 'false') . "<br>";
        } else {
            echo "<strong>IsFFLRequired:</strong> Not included<br>";
        }
        
        // Test API call
        $result = $api->make_request('Items', 'POST', $listing_data);
        
        if (is_wp_error($result)) {
            $error_data = json_decode($result->get_error_message(), true);
            if (isset($error_data['userMessage'])) {
                $error_msg = $error_data['userMessage'];
                
                if (strpos($error_msg, 'terminal categories') !== false) {
                    echo "❌ Parent category - cannot be used for listings<br>";
                } elseif (strpos($error_msg, 'IsFFLRequired option can not be used') !== false) {
                    echo "❌ IsFFLRequired not supported for this category<br>";
                } elseif (strpos($error_msg, 'Required property') !== false) {
                    echo "⚠️ Missing required field: " . $error_msg . "<br>";
                } else {
                    echo "⚠️ Other error: " . $error_msg . "<br>";
                }
            } else {
                echo "❌ API Error: " . $result->get_error_message() . "<br>";
            }
        } else {
            echo "✅ API call successful!<br>";
        }
        
        echo "<br>";
    }
}

echo "<h2>3. Test Complete</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;'>";
echo "<h3>✅ Complete Category Hierarchy Implemented</h3>";
echo "<p>The system now:</p>";
echo "<ul>";
echo "<li>✅ Fetches complete category hierarchy from GunBroker API</li>";
echo "<li>✅ Identifies parent, child, and terminal categories</li>";
echo "<li>✅ Builds hierarchical tree structure</li>";
echo "<li>✅ Calculates category levels (0, 1, 2, etc.)</li>";
echo "<li>✅ Provides organized category data for dropdowns</li>";
echo "</ul>";
echo "<p><strong>Next Step:</strong> Update the dropdown templates to show the hierarchical structure!</p>";
echo "</div>";
?>
