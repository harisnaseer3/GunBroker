<?php
/**
 * TajMap Plot Booking - Deployment Package Creator
 * 
 * This script creates a deployment package with only the essential files
 * needed for the user-side available plots and admin-side plot editor.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('This script must be run from WordPress admin area.');
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this script.');
}

// Define essential files for deployment
$essential_files = [
    // Core plugin files
    'tajmap-plot-booking.php',
    'includes/class-tajmap-pb.php',
    
    // Admin templates (Plot Editor)
    'templates/admin-plot-editor.php',
    'templates/admin-dashboard.php',
    'templates/admin-leads-enhanced.php',
    'templates/admin-reports.php',
    'templates/admin-settings.php',
    
    // Frontend templates (User plots)
    'templates/frontend/plot-selection-interactive.php',
    'templates/frontend/plot-selection-canvas.php',
    'templates/frontend/dashboard.php',
    'templates/frontend/gallery.php',
    'templates/frontend/landing.php',
    'templates/frontend/user-plots-page.php',
    'templates/public-shortcode.php',
    
    // Essential assets
    'assets/plot-editor.js',
    'assets/frontend.css',
    'assets/admin-enhanced.css',
    'assets/admin.css',
    'assets/admin.js',
    'assets/frontend.js',
    'assets/public.css',
    'assets/public.js',
    
    // Installation files
    'install-tajmap-plot-booking.php',
    'README-INSTALLATION.md'
];

// Create deployment package
function create_deployment_package() {
    global $essential_files;
    
    $plugin_dir = WP_PLUGIN_DIR . '/tajmap-plot-booking';
    $package_dir = $plugin_dir . '/deployment-package';
    
    // Create package directory
    if (!is_dir($package_dir)) {
        wp_mkdir_p($package_dir);
    }
    
    $copied_files = [];
    $missing_files = [];
    
    foreach ($essential_files as $file) {
        $source_path = $plugin_dir . '/' . $file;
        $dest_path = $package_dir . '/' . $file;
        $dest_dir = dirname($dest_path);
        
        if (file_exists($source_path)) {
            // Create directory if it doesn't exist
            if (!is_dir($dest_dir)) {
                wp_mkdir_p($dest_dir);
            }
            
            // Copy file
            if (copy($source_path, $dest_path)) {
                $copied_files[] = $file;
            } else {
                $missing_files[] = $file . ' (copy failed)';
            }
        } else {
            $missing_files[] = $file . ' (not found)';
        }
    }
    
    // Create a deployment info file
    $deployment_info = [
        'created_date' => current_time('mysql'),
        'wordpress_version' => get_bloginfo('version'),
        'plugin_version' => '2.0.3',
        'files_included' => count($copied_files),
        'files_missing' => count($missing_files),
        'essential_files' => $essential_files,
        'copied_files' => $copied_files,
        'missing_files' => $missing_files
    ];
    
    file_put_contents(
        $package_dir . '/deployment-info.json',
        json_encode($deployment_info, JSON_PRETTY_PRINT)
    );
    
    // Create a simple installation guide
    $install_guide = "TajMap Plot Booking - Deployment Package\n";
    $install_guide .= "==========================================\n\n";
    $install_guide .= "Installation Steps:\n";
    $install_guide .= "1. Upload this entire folder to your WordPress plugins directory\n";
    $install_guide .= "2. Rename the folder to 'tajmap-plot-booking'\n";
    $install_guide .= "3. Run install-tajmap-plot-booking.php in your browser\n";
    $install_guide .= "4. Activate the plugin in WordPress admin\n\n";
    $install_guide .= "Files included: " . count($copied_files) . "\n";
    $install_guide .= "Missing files: " . count($missing_files) . "\n\n";
    $install_guide .= "For detailed instructions, see README-INSTALLATION.md\n";
    
    file_put_contents($package_dir . '/INSTALL.txt', $install_guide);
    
    return [
        'package_dir' => $package_dir,
        'copied_files' => $copied_files,
        'missing_files' => $missing_files,
        'success' => empty($missing_files)
    ];
}

// Handle the packaging request
if (isset($_GET['action']) && $_GET['action'] === 'create_package') {
    $result = create_deployment_package();
    
    if ($result['success']) {
        $message = "✅ Deployment package created successfully!<br>";
        $message .= "Package location: " . $result['package_dir'] . "<br>";
        $message .= "Files included: " . count($result['copied_files']) . "<br>";
        $message .= "Missing files: " . count($result['missing_files']);
    } else {
        $message = "❌ Package creation completed with issues:<br>";
        $message .= "Files included: " . count($result['copied_files']) . "<br>";
        $message .= "Missing files: " . count($result['missing_files']) . "<br>";
        if (!empty($result['missing_files'])) {
            $message .= "Missing: " . implode(', ', $result['missing_files']);
        }
    }
    
    wp_die($message);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TajMap Plot Booking - Deployment Package Creator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f1f1f1; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; margin-bottom: 10px; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005a87; }
        .file-list { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; max-height: 300px; overflow-y: auto; }
        .file-item { padding: 5px 0; border-bottom: 1px solid #dee2e6; }
        .file-item:last-child { border-bottom: none; }
        .status { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 TajMap Plot Booking - Deployment Package Creator</h1>
            <p>Create a deployment package with essential files for porting to another WordPress site</p>
        </div>

        <div class="status info">
            <h3>Package Contents</h3>
            <p>This package will include only the essential files needed for:</p>
            <ul>
                <li><strong>User Side:</strong> Interactive plot viewing canvas</li>
                <li><strong>Admin Side:</strong> Advanced plot editor</li>
            </ul>
        </div>

        <div class="file-list">
            <h4>Files to be included (<?php echo count($essential_files); ?> files):</h4>
            <?php foreach ($essential_files as $file): ?>
                <div class="file-item">
                    <code><?php echo esc_html($file); ?></code>
                    <?php if (file_exists(WP_PLUGIN_DIR . '/tajmap-plot-booking/' . $file)): ?>
                        <span style="color: green;">✅</span>
                    <?php else: ?>
                        <span style="color: red;">❌</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="status warning">
            <h4>⚠️ Important Notes:</h4>
            <ul>
                <li>This package includes only the essential files for core functionality</li>
                <li>Test files and development files are excluded</li>
                <li>The package will be created in: <code>/wp-content/plugins/tajmap-plot-booking/deployment-package/</code></li>
                <li>You can then zip the deployment-package folder for distribution</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="?action=create_package" class="btn" onclick="return confirm('Create deployment package? This will copy files to the deployment-package directory.');">
                📦 Create Deployment Package
            </a>
        </div>

        <div class="status info">
            <h4>After Creating Package:</h4>
            <ol>
                <li>Navigate to the deployment-package folder</li>
                <li>Zip the entire contents</li>
                <li>Upload to your target WordPress site</li>
                <li>Extract to <code>/wp-content/plugins/tajmap-plot-booking/</code></li>
                <li>Run the installation script</li>
            </ol>
        </div>
    </div>
</body>
</html>

