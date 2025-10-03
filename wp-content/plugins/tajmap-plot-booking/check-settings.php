<?php
require_once('../../../wp-config.php');

echo "Checking TajMap settings...\n\n";

$settings = get_option('tajmap_pb_settings', []);
echo "Settings found: " . (empty($settings) ? 'No' : 'Yes') . "\n";

if (!empty($settings)) {
    echo "Settings content:\n";
    print_r($settings);
    
    if (isset($settings['global_base_map_image_id'])) {
        echo "\nGlobal base map image ID: " . $settings['global_base_map_image_id'] . "\n";
        
        if ($settings['global_base_map_image_id']) {
            $image_url = wp_get_attachment_url($settings['global_base_map_image_id']);
            echo "Image URL: " . ($image_url ? $image_url : 'Not found') . "\n";
        }
    }
    
    if (isset($settings['global_base_map_transform'])) {
        echo "Transform data: " . $settings['global_base_map_transform'] . "\n";
    }
} else {
    echo "No settings found in database.\n";
}
?>