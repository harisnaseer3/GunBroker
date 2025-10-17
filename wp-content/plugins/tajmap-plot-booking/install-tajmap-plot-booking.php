<?php
/**
 * TajMap Plot Booking - Installation Script
 * 
 * This script helps install the TajMap Plot Booking plugin on a new WordPress site.
 * It includes only the essential components for:
 * 1. User Side - Available Plots (Interactive Canvas)
 * 2. Admin Side - Advanced Plot Editor
 * 
 * Installation Instructions:
 * 1. Upload this file and the plugin folder to your WordPress site
 * 2. Run this script once to set up the plugin
 * 3. Activate the plugin in WordPress admin
 * 4. Configure your plots and base map
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>TajMap Plot Booking - Installation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f1f1f1; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; margin-bottom: 10px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .step { margin: 20px 0; padding: 20px; background: #f8f9fa; border-left: 4px solid #007cba; }
        .step h3 { margin-top: 0; color: #007cba; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #005a87; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏗️ TajMap Plot Booking - Installation</h1>
            <p>Installation script for porting the plot booking system to your WordPress site</p>
        </div>

        <?php
        $step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        
        switch ($step) {
            case 1:
                // Step 1: Check requirements
                ?>
                <div class="step">
                    <h3>Step 1: System Requirements Check</h3>
                    
                    <?php
                    $requirements_met = true;
                    $issues = [];
                    
                    // Check WordPress version
                    global $wp_version;
                    if (version_compare($wp_version, '5.0', '<')) {
                        $issues[] = "WordPress version {$wp_version} is too old. Requires 5.0 or higher.";
                        $requirements_met = false;
                    }
                    
                    // Check PHP version
                    if (version_compare(PHP_VERSION, '7.4', '<')) {
                        $issues[] = "PHP version " . PHP_VERSION . " is too old. Requires 7.4 or higher.";
                        $requirements_met = false;
                    }
                    
                    // Check if plugin directory exists
                    $plugin_dir = WP_PLUGIN_DIR . '/tajmap-plot-booking';
                    if (!is_dir($plugin_dir)) {
                        $issues[] = "Plugin directory not found. Please upload the plugin files first.";
                        $requirements_met = false;
                    }
                    
                    // Check database permissions
                    global $wpdb;
                    $test_table = $wpdb->prefix . 'tajmap_test_' . time();
                    $result = $wpdb->query("CREATE TABLE IF NOT EXISTS `{$test_table}` (id INT PRIMARY KEY)");
                    if ($result === false) {
                        $issues[] = "Database permissions insufficient. Cannot create tables.";
                        $requirements_met = false;
                    } else {
                        $wpdb->query("DROP TABLE `{$test_table}`");
                    }
                    
                    if ($requirements_met) {
                        echo '<div class="status success">✅ All requirements met!</div>';
                    } else {
                        echo '<div class="status error">❌ Requirements not met:</div>';
                        echo '<ul>';
                        foreach ($issues as $issue) {
                            echo '<li>' . esc_html($issue) . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                    
                    <div class="status info">
                        <strong>System Information:</strong><br>
                        WordPress: <?php echo esc_html($wp_version); ?><br>
                        PHP: <?php echo esc_html(PHP_VERSION); ?><br>
                        Plugin Directory: <?php echo esc_html($plugin_dir); ?><br>
                        Database: <?php echo esc_html($wpdb->dbname); ?>
                    </div>
                    
                    <?php if ($requirements_met): ?>
                        <a href="?step=2" class="btn">Continue to Step 2</a>
                    <?php else: ?>
                        <div class="status warning">
                            Please resolve the issues above before continuing.
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                break;
                
            case 2:
                // Step 2: Create database tables
                ?>
                <div class="step">
                    <h3>Step 2: Database Setup</h3>
                    
                    <?php
                    global $wpdb;
                    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                    $charset_collate = $wpdb->get_charset_collate();
                    
                    $tables_created = [];
                    $errors = [];
                    
                    // Create plots table
                    $plots_sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tajmap_plots` (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        plot_name VARCHAR(191) NOT NULL,
                        street VARCHAR(191) NULL,
                        sector VARCHAR(191) NULL,
                        type VARCHAR(191) NULL,
                        category VARCHAR(191) NULL,
                        coordinates LONGTEXT NOT NULL,
                        status ENUM('available','sold') NOT NULL DEFAULT 'available',
                        base_image_id BIGINT UNSIGNED NULL,
                        base_image_transform LONGTEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NULL DEFAULT NULL,
                        PRIMARY KEY (id),
                        KEY status_idx (status)
                    ) $charset_collate;";

                    $result = dbDelta($plots_sql);
                    if ($result) {
                        $tables_created[] = 'Plots table';
                    } else {
                        $errors[] = 'Failed to create plots table';
                    }

                    // Add 'category' column and rename 'block' to 'type' if table exists (for existing installations)
                    $table_name = $wpdb->prefix . 'tajmap_plots';
                    $columns = $wpdb->get_col("DESCRIBE `{$table_name}`", 0);

                    if (in_array('block', $columns)) {
                        // Rename 'block' column to 'type'
                        $wpdb->query("ALTER TABLE `{$table_name}` CHANGE `block` `type` VARCHAR(191) NULL");
                        $tables_created[] = 'Renamed block column to type';
                    } elseif (!in_array('type', $columns)) {
                        // Add 'type' column if it doesn't exist
                        $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `type` VARCHAR(191) NULL AFTER `sector`");
                        $tables_created[] = 'Added type column';
                    }

                    if (!in_array('category', $columns)) {
                        // Add 'category' column if it doesn't exist
                        $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `category` VARCHAR(191) NULL AFTER `type`");
                        $tables_created[] = 'Added category column';
                    }
                    
                    // Create leads table
                    $leads_sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tajmap_leads` (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        plot_id BIGINT UNSIGNED NOT NULL,
                        phone VARCHAR(64) NOT NULL,
                        email VARCHAR(191) NOT NULL,
                        message TEXT NULL,
                        status ENUM('new','contacted','interested','closed') NOT NULL DEFAULT 'new',
                        source VARCHAR(50) DEFAULT 'website',
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY plot_idx (plot_id),
                        KEY status_idx (status),
                        KEY email_idx (email)
                    ) $charset_collate;";
                    
                    $result = dbDelta($leads_sql);
                    if ($result) {
                        $tables_created[] = 'Leads table';
                    } else {
                        $errors[] = 'Failed to create leads table';
                    }
                    
                    // Create users table
                    $users_sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tajmap_users` (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        wp_user_id BIGINT UNSIGNED NULL,
                        email VARCHAR(191) NOT NULL UNIQUE,
                        phone VARCHAR(64) NULL,
                        first_name VARCHAR(100) NULL,
                        last_name VARCHAR(100) NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY wp_user_idx (wp_user_id),
                        KEY email_idx (email)
                    ) $charset_collate;";
                    
                    $result = dbDelta($users_sql);
                    if ($result) {
                        $tables_created[] = 'Users table';
                    } else {
                        $errors[] = 'Failed to create users table';
                    }
                    
                    // Create saved plots table
                    $saved_plots_sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tajmap_saved_plots` (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        user_id BIGINT UNSIGNED NOT NULL,
                        plot_id BIGINT UNSIGNED NOT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY user_idx (user_id),
                        KEY plot_idx (plot_id),
                        UNIQUE KEY user_plot_idx (user_id, plot_id)
                    ) $charset_collate;";
                    
                    $result = dbDelta($saved_plots_sql);
                    if ($result) {
                        $tables_created[] = 'Saved plots table';
                    } else {
                        $errors[] = 'Failed to create saved plots table';
                    }
                    
                    // Create lead history table
                    $lead_history_sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tajmap_lead_history` (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        lead_id BIGINT UNSIGNED NOT NULL,
                        user_id BIGINT UNSIGNED NULL,
                        action VARCHAR(100) NOT NULL,
                        details TEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        KEY lead_idx (lead_id)
                    ) $charset_collate;";
                    
                    $result = dbDelta($lead_history_sql);
                    if ($result) {
                        $tables_created[] = 'Lead history table';
                    } else {
                        $errors[] = 'Failed to create lead history table';
                    }
                    
                    if (empty($errors)) {
                        echo '<div class="status success">✅ Database tables created successfully!</div>';
                        echo '<ul>';
                        foreach ($tables_created as $table) {
                            echo '<li>' . esc_html($table) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<div class="status error">❌ Database setup failed:</div>';
                        echo '<ul>';
                        foreach ($errors as $error) {
                            echo '<li>' . esc_html($error) . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                    
                    <a href="?step=3" class="btn">Continue to Step 3</a>
                </div>
                <?php
                break;
                
            case 3:
                // Step 3: Create pages and configure
                ?>
                <div class="step">
                    <h3>Step 3: Create Pages and Configure</h3>
                    
                    <?php
                    $pages_created = [];
                    $errors = [];
                    
                    // Create Available Plots page
                    $plots_page = get_page_by_path('available-plots');
                    if (!$plots_page) {
                        $page_id = wp_insert_post([
                            'post_title' => 'Available Plots',
                            'post_content' => '[tajmap_plot_canvas]',
                            'post_status' => 'publish',
                            'post_type' => 'page',
                            'post_name' => 'available-plots'
                        ]);
                        
                        if ($page_id && !is_wp_error($page_id)) {
                            $pages_created[] = 'Available Plots page created';
                        } else {
                            $errors[] = 'Failed to create Available Plots page';
                        }
                    } else {
                        $pages_created[] = 'Available Plots page already exists';
                    }
                    
                    // Create admin menu items (this will be done by the plugin)
                    $pages_created[] = 'Admin menu items will be created when plugin is activated';
                    
                    // Set default settings
                    $default_settings = [
                        'company_name' => 'Your Company Name',
                        'development_name' => 'Your Development',
                        'company_email' => get_option('admin_email'),
                        'company_phone' => '',
                        'default_currency' => 'USD',
                        'measurement_units' => 'sqft',
                        'default_zoom_level' => 1,
                        'email_notifications' => true,
                        'require_registration' => false,
                        'enable_analytics' => true
                    ];
                    
                    $existing_settings = get_option('tajmap_pb_settings', []);
                    $merged_settings = array_merge($default_settings, $existing_settings);
                    update_option('tajmap_pb_settings', $merged_settings);
                    $pages_created[] = 'Default settings configured';
                    
                    if (empty($errors)) {
                        echo '<div class="status success">✅ Configuration completed!</div>';
                        echo '<ul>';
                        foreach ($pages_created as $item) {
                            echo '<li>' . esc_html($item) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<div class="status error">❌ Configuration failed:</div>';
                        echo '<ul>';
                        foreach ($errors as $error) {
                            echo '<li>' . esc_html($error) . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                    
                    <a href="?step=4" class="btn">Continue to Step 4</a>
                </div>
                <?php
                break;
                
            case 4:
                // Step 4: Final setup and instructions
                ?>
                <div class="step">
                    <h3>Step 4: Final Setup</h3>
                    
                    <div class="status success">
                        <h4>🎉 Installation Complete!</h4>
                        <p>Your TajMap Plot Booking system has been successfully installed.</p>
                    </div>
                    
                    <div class="status info">
                        <h4>Next Steps:</h4>
                        <ol>
                            <li><strong>Activate the Plugin:</strong> Go to <a href="<?php echo admin_url('plugins.php'); ?>">Plugins</a> and activate "TajMap Plot Booking"</li>
                            <li><strong>Access Admin Panel:</strong> Go to <a href="<?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?>">Plot Management → Plot Editor</a></li>
                            <li><strong>Upload Base Map:</strong> In the Plot Editor, upload your base map image</li>
                            <li><strong>Create Plots:</strong> Use the drawing tools to create your plots</li>
                            <li><strong>View Frontend:</strong> Visit <a href="<?php echo home_url('/available-plots'); ?>">Available Plots</a> to see the user interface</li>
                        </ol>
                    </div>
                    
                    <div class="status warning">
                        <h4>Important URLs:</h4>
                        <ul>
                            <li><strong>Admin Plot Editor:</strong> <a href="<?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?>"><?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?></a></li>
                            <li><strong>User Available Plots:</strong> <a href="<?php echo home_url('/available-plots'); ?>"><?php echo home_url('/available-plots'); ?></a></li>
                            <li><strong>Admin Dashboard:</strong> <a href="<?php echo admin_url('admin.php?page=tajmap-plot-management'); ?>"><?php echo admin_url('admin.php?page=tajmap-plot-management'); ?></a></li>
                        </ul>
                    </div>
                    
                    <div class="code">
                        <strong>Shortcode Usage:</strong><br>
                        <code>[tajmap_plot_canvas]</code> - Display the interactive plot canvas<br>
                        <code>[tajmap_plot_selection]</code> - Alternative plot selection interface
                    </div>
                    
                    <div class="status info">
                        <h4>Features Included:</h4>
                        <ul>
                            <li>✅ Interactive Canvas for Plot Viewing</li>
                            <li>✅ Advanced Plot Editor for Admins</li>
                            <li>✅ Lead Management System</li>
                            <li>✅ Responsive Design</li>
                            <li>✅ Database Integration</li>
                            <li>✅ AJAX-powered Interface</li>
                        </ul>
                    </div>
                    
                    <a href="<?php echo admin_url('plugins.php'); ?>" class="btn">Go to Plugins</a>
                    <a href="<?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?>" class="btn">Go to Plot Editor</a>
                </div>
                <?php
                break;
        }
        ?>
        
        <div class="step">
            <h3>Installation Progress</h3>
            <div style="display: flex; justify-content: space-between; margin: 20px 0;">
                <div style="text-align: center;">
                    <div style="width: 30px; height: 30px; border-radius: 50%; background: <?php echo $step >= 1 ? '#28a745' : '#6c757d'; ?>; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto;">1</div>
                    <small>Requirements</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 30px; height: 30px; border-radius: 50%; background: <?php echo $step >= 2 ? '#28a745' : '#6c757d'; ?>; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto;">2</div>
                    <small>Database</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 30px; height: 30px; border-radius: 50%; background: <?php echo $step >= 3 ? '#28a745' : '#6c757d'; ?>; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto;">3</div>
                    <small>Configure</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 30px; height: 30px; border-radius: 50%; background: <?php echo $step >= 4 ? '#28a745' : '#6c757d'; ?>; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto;">4</div>
                    <small>Complete</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

