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
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>

            <!-- DEBUG SECTION -->
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0;">
                <h3>Debug Tools</h3>
                <button type="button" id="debug-credentials" class="button">Check Stored Credentials</button>
                <button type="button" id="test-raw-auth" class="button">Test Raw Authentication</button>
                <button type="button" id="test-endpoints" class="button">Test API Endpoints</button>
                <button type="button" id="test-official-endpoints" class="button">Test Official Endpoints</button>
                <button type="button" id="show-recent-logs" class="button">Show Recent Logs</button>
                <button type="button" id="test-product-endpoints" class="button">Test Product Fetch Endpoints</button>
                <button type="button" id="discover-endpoints" class="button button-primary">🔍 Discover Working Endpoints</button>
                <button type="button" id="test-all-endpoints" class="button button-primary">🔥 Test All Product Endpoints</button>

                <div id="debug-results" style="margin-top: 15px; font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">
                    Click a button above to see debug information...
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

        // Test API endpoints
        $('#test-endpoints').click(function() {
            console.log('Test endpoints clicked');
            $(this).prop('disabled', true).text('Testing...');
            $('#debug-results').html('Testing endpoints...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_endpoints',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Test endpoints response:', response);
                    $('#debug-results').html('<h4>Endpoint Test Results:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.log('Test endpoints error:', error);
                    $('#debug-results').html('<div style="color: red;">Error: ' + error + '</div>');
                },
                complete: function() {
                    $(this).prop('disabled', false).text('Test API Endpoints');
                }
            });
        });

        $('#test-official-endpoints').click(function() {
            $(this).prop('disabled', true).text('Testing...');
            $('#debug-results').html('Testing official endpoints...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_official_endpoints',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    $('#debug-results').html('<h4>Official Endpoints Results:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                complete: function() {
                    $(this).prop('disabled', false).text('Test Official Endpoints');
                }
            });
        });

        $('#show-recent-logs').click(function() {
            $('#debug-results').html('Loading recent logs...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_show_logs',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    $('#debug-results').html('<h4>Recent GunBroker Logs:</h4><pre>' + response.data + '</pre>');
                }
            });
        });

        $('#test-product-endpoints').click(function() {
            $(this).prop('disabled', true).text('Testing...');
            $('#debug-results').html('Testing product endpoints...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_product_endpoints',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    $('#debug-results').html('<h4>Product Endpoints Results:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                complete: function() {
                    $(this).prop('disabled', false).text('Test Product Fetch Endpoints');
                }
            });
        });

        $('#discover-endpoints').click(function() {
            var button = $(this);
            button.prop('disabled', true).text('🔍 Discovering... (may take 2-3 minutes)');
            $('#debug-results').html('Running comprehensive endpoint discovery...<br><br>This will test many different API endpoints to find which ones work for fetching GunBroker products.<br><br>Please wait...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_discover_endpoints',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                timeout: 180000, // 3 minutes timeout
                success: function(response) {
                    if (response.success) {
                        var html = '<h4>🎉 Endpoint Discovery Results:</h4>';
                        html += '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 4px;">';
                        html += '<strong>' + response.data.summary + '</strong>';
                        html += '</div>';

                        if (response.data.working_endpoints.length > 0) {
                            html += '<h5>✅ Working Endpoints:</h5>';
                            html += '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
                            response.data.working_endpoints.forEach(function(endpoint) {
                                html += '<div style="margin: 5px 0; padding: 8px; background: white; border-left: 4px solid #28a745;">';
                                html += '<strong>' + endpoint.endpoint + '</strong><br>';
                                html += 'Works without auth: ' + (endpoint.works_no_auth ? '✅ Yes' : '❌ No') + '<br>';
                                html += 'Works with auth: ' + (endpoint.works_with_auth ? '✅ Yes' : '❌ No') + '<br>';
                                if (endpoint.data_preview) {
                                    html += '<details><summary>Data Preview</summary><pre style="font-size: 10px; overflow: auto; max-height: 100px;">' + endpoint.data_preview + '</pre></details>';
                                }
                                html += '</div>';
                            });
                            html += '</div>';
                        } else {
                            html += '<div style="background: #f8d7da; padding: 10px; border-radius: 4px; color: #721c24;">';
                            html += '❌ No working endpoints found. This indicates your API key may have very limited permissions.';
                            html += '</div>';
                        }

                        html += '<details style="margin-top: 20px;"><summary>View All Test Results</summary>';
                        html += '<pre style="font-size: 10px; max-height: 300px; overflow: auto;">' + JSON.stringify(response.data.all_results, null, 2) + '</pre>';
                        html += '</details>';

                        $('#debug-results').html(html);
                    } else {
                        $('#debug-results').html('<div style="color: red;">Error: ' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#debug-results').html('<div style="color: red;">Network error: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('🔍 Discover Working Endpoints');
                }
            });
        });

        $('#test-all-endpoints').click(function() {
            var button = $(this);
            button.prop('disabled', true).text('🔥 Testing All Endpoints...');
            $('#debug-results').html('Testing all product fetching endpoints based on API documentation...<br><br>This will test:<br>• Items (search)<br>• ItemsSelling (user listings)<br>• ItemsSold (sold items)<br>• ItemsEnded (ended listings)<br>• Categories<br><br>Please wait...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_all_endpoints',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                timeout: 60000, // 1 minute timeout
                success: function(response) {
                    if (response.success) {
                        var html = '<h4>📊 Comprehensive Endpoint Test Results:</h4>';
                        html += '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 4px;">';
                        html += '<strong>Summary: ' + response.data.summary.working + '/' + response.data.summary.total_tested + ' endpoints working (' + response.data.summary.success_rate + ')</strong>';
                        html += '</div>';

                        html += '<h5>📋 Detailed Results:</h5>';
                        html += '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';

                        Object.keys(response.data.results).forEach(function(endpoint) {
                            var result = response.data.results[endpoint];
                            var status = result.success ? '✅' : '❌';
                            var bgColor = result.success ? '#d4edda' : '#f8d7da';

                            html += '<div style="margin: 8px 0; padding: 10px; background: ' + bgColor + '; border-radius: 4px;">';
                            html += '<strong>' + status + ' ' + endpoint + '</strong><br>';
                            html += '<em>' + result.description + '</em><br>';

                            if (result.success) {
                                html += 'Status: SUCCESS';
                                if (result.has_results) {
                                    html += ' (' + result.result_count + ' results returned)';
                                }
                                if (result.data_preview) {
                                    html += '<br><details><summary>Data Preview</summary><pre style="font-size: 10px; overflow: auto; max-height: 100px;">' + result.data_preview + '</pre></details>';
                                }
                            } else {
                                html += 'Status: FAILED<br>';
                                html += 'Error: ' + (result.error || 'Unknown error');
                            }

                            html += '</div>';
                        });
                        html += '</div>';

                        if (response.data.summary.working >= response.data.summary.total_tested - 1) {
                            html += '<div style="background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 4px; color: #155724;">';
                            html += '<strong>🎉 Excellent! Your API credentials have full access to product endpoints.</strong><br>';
                            html += 'Your Browse GunBroker and Orders pages should work perfectly now!';
                            html += '</div>';
                        } else {
                            html += '<div style="background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 4px; color: #856404;">';
                            html += '<strong>⚠️ Some endpoints failed.</strong> Your plugin will work but with limited functionality.';
                            html += '</div>';
                        }

                        $('#debug-results').html(html);
                    } else {
                        $('#debug-results').html('<div style="color: red;">Error: ' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#debug-results').html('<div style="color: red;">Network error: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('🔥 Test All Product Endpoints');
                }
            });
        });
    });
</script>