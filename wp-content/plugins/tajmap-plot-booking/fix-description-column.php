<?php
/**
 * Fix Description Column - Manual Database Update
 * 
 * This script manually adds the description column to the plots table
 * if it doesn't exist, and tests the save functionality.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress, try to find WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php',
        './wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please run this script from your WordPress installation.');
    }
}

// Check if we're in WordPress admin
if (!is_admin()) {
    wp_die('This script must be run from WordPress admin area.');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this script.');
}

global $wpdb;

echo "<h2>Fixing Description Column</h2>";

// Check if description column exists
$column_exists = $wpdb->get_results($wpdb->prepare(
    "SHOW COLUMNS FROM `" . $wpdb->prefix . "tajmap_plots" . "` LIKE %s",
    'description'
));

echo "<p>Checking for description column...</p>";

if (empty($column_exists)) {
    echo "<p style='color: orange;'>Description column does NOT exist. Adding it now...</p>";
    
    $alter_result = $wpdb->query("ALTER TABLE `" . $wpdb->prefix . "tajmap_plots" . "` ADD COLUMN `description` TEXT NULL AFTER `street`");
    
    if ($alter_result !== false) {
        echo "<p style='color: green;'>✅ Description column added successfully!</p>";
        error_log('TajMap: Added description column, result: ' . var_export($alter_result, true));
    } else {
        echo "<p style='color: red;'>❌ Failed to add description column. Error: " . $wpdb->last_error . "</p>";
        error_log('TajMap: Database error adding description column: ' . $wpdb->last_error);
    }
} else {
    echo "<p style='color: green;'>✅ Description column already exists.</p>";
}

// Show current table structure
echo "<h3>Current Table Structure:</h3>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM `" . $wpdb->prefix . "tajmap_plots" . "`");
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . esc_html($column->Field) . "</td>";
    echo "<td>" . esc_html($column->Type) . "</td>";
    echo "<td>" . esc_html($column->Null) . "</td>";
    echo "<td>" . esc_html($column->Key) . "</td>";
    echo "<td>" . esc_html($column->Default) . "</td>";
    echo "<td>" . esc_html($column->Extra) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test saving a description
echo "<h3>Testing Description Save:</h3>";

// Get a sample plot to test with
$sample_plot = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "tajmap_plots" . "` LIMIT 1", ARRAY_A);

if ($sample_plot) {
    echo "<p>Found sample plot: " . esc_html($sample_plot['plot_name']) . "</p>";
    
    // Test updating with description
    $test_description = "Test description added at " . current_time('mysql');
    $update_result = $wpdb->update(
        $wpdb->prefix . "tajmap_plots",
        ['description' => $test_description],
        ['id' => $sample_plot['id']]
    );
    
    if ($update_result !== false) {
        echo "<p style='color: green;'>✅ Successfully saved test description!</p>";
        
        // Verify it was saved
        $updated_plot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `" . $wpdb->prefix . "tajmap_plots" . "` WHERE id = %d",
            $sample_plot['id']
        ), ARRAY_A);
        
        if ($updated_plot && $updated_plot['description'] === $test_description) {
            echo "<p style='color: green;'>✅ Description verified in database: " . esc_html($updated_plot['description']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Description not found in database after save.</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to save test description. Error: " . $wpdb->last_error . "</p>";
    }
} else {
    echo "<p style='color: orange;'>No plots found to test with. Create a plot first.</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Go to the Plot Editor and try saving a plot with a description</li>";
echo "<li>Refresh the page and check if the description persists</li>";
echo "<li>If it still doesn't work, check the browser console for JavaScript errors</li>";
echo "</ol>";

echo "<p><a href='" . admin_url('admin.php?page=tajmap-plot-editor') . "'>Go to Plot Editor</a></p>";
?>

