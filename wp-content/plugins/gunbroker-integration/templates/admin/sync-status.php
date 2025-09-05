<div class="wrap">
    <h1>GunBroker Sync Status</h1>

    <!-- Statistics Cards -->
    <div class="gunbroker-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">

        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Products Enabled</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;"><?php echo esc_html($total_products); ?></p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Products with GunBroker sync enabled</p>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Active Listings</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #46b450;"><?php echo esc_html($active_listings); ?></p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Successfully synced to GunBroker</p>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Pending Sync</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #ffb900;"><?php echo esc_html($pending_listings); ?></p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Waiting to be processed</p>
        </div>

        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Errors</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #dc3232;"><?php echo esc_html($error_listings); ?></p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Products with sync errors</p>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
        <h2>Bulk Actions</h2>
        <p>Perform actions on multiple products at once:</p>

        <button type="button" class="button button-primary" id="sync-all-products">
            Sync All Enabled Products
        </button>

        <button type="button" class="button" id="check-all-status">
            Check All Listing Status
        </button>

        <button type="button" class="button button-secondary" id="clear-error-logs">
            Clear Error Logs
        </button>

        <div id="bulk-action-result" style="margin-top: 15px;"></div>
    </div>

    <!-- Debug Information -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
            <h2>Debug Information</h2>

            <?php
            $api = new GunBroker_API();
            $settings = new GunBroker_Settings();
            ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3>API Configuration</h3>
                    <ul style="font-family: monospace; font-size: 12px;">
                        <li><strong>Base URL:</strong> <?php echo esc_html($api->get_base_url()); ?></li>
                        <li><strong>Dev Key:</strong> <?php echo esc_html(substr(get_option('gunbroker_dev_key'), 0, 8) . '...'); ?></li>
                        <li><strong>Has Access Token:</strong> <?php echo get_option('gunbroker_access_token') ? 'Yes' : 'No'; ?></li>
                        <li><strong>Sandbox Mode:</strong> <?php echo get_option('gunbroker_sandbox_mode') ? 'Yes' : 'No'; ?></li>
                        <li><strong>SSL Verify:</strong> Disabled (Local Dev)</li>
                    </ul>
                </div>

                <div>
                    <h3>Recent Debug Logs</h3>
                    <div style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 11px;">
                        <?php
                        $debug_log = ABSPATH . 'wp-content/debug.log';
                        if (file_exists($debug_log)) {
                            $log_content = file_get_contents($debug_log);
                            if ($log_content) {
                                $log_lines = explode("\n", $log_content);
                                $gunbroker_lines = array_filter($log_lines, function($line) {
                                    return strpos($line, 'GunBroker') !== false;
                                });
                                $recent_lines = array_slice(array_reverse($gunbroker_lines), 0, 15);

                                if (!empty($recent_lines)) {
                                    foreach ($recent_lines as $line) {
                                        $line = trim($line);
                                        if (!empty($line)) {
                                            echo '<div style="margin-bottom: 2px; word-break: break-all;">' . esc_html($line) . '</div>';
                                        }
                                    }
                                } else {
                                    echo '<div style="color: #666;">No GunBroker debug logs found</div>';
                                }
                            } else {
                                echo '<div style="color: #666;">Debug log is empty</div>';
                            }
                        } else {
                            echo '<div style="color: #666;">Debug log file not found at: ' . esc_html($debug_log) . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="button" class="button" onclick="location.reload()">Refresh Debug Info</button>
                <button type="button" class="button" id="test-listing">Test Connection from Here</button>
            </div>

            <script>
                jQuery('#test-listing').click(function() {
                    var button = $(this);
                    button.prop('disabled', true).text('Testing...');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'gunbroker_test_connection',
                            nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✓ Connection successful: ' + response.data);
                            } else {
                                alert('✗ Connection failed: ' + response.data);
                            }
                            location.reload();
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Test Connection from Here');
                        }
                    });
                });
            </script>
        </div>
    <?php endif; ?>

    <!-- Recent Activity Log -->
    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
        <h2>Recent Activity</h2>

        <?php if (empty($recent_logs)): ?>
            <p>No sync activity yet. Enable GunBroker sync on some products to see activity here.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Product</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date('M j, Y g:i A', strtotime($log->timestamp))); ?></td>
                        <td>
                            <?php
                            if ($log->listing_id) {
                                global $wpdb;
                                $product_id = $wpdb->get_var($wpdb->prepare(
                                    "SELECT product_id FROM {$wpdb->prefix}gunbroker_listings WHERE gunbroker_id = %s",
                                    $log->listing_id
                                ));

                                if ($product_id) {
                                    $product = wc_get_product($product_id);
                                    if ($product) {
                                        echo '<a href="' . esc_url(get_edit_post_link($product_id)) . '">' . esc_html($product->get_name()) . '</a>';
                                    } else {
                                        echo 'Product #' . esc_html($product_id);
                                    }
                                } else {
                                    echo 'Unknown Product';
                                }
                            } else {
                                echo 'System';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(ucfirst($log->action)); ?></td>
                        <td>
                                <span class="gunbroker-status-<?php echo esc_attr($log->status); ?>">
                                    <?php echo esc_html(ucfirst($log->status)); ?>
                                </span>
                        </td>
                        <td><?php echo esc_html($log->message); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Bulk sync all products
        $('#sync-all-products').click(function() {
            var button = $(this);
            var result = $('#bulk-action-result');

            button.prop('disabled', true).text('Syncing...');
            result.html('<div class="notice notice-info"><p>Starting bulk sync...</p></div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_bulk_sync',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        // Refresh page after 3 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    result.html('<div class="notice notice-error"><p>Bulk sync failed</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Sync All Enabled Products');
                }
            });
        });

        // Clear error logs
        $('#clear-error-logs').click(function() {
            if (!confirm('Are you sure you want to clear all error logs?')) {
                return;
            }

            var button = $(this);
            var result = $('#bulk-action-result');

            button.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_clear_logs',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        result.html('<div class="notice notice-success"><p>Error logs cleared</p></div>');
                        location.reload();
                    } else {
                        result.html('<div class="notice notice-error"><p>Failed to clear logs</p></div>');
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text('Clear Error Logs');
                }
            });
        });
    });
</script>