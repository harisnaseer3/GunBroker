<?php
// Handle form submission
if (isset($_POST['gunbroker_settings_nonce']) && wp_verify_nonce($_POST['gunbroker_settings_nonce'], 'gunbroker_settings')) {
    // Save all settings
    update_option('gunbroker_dev_key', sanitize_text_field($_POST['gunbroker_dev_key']));
    update_option('gunbroker_username', sanitize_text_field($_POST['gunbroker_username']));
    update_option('gunbroker_password', sanitize_text_field($_POST['gunbroker_password']));
    // Save global markup (used as default)
    if (isset($_POST['gunbroker_markup_percentage'])) {
        update_option('gunbroker_markup_percentage', floatval($_POST['gunbroker_markup_percentage']));
    }
    update_option('gunbroker_auto_end_zero_stock', isset($_POST['gunbroker_auto_end_zero_stock']));
    update_option('gunbroker_public_domain', sanitize_text_field($_POST['gunbroker_public_domain']));
    update_option('gunbroker_sandbox_mode', isset($_POST['gunbroker_sandbox_mode']));
    update_option('gunbroker_enable_buy_now', isset($_POST['gunbroker_enable_buy_now']));
    update_option('gunbroker_default_category', intval($_POST['gunbroker_default_category']));
    update_option('gunbroker_default_country', sanitize_text_field($_POST['gunbroker_default_country']));
    // Product-specific listing options have been moved to the product edit page
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}
?>

<div class="wrap">
    <h1>GunBroker Settings</h1>

    <?php settings_errors('gunbroker_settings'); ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Main Settings -->
        <div>
            <form method="post" action="">
                <?php wp_nonce_field('gunbroker_settings', 'gunbroker_settings_nonce'); ?>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">API Credentials</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gunbroker_dev_key">Developer Key</label>
                            </th>
                            <td>
                                <input type="text" id="gunbroker_dev_key" name="gunbroker_dev_key"
                                       value="<?php echo esc_attr(get_option('gunbroker_dev_key')); ?>"
                                       class="regular-text" />
                                <p class="description">
                                    Get your key from <a href="https://api.gunbroker.com/User/DevKey/Create" target="_blank">GunBroker API</a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="gunbroker_username">GunBroker Username</label>
                            </th>
                            <td>
                                <input type="text" id="gunbroker_username" name="gunbroker_username"
                                       value="<?php echo esc_attr(get_option('gunbroker_username')); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="gunbroker_password">GunBroker Password</label>
                            </th>
                            <td>
                                <input type="password" id="gunbroker_password" name="gunbroker_password"
                                       value="<?php echo esc_attr(get_option('gunbroker_password')); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Inventory & Defaults</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gunbroker_markup_percentage">Global Price Markup</label>
                            </th>
                            <td>
                                <input type="number" id="gunbroker_markup_percentage" name="gunbroker_markup_percentage"
                                       value="<?php echo esc_attr(get_option('gunbroker_markup_percentage', 10)); ?>"
                                       min="0" max="500" step="0.1" class="small-text" />
                                <span>% (Applied to all listings by default)</span>
                                <p class="description">Products can override this per item.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Inventory Management</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gunbroker_auto_end_zero_stock" value="1"
                                        <?php checked(get_option('gunbroker_auto_end_zero_stock', true)); ?> />
                                    Automatically end listings when product is out of stock
                                </label>
                                <p class="description">Recommended to prevent overselling</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="gunbroker_default_country">Default Country Code</label>
                            </th>
                            <td>
                                <input type="text" id="gunbroker_default_country" name="gunbroker_default_country"
                                       value="<?php echo esc_attr(strtoupper(substr((string) get_option('gunbroker_default_country', 'US'),0,2))); ?>"
                                       class="regular-text" style="width:80px; text-transform:uppercase;" maxlength="2" />
                                <p class="description">2-letter ISO code (e.g. US, CA). Used when product does not override.</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">Public Domain for Images</th>
                            <td>
                                <input type="text" name="gunbroker_public_domain" value="<?php echo esc_attr(get_option('gunbroker_public_domain', '')); ?>" 
                                       placeholder="your-domain.com" class="regular-text" />
                                <p class="description">
                                    Enter your public domain (e.g., your-domain.com) for image URLs. 
                                    Required for GunBroker to access product images. Leave empty to skip images in localhost environment.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h2 style="margin-top: 0;">Advanced Options</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Mode</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gunbroker_sandbox_mode" value="1"
                                        <?php checked(get_option('gunbroker_sandbox_mode', true)); ?> />
                                    Sandbox/Test Mode
                                </label>
                                <p class="description">
                                    <strong style="color: #d63638;">Important:</strong> Uncheck this when you're ready to create live listings
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>

            <!-- DEBUG TOOLS -->
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0;">
                <h3>Debug Tools</h3>
                <button type="button" id="debug-credentials" class="button">Check Stored Credentials</button>
                <button type="button" id="test-raw-auth" class="button">Test Raw Authentication</button>

                <div id="debug-results" style="margin-top: 15px; font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">
                    Click a button above to see debug information...
                </div>
            </div>

            <!-- CACHE MANAGEMENT -->
            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 20px; margin: 20px 0;">
                <h3>Category Cache Management</h3>
                <p style="margin: 0 0 15px 0; color: #666;">
                    Categories are cached for 24 hours to improve performance. Clear cache if you need fresh data.
                </p>
                <button type="button" id="clear-category-cache" class="button button-secondary">Clear Category Cache</button>
                <div id="cache-results" style="margin-top: 15px; font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; display: none;">
                    <!-- Cache results will appear here -->
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">Connection Status</h3>
                <?php
                $settings = new GunBroker_Settings();
                $api = new GunBroker_API();
                ?>

                <p><strong>Configuration:</strong>
                    <?php if ($settings->is_configured()): ?>
                        <span style="color: green;">✓ Complete</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Incomplete</span>
                    <?php endif; ?>
                </p>

                <p><strong>API Connection:</strong>
                    <span id="connection-status">Testing...</span>
                </p>

                <button type="button" id="test-connection" class="button button-secondary" style="width: 100%;">
                    Test Connection
                </button>

                <div id="connection-result" style="margin-top: 10px;"></div>
            </div>

            <div style="background: #f0f8ff; border: 1px solid #b8daff; padding: 20px;">
                <h3 style="margin-top: 0; color: #0066cc;">Quick Start</h3>
                <ol style="margin: 0; padding-left: 20px;">
                    <li>Enter your API credentials above</li>
                    <li>Test the connection</li>
                    <li>Go to <strong>List Products</strong> to start listing</li>
                    <li>Monitor orders in <strong>Orders</strong> page</li>
                </ol>

                <div style="margin-top: 15px; padding: 10px; background: rgba(0,102,204,0.1); border-radius: 4px;">
                    <strong>Need help?</strong><br>
                    <a href="<?php echo admin_url('admin.php?page=gunbroker-help'); ?>">View setup guide →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        console.log('GunBroker Settings page loaded');

        // Test connection on page load
        testConnection();

        $('#test-connection').click(function() {
            testConnection();
        });

        function testConnection() {
            const $status = $('#connection-status');
            const $button = $('#test-connection');
            const $result = $('#connection-result');

            $status.html('<span style="color: #666;">Testing...</span>');
            $button.prop('disabled', true).text('Testing...');
            $result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_connection',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span style="color: green;">✓ Connected</span>');
                        $result.html('<div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-top: 10px;">Connection successful! You can now list products.</div>');
                    } else {
                        $status.html('<span style="color: red;">✗ Failed</span>');
                        $result.html('<div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-top: 10px;">Error: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: red;">✗ Error</span>');
                    $result.html('<div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-top: 10px;">Network error occurred</div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        }

        // Debug buttons
        $('#debug-credentials').click(function() {
            console.log('Debug credentials clicked');
            $('#debug-results').html('Loading credentials...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_debug_credentials',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Debug credentials response:', response);
                    $('#debug-results').html('<h4>Stored Credentials:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.log('Debug credentials error:', error);
                    $('#debug-results').html('<div style="color: red;">Error: ' + error + '</div>');
                }
            });
        });

        $('#test-raw-auth').click(function() {
            console.log('Test raw auth clicked');
            var button = $(this);
            button.prop('disabled', true).text('Testing...');
            $('#debug-results').html('Testing authentication...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_raw_auth',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Test raw auth response:', response);
                    $('#debug-results').html('<h4>Authentication Test Results:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.log('Test raw auth error:', error);
                    $('#debug-results').html('<div style="color: red;">Error: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Raw Authentication');
                }
            });
        });

        $('#clear-category-cache').click(function() {
            clearCategoryCache();
        });

        function clearCategoryCache() {
            console.log('Clear category cache clicked');
            var button = $('#clear-category-cache');
            var $results = $('#cache-results');
            
            button.prop('disabled', true).text('Clearing...');
            $results.show().html('Clearing category cache...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_clear_category_cache',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Clear cache response:', response);
                    if (response.success) {
                        $results.html('<div style="color: green;">✓ ' + response.data + '</div>');
                    } else {
                        $results.html('<div style="color: red;">✗ Error: ' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Clear cache error:', error);
                    $results.html('<div style="color: red;">✗ Error: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Clear Category Cache');
                }
            });
        }

    });
</script>