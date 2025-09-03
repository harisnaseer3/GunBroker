<div class="wrap">
    <h1>GunBroker Integration Help</h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Main content -->
        <div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h2>Getting Started</h2>
                <ol>
                    <li><strong>Get GunBroker API Credentials:</strong>
                        <ul>
                            <li>Visit <a href="https://api.gunbroker.com/User/DevKey/Create" target="_blank">GunBroker Developer Key Request</a></li>
                            <li>Fill out the application form</li>
                            <li>Wait for approval (typically 1-2 weeks)</li>
                        </ul>
                    </li>
                    <li><strong>Configure Plugin:</strong>
                        <ul>
                            <li>Go to GunBroker → Settings</li>
                            <li>Enter your Developer Key</li>
                            <li>Enter your GunBroker username and password</li>
                            <li>Test the connection</li>
                        </ul>
                    </li>
                    <li><strong>Enable Products:</strong>
                        <ul>
                            <li>Edit any WooCommerce product</li>
                            <li>Scroll to "GunBroker Integration" section</li>
                            <li>Check "Enable GunBroker Sync"</li>
                            <li>Save the product</li>
                        </ul>
                    </li>
                </ol>
            </div>

            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h2>How Synchronization Works</h2>
                <h3>Automatic Sync</h3>
                <ul>
                    <li><strong>Product Updates:</strong> When you update a WooCommerce product, it automatically syncs to GunBroker</li>
                    <li><strong>Stock Changes:</strong> Inventory levels sync in real-time</li>
                    <li><strong>Scheduled Sync:</strong> All products sync every 2 hours automatically</li>
                </ul>

                <h3>Manual Sync</h3>
                <ul>
                    <li><strong>Single Product:</strong> Click "Sync Now" in the product edit page</li>
                    <li><strong>Bulk Sync:</strong> Use "Sync All Products" in the Sync Status page</li>
                    <li><strong>Quick Sync:</strong> Click "Sync" in the Products list</li>
                </ul>

                <h3>Pricing</h3>
                <p>The plugin adds a markup percentage to your WooCommerce price:</p>
                <ul>
                    <li>WooCommerce Price: $500</li>
                    <li>Markup: 10%</li>
                    <li>GunBroker Price: $550</li>
                </ul>
            </div>

            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h2>Troubleshooting</h2>

                <h3>Common Issues</h3>
                <dl>
                    <dt><strong>Authentication Failed</strong></dt>
                    <dd>
                        <ul>
                            <li>Double-check your Developer Key</li>
                            <li>Verify your GunBroker username/password</li>
                            <li>Make sure your GunBroker account is active</li>
                            <li>Try the "Test Connection" button</li>
                        </ul>
                    </dd>

                    <dt><strong>Products Not Syncing</strong></dt>
                    <dd>
                        <ul>
                            <li>Ensure "Enable GunBroker Sync" is checked on the product</li>
                            <li>Check the Sync Status page for errors</li>
                            <li>Try manual sync on the product</li>
                            <li>Verify your GunBroker account can create listings</li>
                        </ul>
                    </dd>

                    <dt><strong>Out of Stock Issues</strong></dt>
                    <dd>
                        <ul>
                            <li>The plugin automatically ends listings when stock reaches 0</li>
                            <li>Listings resume when stock is replenished</li>
                            <li>Check your WooCommerce stock management settings</li>
                        </ul>
                    </dd>
                </dl>
            </div>

            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                <h2>Advanced Settings</h2>

                <h3>Product-Level Settings</h3>
                <ul>
                    <li><strong>Custom Title:</strong> Override the product title for GunBroker</li>
                    <li><strong>Category:</strong> Select the appropriate GunBroker category</li>
                    <li><strong>Listing Duration:</strong> How long the listing stays active</li>
                </ul>

                <h3>Global Settings</h3>
                <ul>
                    <li><strong>Markup Percentage:</strong> Default markup for all products</li>
                    <li><strong>Listing Duration:</strong> How long listings stay active (1-10 days)</li>
                    <li><strong>Sandbox Mode:</strong> Test mode for development (disable for live listings)</li>
                    <li><strong>Auto-End on Zero Stock:</strong> Automatically end listings when out of stock</li>
                </ul>

                <h3>Bulk Operations</h3>
                <ul>
                    <li><strong>Sync All:</strong> Sync all enabled products at once</li>
                    <li><strong>Check Status:</strong> Verify all listing statuses on GunBroker</li>
                    <li><strong>Clear Logs:</strong> Remove old error logs</li>
                </ul>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h3>Quick Links</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><a href="<?php echo admin_url('admin.php?page=gunbroker-integration'); ?>">Settings</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=gunbroker-sync-status'); ?>">Sync Status</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=product'); ?>">Products</a></li>
                </ul>

                <h3>External Links</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><a href="https://api.gunbroker.com/User/DevKey/Create" target="_blank">Request Developer Key</a></li>
                    <li><a href="https://www.gunbroker.com" target="_blank">GunBroker.com</a></li>
                    <li><a href="https://support.gunbroker.com" target="_blank">GunBroker Support</a></li>
                </ul>
            </div>

            <div style="background: #f8f9fa; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h3>System Status</h3>
                <?php
                $api = new GunBroker_API();
                $settings = new GunBroker_Settings();
                ?>

                <p><strong>Plugin Version:</strong> <?php echo GUNBROKER_VERSION; ?></p>
                <p><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></p>
                <p><strong>WooCommerce:</strong> <?php echo defined('WC_VERSION') ? WC_VERSION : 'Not installed'; ?></p>
                <p><strong>API Configured:</strong>
                    <?php if ($settings->is_configured()): ?>
                        <span style="color: green;">✓ Yes</span>
                    <?php else: ?>
                        <span style="color: red;">✗ No</span>
                    <?php endif; ?>
                </p>
                <p><strong>Connection Status:</strong>
                    <?php if ($settings->is_configured() && $api->test_connection()): ?>
                        <span style="color: green;">✓ Connected</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Not Connected</span>
                    <?php endif; ?>
                </p>

                <?php
                global $wpdb;
                $enabled_products = $wpdb->get_var("
                    SELECT COUNT(*) FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_gunbroker_enabled' AND meta_value = 'yes'
                ");
                $active_listings = $wpdb->get_var("
                    SELECT COUNT(*) FROM {$wpdb->prefix}gunbroker_listings 
                    WHERE status = 'active'
                ");
                ?>

                <p><strong>Enabled Products:</strong> <?php echo intval($enabled_products); ?></p>
                <p><strong>Active Listings:</strong> <?php echo intval($active_listings); ?></p>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin-bottom: 20px;">
                <h3>💡 Pro Tips</h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                    <li>Use descriptive product titles for better GunBroker visibility</li>
                    <li>High-quality images improve listing performance</li>
                    <li>Keep product descriptions detailed and accurate</li>
                    <li>Monitor the sync status regularly</li>
                    <li>Test with sandbox mode before going live</li>
                </ul>
            </div>

            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px;">
                <h3>⚠️ Important Notes</h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                    <li>Always comply with federal and state firearm laws</li>
                    <li>Verify FFL requirements for your products</li>
                    <li>GunBroker has specific category requirements</li>
                    <li>Test thoroughly before enabling on live products</li>
                    <li>Keep your GunBroker account in good standing</li>
                </ul>
            </div>
        </div>
    </div>
</div>