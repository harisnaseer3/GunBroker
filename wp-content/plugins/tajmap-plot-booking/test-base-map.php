<?php
require_once('../../../wp-config.php');

echo "Checking for base map settings...\n";

$settings = get_option('tajmap_pb_settings', []);
if (isset($settings['global_base_map_image_id']) && $settings['global_base_map_image_id']) {
    echo "Base map image ID found: " . $settings['global_base_map_image_id'] . "\n";
    $url = wp_get_attachment_url($settings['global_base_map_image_id']);
    echo "Image URL: " . ($url ? $url : 'Not found') . "\n";
    
    if (isset($settings['global_base_map_transform'])) {
        echo "Transform data: " . $settings['global_base_map_transform'] . "\n";
    }
} else {
    echo "No base map image configured\n";
    echo "Available settings keys: " . implode(', ', array_keys($settings)) . "\n";
}
?>

