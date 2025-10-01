<?php
/**
 * Plugin Name: TajMap Plot Booking
 * Description: Interactive real estate plot booking system with SVG overlays, admin polygon editor, and lead capture.
 * Version: 1.0.0
 * Author: TajMap
 * License: GPL2
 */

if (!defined('ABSPATH')) {
	exit;
}

// Constants
if (!defined('TAJMAP_PB_VERSION')) {
	define('TAJMAP_PB_VERSION', '2.0.0');
}
if (!defined('TAJMAP_PB_PATH')) {
	define('TAJMAP_PB_PATH', plugin_dir_path(__FILE__));
}
if (!defined('TAJMAP_PB_URL')) {
	define('TAJMAP_PB_URL', plugin_dir_url(__FILE__));
}
if (!defined('TAJMAP_PB_TABLE_PLOTS')) {
	global $wpdb;
	define('TAJMAP_PB_TABLE_PLOTS', $wpdb->prefix . 'tajmap_plots');
}
if (!defined('TAJMAP_PB_TABLE_LEADS')) {
	global $wpdb;
	define('TAJMAP_PB_TABLE_LEADS', $wpdb->prefix . 'tajmap_leads');
}
if (!defined('TAJMAP_PB_TABLE_USERS')) {
	global $wpdb;
	define('TAJMAP_PB_TABLE_USERS', $wpdb->prefix . 'tajmap_users');
}
if (!defined('TAJMAP_PB_TABLE_SAVED_PLOTS')) {
	global $wpdb;
	define('TAJMAP_PB_TABLE_SAVED_PLOTS', $wpdb->prefix . 'tajmap_saved_plots');
}
if (!defined('TAJMAP_PB_TABLE_LEAD_HISTORY')) {
	global $wpdb;
	define('TAJMAP_PB_TABLE_LEAD_HISTORY', $wpdb->prefix . 'tajmap_lead_history');
}

register_activation_hook(__FILE__, function () {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset_collate = $wpdb->get_charset_collate();

	$plots_sql = "CREATE TABLE IF NOT EXISTS `" . TAJMAP_PB_TABLE_PLOTS . "` (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		plot_name VARCHAR(191) NOT NULL,
		street VARCHAR(191) NULL,
		sector VARCHAR(191) NULL,
		block VARCHAR(191) NULL,
		coordinates LONGTEXT NOT NULL,
		status ENUM('available','sold') NOT NULL DEFAULT 'available',
		base_image_id BIGINT UNSIGNED NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (id),
		KEY status_idx (status)
	) $charset_collate;";
	dbDelta($plots_sql);

	$leads_sql = "CREATE TABLE IF NOT EXISTS `" . TAJMAP_PB_TABLE_LEADS . "` (
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
	dbDelta($leads_sql);

	$users_sql = "CREATE TABLE IF NOT EXISTS `" . TAJMAP_PB_TABLE_USERS . "` (
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
	dbDelta($users_sql);

	$saved_plots_sql = "CREATE TABLE IF NOT EXISTS `" . TAJMAP_PB_TABLE_SAVED_PLOTS . "` (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		plot_id BIGINT UNSIGNED NOT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY user_idx (user_id),
		KEY plot_idx (plot_id),
		UNIQUE KEY user_plot_idx (user_id, plot_id)
	) $charset_collate;";
	dbDelta($saved_plots_sql);

	$lead_history_sql = "CREATE TABLE IF NOT EXISTS `" . TAJMAP_PB_TABLE_LEAD_HISTORY . "` (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		lead_id BIGINT UNSIGNED NOT NULL,
		user_id BIGINT UNSIGNED NULL,
		action VARCHAR(100) NOT NULL,
		details TEXT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY lead_idx (lead_id)
	) $charset_collate;";
	dbDelta($lead_history_sql);
});

// Autoload basic includes
require_once TAJMAP_PB_PATH . 'includes/class-tajmap-pb.php';

add_action('plugins_loaded', function () {
	\TajMapPB\Plugin::init();
});
