<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Get current settings (we'll implement a settings system)
$settings = get_option('tajmap_pb_settings', []);
$default_settings = [
    'company_name' => get_bloginfo('name'),
    'company_email' => get_bloginfo('admin_email'),
    'company_phone' => '',
    'development_name' => 'Premium Development',
    'default_currency' => 'Rs.',
    'email_notifications' => true,
    'sms_notifications' => false,
    'auto_assign_leads' => false,
    'require_registration' => false,
    'map_tile_server' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    'google_maps_api_key' => '',
    'default_zoom_level' => 15,
    'max_plot_area' => 5000,
    'min_plot_area' => 500,
    'measurement_units' => 'sqft',
    'backup_frequency' => 'daily',
    'data_retention_days' => 365,
    'enable_analytics' => true,
    'enable_lead_scoring' => false,
    'lead_scoring_weights' => [
        'email' => 10,
        'phone' => 15,
        'message' => 20,
        'plot_interest' => 25,
        'budget_fit' => 30
    ]
];

// Merge with saved settings
$settings = array_merge($default_settings, $settings);
?>
<div class="wrap tajmap-settings">
    <div class="settings-header">
        <h1>Settings & Configuration</h1>
        <div class="settings-actions">
            <button class="btn primary" onclick="saveSettings()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
                Save All Settings
            </button>
            <button class="btn secondary" onclick="exportSettings()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export Settings
            </button>
            <button class="btn danger" onclick="resetSettings()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6l3 3m0-3L3 3"></path>
                    <path d="M21 6l-3-3m3 3l3-3"></path>
                    <path d="M9 6v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6"></path>
                </svg>
                Reset to Default
            </button>
        </div>
    </div>

    <form id="tajmap-settings-form" class="settings-form">
        <!-- Company Information -->
        <div class="settings-section">
            <div class="section-header">
                <h2>Company Information</h2>
                <p>Basic information about your development company</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="company-name">Company Name *</label>
                    <input type="text" id="company-name" name="company_name" value="<?php echo esc_attr($settings['company_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="company-email">Company Email *</label>
                    <input type="email" id="company-email" name="company_email" value="<?php echo esc_attr($settings['company_email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="company-phone">Company Phone</label>
                    <input type="tel" id="company-phone" name="company_phone" value="<?php echo esc_attr($settings['company_phone']); ?>">
                </div>

                <div class="form-group">
                    <label for="development-name">Development Name *</label>
                    <input type="text" id="development-name" name="development_name" value="<?php echo esc_attr($settings['development_name']); ?>" required>
                </div>
            </div>
        </div>

        <!-- Development Settings -->
        <div class="settings-section">
            <div class="section-header">
                <h2>Development Configuration</h2>
                <p>Configure your plot development settings</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="default-currency">Default Currency</label>
                    <select id="default-currency" name="default_currency">
                        <option value="Rs." <?php selected($settings['default_currency'], 'Rs.'); ?>>Rs. (PKR)</option>
                        <option value="$" <?php selected($settings['default_currency'], '$'); ?>>$ (USD)</option>
                        <option value="€" <?php selected($settings['default_currency'], '€'); ?>>€ (EUR)</option>
                        <option value="£" <?php selected($settings['default_currency'], '£'); ?>>£ (GBP)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="measurement-units">Measurement Units</label>
                    <select id="measurement-units" name="measurement_units">
                        <option value="sqft" <?php selected($settings['measurement_units'], 'sqft'); ?>>Square Feet</option>
                        <option value="sqm" <?php selected($settings['measurement_units'], 'sqm'); ?>>Square Meters</option>
                        <option value="acres" <?php selected($settings['measurement_units'], 'acres'); ?>>Acres</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="min-plot-area">Minimum Plot Area</label>
                    <input type="number" id="min-plot-area" name="min_plot_area" value="<?php echo esc_attr($settings['min_plot_area']); ?>" min="100" step="50">
                </div>

                <div class="form-group">
                    <label for="max-plot-area">Maximum Plot Area</label>
                    <input type="number" id="max-plot-area" name="max_plot_area" value="<?php echo esc_attr($settings['max_plot_area']); ?>" min="1000" step="100">
                </div>
            </div>
        </div>

        <!-- Map Configuration -->
        <div class="settings-section">
            <div class="section-header">
                <h2>Map & Visualization</h2>
                <p>Configure map display and visualization settings</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="map-tile-server">Map Tile Server URL</label>
                    <input type="url" id="map-tile-server" name="map_tile_server" value="<?php echo esc_attr($settings['map_tile_server']); ?>" placeholder="https://tile.openstreetmap.org/{z}/{x}/{y}.png">
                </div>

                <div class="form-group">
                    <label for="google-maps-key">Google Maps API Key</label>
                    <input type="text" id="google-maps-key" name="google_maps_api_key" value="<?php echo esc_attr($settings['google_maps_api_key']); ?>" placeholder="Optional - for enhanced mapping features">
                </div>

                <div class="form-group">
                    <label for="default-zoom">Default Zoom Level</label>
                    <select id="default-zoom" name="default_zoom_level">
                        <?php for ($i = 10; $i <= 20; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($settings['default_zoom_level'], $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="settings-section">
            <div class="section-header">
                <h2>Notifications & Communication</h2>
                <p>Configure how you want to be notified about leads and inquiries</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="email-notifications" name="email_notifications" <?php checked($settings['email_notifications']); ?>>
                        <span class="checkmark"></span>
                        Enable Email Notifications
                    </label>
                    <small>Receive email alerts for new leads and inquiries</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="sms-notifications" name="sms_notifications" <?php checked($settings['sms_notifications']); ?>>
                        <span class="checkmark"></span>
                        Enable SMS Notifications
                    </label>
                    <small>Receive SMS alerts for urgent inquiries</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="auto-assign-leads" name="auto_assign_leads" <?php checked($settings['auto_assign_leads']); ?>>
                        <span class="checkmark"></span>
                        Auto-assign Leads to Agents
                    </label>
                    <small>Automatically assign new leads to available agents</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="require-registration" name="require_registration" <?php checked($settings['require_registration']); ?>>
                        <span class="checkmark"></span>
                        Require User Registration
                    </label>
                    <small>Users must register before submitting inquiries</small>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="settings-section">
            <div class="section-header">
                <h2>Data Management</h2>
                <p>Configure data backup, retention, and privacy settings</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="backup-frequency">Backup Frequency</label>
                    <select id="backup-frequency" name="backup_frequency">
                        <option value="daily" <?php selected($settings['backup_frequency'], 'daily'); ?>>Daily</option>
                        <option value="weekly" <?php selected($settings['backup_frequency'], 'weekly'); ?>>Weekly</option>
                        <option value="monthly" <?php selected($settings['backup_frequency'], 'monthly'); ?>>Monthly</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data-retention">Data Retention (days)</label>
                    <input type="number" id="data-retention" name="data_retention_days" value="<?php echo esc_attr($settings['data_retention_days']); ?>" min="30" max="3650">
                    <small>How long to keep lead and inquiry data</small>
                </div>
            </div>
        </div>

        <!-- Advanced Features -->
        <div class="settings-section">
            <div class="section-header">
                <h2>Advanced Features</h2>
                <p>Configure advanced analytics and lead management features</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="enable-analytics" name="enable_analytics" <?php checked($settings['enable_analytics']); ?>>
                        <span class="checkmark"></span>
                        Enable Advanced Analytics
                    </label>
                    <small>Track detailed analytics and conversion metrics</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="enable-lead-scoring" name="enable_lead_scoring" <?php checked($settings['enable_lead_scoring']); ?>>
                        <span class="checkmark"></span>
                        Enable Lead Scoring
                    </label>
                    <small>Automatically score leads based on engagement and fit</small>
                </div>
            </div>

            <?php if ($settings['enable_lead_scoring']): ?>
            <div class="lead-scoring-settings" id="lead-scoring-settings">
                <h4>Lead Scoring Weights</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email-weight">Email Quality (0-50)</label>
                        <input type="number" id="email-weight" name="lead_scoring_weights[email]" value="<?php echo esc_attr($settings['lead_scoring_weights']['email']); ?>" min="0" max="50">
                    </div>

                    <div class="form-group">
                        <label for="phone-weight">Phone Provided (0-50)</label>
                        <input type="number" id="phone-weight" name="lead_scoring_weights[phone]" value="<?php echo esc_attr($settings['lead_scoring_weights']['phone']); ?>" min="0" max="50">
                    </div>

                    <div class="form-group">
                        <label for="message-weight">Message Quality (0-50)</label>
                        <input type="number" id="message-weight" name="lead_scoring_weights[message]" value="<?php echo esc_attr($settings['lead_scoring_weights']['message']); ?>" min="0" max="50">
                    </div>

                    <div class="form-group">
                        <label for="plot-interest-weight">Plot Interest Level (0-50)</label>
                        <input type="number" id="plot-interest-weight" name="lead_scoring_weights[plot_interest]" value="<?php echo esc_attr($settings['lead_scoring_weights']['plot_interest']); ?>" min="0" max="50">
                    </div>

                    <div class="form-group">
                        <label for="budget-fit-weight">Budget Fit (0-50)</label>
                        <input type="number" id="budget-fit-weight" name="lead_scoring_weights[budget_fit]" value="<?php echo esc_attr($settings['lead_scoring_weights']['budget_fit']); ?>" min="0" max="50">
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- API & Integration -->
        <div class="settings-section">
            <div class="section-header">
                <h2>API & Integrations</h2>
                <p>Configure external service integrations</p>
            </div>

            <div class="integration-grid">
                <div class="integration-item">
                    <div class="integration-header">
                        <h4>CRM Integration</h4>
                        <label class="toggle-switch">
                            <input type="checkbox" id="crm-integration" name="integrations[crm][enabled]" <?php checked(!empty($settings['integrations']['crm']['enabled'])); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="integration-content" id="crm-settings" style="<?php echo empty($settings['integrations']['crm']['enabled']) ? 'display: none;' : ''; ?>">
                        <div class="form-group">
                            <label for="crm-provider">CRM Provider</label>
                            <select id="crm-provider" name="integrations[crm][provider]">
                                <option value="hubspot" <?php selected($settings['integrations']['crm']['provider'], 'hubspot'); ?>>HubSpot</option>
                                <option value="salesforce" <?php selected($settings['integrations']['crm']['provider'], 'salesforce'); ?>>Salesforce</option>
                                <option value="pipedrive" <?php selected($settings['integrations']['crm']['provider'], 'pipedrive'); ?>>Pipedrive</option>
                                <option value="zoho" <?php selected($settings['integrations']['crm']['provider'], 'zoho'); ?>>Zoho CRM</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="crm-api-key">API Key</label>
                            <input type="password" id="crm-api-key" name="integrations[crm][api_key]" value="<?php echo esc_attr($settings['integrations']['crm']['api_key'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="integration-item">
                    <div class="integration-header">
                        <h4>Email Marketing</h4>
                        <label class="toggle-switch">
                            <input type="checkbox" id="email-marketing" name="integrations[email_marketing][enabled]" <?php checked(!empty($settings['integrations']['email_marketing']['enabled'])); ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="integration-content" id="email-marketing-settings" style="<?php echo empty($settings['integrations']['email_marketing']['enabled']) ? 'display: none;' : ''; ?>">
                        <div class="form-group">
                            <label for="email-provider">Email Provider</label>
                            <select id="email-provider" name="integrations[email_marketing][provider]">
                                <option value="mailchimp" <?php selected($settings['integrations']['email_marketing']['provider'], 'mailchimp'); ?>>Mailchimp</option>
                                <option value="sendinblue" <?php selected($settings['integrations']['email_marketing']['provider'], 'sendinblue'); ?>>Sendinblue</option>
                                <option value="convertkit" <?php selected($settings['integrations']['email_marketing']['provider'], 'convertkit'); ?>>ConvertKit</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="email-api-key">API Key</label>
                            <input type="password" id="email-api-key" name="integrations[email_marketing][api_key]" value="<?php echo esc_attr($settings['integrations']['email_marketing']['api_key'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Section -->
        <div class="settings-section">
            <div class="form-actions">
                <button type="button" class="btn primary large" onclick="saveSettings()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Save All Settings
                </button>
                <button type="button" class="btn secondary large" onclick="testConfiguration()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                        <path d="M9 7v4"></path>
                        <path d="M13 7l-4-4-4 4"></path>
                    </svg>
                    Test Configuration
                </button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">
(function($) {
    $(document).ready(function() {
        initializeSettings();
    });

    function initializeSettings() {
        // Toggle integration settings visibility
        $('#crm-integration, #email-marketing').on('change', function() {
            const target = $(this).closest('.integration-item').find('.integration-content');
            if ($(this).is(':checked')) {
                target.slideDown();
            } else {
                target.slideUp();
            }
        });

        // Toggle lead scoring settings
        $('#enable-lead-scoring').on('change', function() {
            const target = $('#lead-scoring-settings');
            if ($(this).is(':checked')) {
                target.slideDown();
            } else {
                target.slideUp();
            }
        });

        // Form validation
        $('#tajmap-settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }

    function saveSettings() {
        const formData = new FormData(document.getElementById('tajmap-settings-form'));
        const settings = {};

        // Convert form data to settings object
        for (let [key, value] of formData.entries()) {
            if (key.includes('[')) {
                // Handle nested objects like lead_scoring_weights[email]
                const matches = key.match(/^([^[]+)\[([^\]]+)\]$/);
                if (matches) {
                    if (!settings[matches[1]]) {
                        settings[matches[1]] = {};
                    }
                    settings[matches[1]][matches[2]] = value;
                }
            } else {
                settings[key] = value;
            }
        }

        // Also handle checkbox values
        $('input[type="checkbox"]').each(function() {
            const name = $(this).attr('name');
            if (name) {
                if (name.includes('[')) {
                    const matches = name.match(/^([^[]+)\[([^\]]+)\]$/);
                    if (matches) {
                        if (!settings[matches[1]]) {
                            settings[matches[1]] = {};
                        }
                        settings[matches[1]][matches[2]] = $(this).is(':checked') ? '1' : '0';
                    }
                } else {
                    settings[name] = $(this).is(':checked') ? '1' : '0';
                }
            }
        });

        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_save_settings',
            nonce: TajMapPB.nonce,
            settings: JSON.stringify(settings)
        }, function(response) {
            if (response.success) {
                showNotification('Settings saved successfully!', 'success');
            } else {
                showNotification('Failed to save settings. Please try again.', 'error');
            }
        });
    }

    function testConfiguration() {
        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_test_configuration',
            nonce: TajMapPB.nonce
        }, function(response) {
            if (response.success) {
                showNotification('Configuration test passed!', 'success');
            } else {
                showNotification('Configuration test failed: ' + response.data.message, 'error');
            }
        });
    }

    function exportSettings() {
        window.location.href = TajMapPB.ajaxUrl + '?action=tajmap_pb_export_settings&nonce=' + TajMapPB.nonce;
    }

    function resetSettings() {
        if (confirm('Are you sure you want to reset all settings to default? This cannot be undone.')) {
            $.post(TajMapPB.ajaxUrl, {
                action: 'tajmap_pb_reset_settings',
                nonce: TajMapPB.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showNotification('Failed to reset settings.', 'error');
                }
            });
        }
    }

    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="settings-notification ${type}">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `);

        $('.tajmap-settings').prepend(notification);

        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);

        notification.find('.notification-close').on('click', function() {
            notification.fadeOut(() => notification.remove());
        });
    }

})(jQuery);
</script>
