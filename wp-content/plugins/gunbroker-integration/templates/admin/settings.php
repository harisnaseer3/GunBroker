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
                    <h2 style="margin-top: 0;">Default Listing Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gunbroker_markup_percentage">Global Price Markup</label>
                            </th>
                            <td>
                                <input type="number" id="gunbroker_markup_percentage" name="gunbroker_markup_percentage"
                                       value="<?php echo esc_attr(get_option('gunbroker_markup_percentage', 10)); ?>"
                                       min="0" max="500" step="0.1" class="small-text" />
                                <span>% (Applied to all listings)</span>
                                <p class="description">Add this percentage to your WooCommerce price for GunBroker listings</p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="gunbroker_listing_duration">Default Listing Duration</label>
                            </th>
                            <td>
                                <select id="gunbroker_listing_duration" name="gunbroker_listing_duration">
                                    <option value="1" <?php selected(get_option('gunbroker_listing_duration', 7), 1); ?>>1 Day</option>
                                    <option value="3" <?php selected(get_option('gunbroker_listing_duration', 7), 3); ?>>3 Days</option>
                                    <option value="5" <?php selected(get_option('gunbroker_listing_duration', 7), 5); ?>>5 Days</option>
                                    <option value="7" <?php selected(get_option('gunbroker_listing_duration', 7), 7); ?>>7 Days</option>
                                    <option value="10" <?php selected(get_option('gunbroker_listing_duration', 7), 10); ?>>10 Days</option>
                                </select>
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

                        <tr>
                            <th scope="row">Order Import</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gunbroker_import_orders" value="1"
                                        <?php checked(get_option('gunbroker_import_orders', false)); ?> />
                                    Import orders as WooCommerce orders (not recommended)
                                </label>
                                <p class="description">
                                    Leave unchecked to view orders in GunBroker → Orders page without creating WooCommerce orders
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>
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
    });
</script><div class="wrap">
    <h1>GunBroker Integration Settings</h1>

    <?php settings_errors('gunbroker_settings'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('gunbroker_settings', 'gunbroker_settings_nonce'); ?>

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
                        Get your Developer Key from <a href="https://api.gunbroker.com/User/DevKey/Create" target="_blank">GunBroker API</a>
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

            <tr>
                <th scope="row">
                    <label for="gunbroker_markup_percentage">Markup Percentage</label>
                </th>
                <td>
                    <input type="number" id="gunbroker_markup_percentage" name="gunbroker_markup_percentage"
                           value="<?php echo esc_attr(get_option('gunbroker_markup_percentage', 10)); ?>"
                           min="0" max="500" step="0.1" class="small-text" />
                    <span>%</span>
                    <p class="description">Percentage to add to WooCommerce price for GunBroker listing</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="gunbroker_listing_duration">Listing Duration</label>
                </th>
                <td>
                    <select id="gunbroker_listing_duration" name="gunbroker_listing_duration">
                        <option value="1" <?php selected(get_option('gunbroker_listing_duration', 7), 1); ?>>1 Day</option>
                        <option value="3" <?php selected(get_option('gunbroker_listing_duration', 7), 3); ?>>3 Days</option>
                        <option value="5" <?php selected(get_option('gunbroker_listing_duration', 7), 5); ?>>5 Days</option>
                        <option value="7" <?php selected(get_option('gunbroker_listing_duration', 7), 7); ?>>7 Days</option>
                        <option value="10" <?php selected(get_option('gunbroker_listing_duration', 7), 10); ?>>10 Days</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="gunbroker_sandbox_mode">Sandbox Mode</label>
                </th>
                <td>
                    <input type="checkbox" id="gunbroker_sandbox_mode" name="gunbroker_sandbox_mode"
                           value="1" <?php checked(get_option('gunbroker_sandbox_mode', true)); ?> />
                    <label for="gunbroker_sandbox_mode">Enable sandbox mode for testing</label>
                    <p class="description">Uncheck this when you're ready to go live</p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <hr>

    <h2>Connection Test</h2>
    <p>Test your API connection:</p>
    <button type="button" id="test-connection" class="button">Test Connection</button>
    <div id="connection-result" style="margin-top: 10px;"></div>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#test-connection').click(function() {
            var button = $(this);
            var result = $('#connection-result');

            button.prop('disabled', true).text('Testing...');
            result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_connection',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    result.html('<div class="notice notice-error"><p>Connection test failed</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        });
    });
</script>