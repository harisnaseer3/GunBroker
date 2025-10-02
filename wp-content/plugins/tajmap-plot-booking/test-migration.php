<?php
// Test script to check and add the base_image_transform column
require_once('wp-config.php');

global $wpdb;

// Check if base_image_transform column exists
$table_name = $wpdb->prefix . 'tajmap_plots';
$column_exists = $wpdb->get_results($wpdb->prepare(
    "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
    'base_image_transform'
));

echo "Checking table: {$table_name}\n";
echo "Column exists: " . (count($column_exists) > 0 ? 'YES' : 'NO') . "\n";

if (empty($column_exists)) {
    echo "Adding base_image_transform column...\n";
    $alter_result = $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `base_image_transform` LONGTEXT NULL AFTER `base_image_id`");
    echo "Alter result: " . var_export($alter_result, true) . "\n";
    if ($wpdb->last_error) {
        echo "Database error: " . $wpdb->last_error . "\n";
    }
} else {
    echo "Column already exists\n";
}

// Show current table structure
echo "\nCurrent table structure:\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}`");
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

// Show a sample plot
echo "\nSample plot data:\n";
$sample_plot = $wpdb->get_row("SELECT * FROM `{$table_name}` LIMIT 1", ARRAY_A);
if ($sample_plot) {
    print_r($sample_plot);
} else {
    echo "No plots found\n";
}
?>
