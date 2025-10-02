<?php
require_once('wp-config.php');

global $wpdb;
$table_name = $wpdb->prefix . 'tajmap_plots';

echo "Current plots with base image data:\n";
$plots = $wpdb->get_results("SELECT id, plot_name, base_image_id, base_image_transform FROM `{$table_name}` ORDER BY id", ARRAY_A);

foreach ($plots as $plot) {
    echo "Plot {$plot['id']}: {$plot['plot_name']} - base_image_id: {$plot['base_image_id']}\n";
    if ($plot['base_image_transform']) {
        echo "  Transform: " . substr($plot['base_image_transform'], 0, 100) . "...\n";
    }
}
?>
