<?php
/**
 * TajMap Plot Booking - Quick Migration Script
 * Run this file directly to migrate 'block' to 'type' and add 'category' column
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check permissions
if (!is_admin() && !current_user_can('manage_options')) {
    die('Unauthorized access');
}

global $wpdb;

echo "<h2>TajMap Plot Booking - Database Migration</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

$table_name = $wpdb->prefix . 'tajmap_plots';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if (!$table_exists) {
    echo "<p class='error'>❌ Table {$table_name} does not exist!</p>";
    die();
}

echo "<h3>Current Table Structure:</h3>";
$columns = $wpdb->get_results("DESCRIBE `{$table_name}`");
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $column) {
    echo "<tr><td>{$column->Field}</td><td>{$column->Type}</td><td>{$column->Null}</td><td>{$column->Key}</td></tr>";
}
echo "</table>";

echo "<h3>Running Migration...</h3>";

$column_names = $wpdb->get_col("DESCRIBE `{$table_name}`", 0);

// Step 1: Rename 'block' to 'type' if 'block' exists
if (in_array('block', $column_names)) {
    echo "<p class='info'>🔄 Found 'block' column, renaming to 'type'...</p>";
    $result = $wpdb->query("ALTER TABLE `{$table_name}` CHANGE `block` `type` VARCHAR(191) NULL");

    if ($result !== false) {
        echo "<p class='success'>✅ Successfully renamed 'block' to 'type'</p>";
    } else {
        echo "<p class='error'>❌ Failed to rename 'block' to 'type': " . $wpdb->last_error . "</p>";
    }
} elseif (!in_array('type', $column_names)) {
    echo "<p class='info'>🔄 Adding 'type' column...</p>";
    $result = $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `type` VARCHAR(191) NULL AFTER `sector`");

    if ($result !== false) {
        echo "<p class='success'>✅ Successfully added 'type' column</p>";
    } else {
        echo "<p class='error'>❌ Failed to add 'type' column: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p class='success'>✅ 'type' column already exists</p>";
}

// Step 2: Add 'category' column if it doesn't exist
// Refresh column list after first migration
$column_names = $wpdb->get_col("DESCRIBE `{$table_name}`", 0);

if (!in_array('category', $column_names)) {
    echo "<p class='info'>🔄 Adding 'category' column...</p>";
    $result = $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `category` VARCHAR(191) NULL AFTER `type`");

    if ($result !== false) {
        echo "<p class='success'>✅ Successfully added 'category' column</p>";
    } else {
        echo "<p class='error'>❌ Failed to add 'category' column: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p class='success'>✅ 'category' column already exists</p>";
}

echo "<h3>Updated Table Structure:</h3>";
$columns = $wpdb->get_results("DESCRIBE `{$table_name}`");
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($columns as $column) {
    echo "<tr><td>{$column->Field}</td><td>{$column->Type}</td><td>{$column->Null}</td><td>{$column->Key}</td></tr>";
}
echo "</table>";

echo "<h3 class='success'>✅ Migration Complete!</h3>";
echo "<p><a href='" . admin_url('admin.php?page=tajmap-plot-editor') . "'>Go to Plot Editor</a></p>";