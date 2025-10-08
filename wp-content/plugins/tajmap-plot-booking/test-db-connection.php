<?php
// Quick database test - run this once then delete
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

require_once ABSPATH . 'wp-load.php';

global $wpdb;

echo "Testing database connection...\n";

// Test 1: Check if table exists
$table_name = $wpdb->prefix . 'tajmap_plots';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($table_exists) {
    echo "✅ Table $table_name exists\n";
    
    // Test 2: Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
    // Test 3: Try a simple insert
    $test_data = [
        'plot_name' => 'TEST_PLOT_' . time(),
        'street' => 'Test Street',
        'description' => 'Test Description',
        'sector' => 'Test',
        'block' => 'A',
        'coordinates' => '[{"x":10,"y":10}]',
        'status' => 'available',
        'created_at' => current_time('mysql')
    ];
    
    $result = $wpdb->insert($table_name, $test_data);
    
    if ($result !== false) {
        $insert_id = $wpdb->insert_id;
        echo "✅ Test insert successful! ID: $insert_id\n";
        
        // Clean up test record
        $wpdb->delete($table_name, ['id' => $insert_id]);
        echo "✅ Test record cleaned up\n";
    } else {
        echo "❌ Test insert failed: " . $wpdb->last_error . "\n";
    }
    
} else {
    echo "❌ Table $table_name does not exist!\n";
}

echo "Test completed.\n";
?>

