<?php

class GunBroker_Settings {

    public function __construct() {
        add_action('admin_init', array($this, 'init_settings'));
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        // Register settings with validation
        register_setting('gunbroker_settings', 'gunbroker_dev_key', array(
            'sanitize_callback' => array($this, 'sanitize_dev_key')
        ));
        register_setting('gunbroker_settings', 'gunbroker_username', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gunbroker_settings', 'gunbroker_password', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gunbroker_settings', 'gunbroker_markup_percentage', array(
            'sanitize_callback' => array($this, 'sanitize_markup_percentage')
        ));
        register_setting('gunbroker_settings', 'gunbroker_listing_duration', array(
            'sanitize_callback' => array($this, 'sanitize_listing_duration')
        ));
        register_setting('gunbroker_settings', 'gunbroker_sandbox_mode', array(
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        register_setting('gunbroker_settings', 'gunbroker_auto_end_zero_stock', array(
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
    }

    /**
     * Sanitize developer key
     */
    public function sanitize_dev_key($value) {
        $value = sanitize_text_field($value);
        if (!empty($value) && strlen($value) < 10) {
            add_settings_error(
                'gunbroker_settings',
                'invalid_dev_key',
                'Developer key appears to be invalid (too short)'
            );
        }
        return $value;
    }

    /**
     * Sanitize markup percentage
     */
    public function sanitize_markup_percentage($value) {
        $value = floatval($value);
        if ($value < 0) {
            $value = 0;
            add_settings_error(
                'gunbroker_settings',
                'invalid_markup',
                'Markup percentage cannot be negative'
            );
        } elseif ($value > 500) {
            $value = 500;
            add_settings_error(
                'gunbroker_settings',
                'invalid_markup',
                'Markup percentage cannot exceed 500%'
            );
        }
        return $value;
    }

    /**
     * Sanitize listing duration
     */
    public function sanitize_listing_duration($value) {
        $value = intval($value);
        $valid_durations = array(1, 3, 5, 7, 10);

        if (!in_array($value, $valid_durations)) {
            add_settings_error(
                'gunbroker_settings',
                'invalid_duration',
                'Invalid listing duration selected'
            );
            return 7; // Default to 7 days
        }

        return $value;
    }

    /**
     * Get all settings as array
     */
    public function get_settings() {
        return array(
            'dev_key' => get_option('gunbroker_dev_key', ''),
            'username' => get_option('gunbroker_username', ''),
            'password' => get_option('gunbroker_password', ''),
            'markup_percentage' => get_option('gunbroker_markup_percentage', 10),
            'listing_duration' => get_option('gunbroker_listing_duration', 7),
            'sandbox_mode' => get_option('gunbroker_sandbox_mode', true),
            'auto_end_zero_stock' => get_option('gunbroker_auto_end_zero_stock', true)
        );
    }

    /**
     * Check if plugin is properly configured
     */
    public function is_configured() {
        $settings = $this->get_settings();
        return !empty($settings['dev_key']) && !empty($settings['username']) && !empty($settings['password']);
    }

    /**
     * Get formatted settings for display
     */
    public function get_formatted_settings() {
        $settings = $this->get_settings();

        return array(
            'API Status' => $this->is_configured() ? 'Configured' : 'Not Configured',
            'Sandbox Mode' => $settings['sandbox_mode'] ? 'Enabled' : 'Disabled',
            'Markup Percentage' => $settings['markup_percentage'] . '%',
            'Listing Duration' => $settings['listing_duration'] . ' days',
            'Auto-End Zero Stock' => $settings['auto_end_zero_stock'] ? 'Yes' : 'No'
        );
    }

    /**
     * Export settings as JSON (for backup)
     */
    public function export_settings() {
        $settings = $this->get_settings();
        // Don't export password for security
        unset($settings['password']);
        return json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * Import settings from JSON
     */
    public function import_settings($json_data) {
        $settings = json_decode($json_data, true);

        if (!$settings) {
            return new WP_Error('invalid_json', 'Invalid JSON data');
        }

        // Validate and save each setting
        foreach ($settings as $key => $value) {
            if ($key === 'password') {
                continue; // Skip password for security
            }

            $option_name = 'gunbroker_' . $key;
            update_option($option_name, $value);
        }

        return true;
    }
}