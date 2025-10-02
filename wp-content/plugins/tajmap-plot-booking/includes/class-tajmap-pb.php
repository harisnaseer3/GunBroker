<?php

namespace TajMapPB;

if (!defined('ABSPATH')) {
	exit;
}

class Plugin {
	public static function init() {
		self::instance()->hooks();
	}

	private static $instance = null;

	public static function instance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function hooks() {
		add_action('admin_menu', [$this, 'register_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);

		// Frontend AJAX actions
		add_action('wp_ajax_tajmap_pb_get_plots', [$this, 'ajax_get_plots']);
		add_action('wp_ajax_nopriv_tajmap_pb_get_plots', [$this, 'ajax_get_plots']);
		add_action('wp_ajax_tajmap_pb_test_ajax', [$this, 'ajax_test_ajax']);
		add_action('wp_ajax_nopriv_tajmap_pb_test_ajax', [$this, 'ajax_test_ajax']);
		
		// Debug: Log that AJAX actions are being registered
		error_log('TajMap: AJAX actions registered at ' . current_time('mysql'));
		add_action('wp_ajax_tajmap_pb_save_lead', [$this, 'ajax_save_lead']);
		add_action('wp_ajax_nopriv_tajmap_pb_save_lead', [$this, 'ajax_save_lead']);
		add_action('wp_ajax_tajmap_pb_get_plot_details', [$this, 'ajax_get_plot_details']);
		add_action('wp_ajax_nopriv_tajmap_pb_get_plot_details', [$this, 'ajax_get_plot_details']);
		add_action('wp_ajax_tajmap_pb_save_user', [$this, 'ajax_save_user']);
		add_action('wp_ajax_nopriv_tajmap_pb_save_user', [$this, 'ajax_save_user']);
		add_action('wp_ajax_tajmap_pb_save_saved_plot', [$this, 'ajax_save_saved_plot']);
		add_action('wp_ajax_tajmap_pb_get_saved_plots', [$this, 'ajax_get_saved_plots']);
		add_action('wp_ajax_tajmap_pb_check_user_status', [$this, 'ajax_check_user_status']);
		add_action('wp_ajax_nopriv_tajmap_pb_check_user_status', [$this, 'ajax_check_user_status']);

		// Admin AJAX actions
		add_action('wp_ajax_tajmap_pb_save_plot', [$this, 'ajax_save_plot']);
		add_action('wp_ajax_tajmap_pb_delete_plot', [$this, 'ajax_delete_plot']);
		add_action('wp_ajax_tajmap_pb_set_status', [$this, 'ajax_set_status']);
		add_action('wp_ajax_tajmap_pb_set_lead_status', [$this, 'ajax_set_lead_status']);
		add_action('wp_ajax_tajmap_pb_get_leads', [$this, 'ajax_get_leads']);
		add_action('wp_ajax_tajmap_pb_get_lead_details', [$this, 'ajax_get_lead_details']);
		add_action('wp_ajax_tajmap_pb_add_lead_note', [$this, 'ajax_add_lead_note']);
		add_action('wp_ajax_tajmap_pb_get_analytics', [$this, 'ajax_get_analytics']);
		
		// Settings AJAX handlers
		add_action('wp_ajax_tajmap_pb_save_settings', [$this, 'ajax_save_settings']);
		add_action('wp_ajax_tajmap_pb_test_configuration', [$this, 'ajax_test_configuration']);
		add_action('wp_ajax_tajmap_pb_export_settings', [$this, 'ajax_export_settings']);
		add_action('wp_ajax_tajmap_pb_reset_settings', [$this, 'ajax_reset_settings']);

		// Admin post actions
		add_action('admin_post_tajmap_pb_export_csv', [$this, 'handle_export_csv']);
		add_action('admin_post_tajmap_pb_export_leads', [$this, 'handle_export_leads']);
		add_action('admin_post_tajmap_pb_export_analytics', [$this, 'handle_export_analytics']);

		// Frontend routes
		add_action('init', [$this, 'register_frontend_routes']);
		add_action('template_redirect', [$this, 'handle_frontend_routes']);

		// Shortcodes
		add_shortcode('tajmap_plot_booking', [$this, 'shortcode']);
		add_shortcode('tajmap_landing_page', [$this, 'landing_page_shortcode']);
		add_shortcode('tajmap_plot_selection', [$this, 'plot_selection_shortcode']);
		add_shortcode('tajmap_gallery', [$this, 'gallery_shortcode']);
		add_shortcode('tajmap_user_dashboard', [$this, 'user_dashboard_shortcode']);
	}

	public function register_admin_menu() {
		add_menu_page(
			'Plot Management',
			'Plot Management',
			'manage_options',
			'tajmap-plot-management',
			[$this, 'render_admin_dashboard'],
			'dashicons-location-alt',
			56
		);
		add_submenu_page(
			'tajmap-plot-management',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'tajmap-plot-management',
			[$this, 'render_admin_dashboard']
		);
		add_submenu_page(
			'tajmap-plot-management',
			'Plot Editor',
			'Plot Editor',
			'manage_options',
			'tajmap-plot-editor',
			[$this, 'render_plot_editor']
		);
		add_submenu_page(
			'tajmap-plot-management',
			'Leads Management',
			'Leads',
			'manage_options',
			'tajmap-plot-leads',
			[$this, 'render_leads_page']
		);
		add_submenu_page(
			'tajmap-plot-management',
			'Reports',
			'Reports',
			'manage_options',
			'tajmap-plot-reports',
			[$this, 'render_reports_page']
		);
		add_submenu_page(
			'tajmap-plot-management',
			'Settings',
			'Settings',
			'manage_options',
			'tajmap-plot-settings',
			[$this, 'render_settings_page']
		);
	}

	public function enqueue_admin_assets($hook) {
		$admin_pages = [
			'toplevel_page_tajmap-plot-management',
			'plot-management_page_tajmap-plot-editor',
			'plot-management_page_tajmap-plot-leads',
			'plot-management_page_tajmap-plot-reports',
			'plot-management_page_tajmap-plot-settings'
		];

		if (!in_array($hook, $admin_pages)) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style('tajmap-pb-admin', TAJMAP_PB_URL . 'assets/admin-enhanced.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-pb-admin', TAJMAP_PB_URL . 'assets/admin.js', ['jquery'], TAJMAP_PB_VERSION, true);

		// Plot editor JavaScript for advanced editor
		if ($hook === 'plot-management_page_tajmap-plot-editor') {
			wp_enqueue_script('tajmap-plot-editor', TAJMAP_PB_URL . 'assets/plot-editor.js', ['jquery'], TAJMAP_PB_VERSION, true);
		}

		// Chart.js for dashboard and reports
		if (in_array($hook, ['toplevel_page_tajmap-plot-management', 'plot-management_page_tajmap-plot-reports'])) {
			wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
		}

		wp_localize_script('tajmap-pb-admin', 'TajMapPB', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_admin'),
		]);
	}

	public function enqueue_public_assets() {
		wp_enqueue_style('tajmap-pb-public', TAJMAP_PB_URL . 'assets/public.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-pb-public', TAJMAP_PB_URL . 'assets/public.js', ['jquery'], TAJMAP_PB_VERSION, true);
		wp_localize_script('tajmap-pb-public', 'TajMapPBPublic', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_public'),
		]);
	}

	public function render_admin_dashboard() {
		include TAJMAP_PB_PATH . 'templates/admin-dashboard.php';
	}

	public function render_plot_editor() {
		include TAJMAP_PB_PATH . 'templates/admin-plot-editor.php';
	}

	public function render_leads_page() {
		include TAJMAP_PB_PATH . 'templates/admin-leads-enhanced.php';
	}

	public function render_reports_page() {
		include TAJMAP_PB_PATH . 'templates/admin-reports.php';
	}

	public function render_settings_page() {
		include TAJMAP_PB_PATH . 'templates/admin-settings.php';
	}

	public function shortcode($atts) {
		ob_start();
		include TAJMAP_PB_PATH . 'templates/public-shortcode.php';
		return ob_get_clean();
	}

	private function verify_nonce($nonce_action, $field = 'nonce') {
		$nonce = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
		if (!wp_verify_nonce($nonce, $nonce_action)) {
			wp_send_json_error(['message' => 'Invalid nonce'], 403);
		}
	}

	public function ajax_get_plots() {
		// Immediate response to test if AJAX is working
		error_log('TajMap: ajax_get_plots called at ' . current_time('mysql'));
		error_log('TajMap: POST data: ' . print_r($_POST, true));
		
		// Get plots from database
		global $wpdb;
		$rows = $wpdb->get_results('SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' ORDER BY id ASC', ARRAY_A);
		
		error_log('TajMap: Found ' . count($rows) . ' plots in database');
		
		// If no plots exist, create some sample plots
		if (empty($rows)) {
			error_log('TajMap: No plots found, creating sample plots');
			$sample_plots = [
				[
					'plot_name' => 'Sample Plot 1',
					'sector' => 'A',
					'block' => '1',
					'street' => 'Main Street',
					'status' => 'available',
					'coordinates' => json_encode([
						['x' => 100, 'y' => 100],
						['x' => 200, 'y' => 100],
						['x' => 200, 'y' => 200],
						['x' => 100, 'y' => 200]
					]),
					'created_at' => current_time('mysql')
				],
				[
					'plot_name' => 'Sample Plot 2',
					'sector' => 'A',
					'block' => '2',
					'street' => 'Second Street',
					'status' => 'sold',
					'coordinates' => json_encode([
						['x' => 300, 'y' => 100],
						['x' => 400, 'y' => 100],
						['x' => 400, 'y' => 200],
						['x' => 300, 'y' => 200]
					]),
					'created_at' => current_time('mysql')
				]
			];
			
			foreach ($sample_plots as $plot) {
				$wpdb->insert(TAJMAP_PB_TABLE_PLOTS, $plot);
			}
			
			// Get the newly created plots
			$rows = $wpdb->get_results('SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' ORDER BY id ASC', ARRAY_A);
			error_log('TajMap: Created ' . count($rows) . ' sample plots');
		}
		
		// Debug: Log the first plot structure
		if (!empty($rows)) {
			error_log('TajMap: First plot structure: ' . print_r($rows[0], true));
		}
		
		// Send response with plots
		wp_send_json_success(['plots' => $rows]);
		error_log('TajMap: Plugin constants: ' . print_r([
			'TAJMAP_PB_TABLE_PLOTS' => defined('TAJMAP_PB_TABLE_PLOTS') ? TAJMAP_PB_TABLE_PLOTS : 'NOT_DEFINED',
			'TAJMAP_PB_VERSION' => defined('TAJMAP_PB_VERSION') ? TAJMAP_PB_VERSION : 'NOT_DEFINED'
		], true));
		
		// For now, completely bypass nonce verification to test if that's the issue
		error_log('TajMap: Bypassing nonce verification for debugging');
		
		global $wpdb;
		
		// Check if table exists
		$table_name = TAJMAP_PB_TABLE_PLOTS;
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
		error_log('TajMap: Table exists: ' . ($table_exists ? 'YES' : 'NO'));
		
		$rows = $wpdb->get_results('SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' ORDER BY id ASC', ARRAY_A);
		
		// Debug: Log the query and results
		error_log('TajMap: Query executed: SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS);
		error_log('TajMap: Found ' . count($rows) . ' plots');
		
		// If no plots exist, create some sample plots for testing
		if (empty($rows)) {
			error_log('TajMap: No plots found, creating sample plots');
			$sample_plots = [
				[
					'plot_name' => 'Sample Plot 1',
					'sector' => 'A',
					'block' => '1',
					'street' => 'Main Street',
					'status' => 'available',
					'coordinates' => json_encode([
						['x' => 100, 'y' => 100],
						['x' => 200, 'y' => 100],
						['x' => 200, 'y' => 200],
						['x' => 100, 'y' => 200]
					]),
					'created_at' => current_time('mysql')
				],
				[
					'plot_name' => 'Sample Plot 2',
					'sector' => 'A',
					'block' => '2',
					'street' => 'Second Street',
					'status' => 'sold',
					'coordinates' => json_encode([
						['x' => 300, 'y' => 100],
						['x' => 400, 'y' => 100],
						['x' => 400, 'y' => 200],
						['x' => 300, 'y' => 200]
					]),
					'created_at' => current_time('mysql')
				]
			];
			
			foreach ($sample_plots as $plot) {
				$wpdb->insert(TAJMAP_PB_TABLE_PLOTS, $plot);
			}
			
			// Get the newly created plots
			$rows = $wpdb->get_results('SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' ORDER BY id ASC', ARRAY_A);
			error_log('TajMap: Created ' . count($rows) . ' sample plots');
		}
		
		foreach ($rows as &$r) {
			$r['base_image_url'] = $r['base_image_id'] ? wp_get_attachment_image_url((int) $r['base_image_id'], 'full') : '';
		}
		
		error_log('TajMap: Sending response with ' . count($rows) . ' plots');
		wp_send_json_success([
			'plots' => $rows,
			'debug_info' => [
				'table_exists' => $table_exists,
				'plugin_version' => defined('TAJMAP_PB_VERSION') ? TAJMAP_PB_VERSION : 'NOT_DEFINED',
				'timestamp' => current_time('mysql')
			]
		]);
	}

	public function ajax_test_ajax() {
		// Simple test endpoint without nonce verification
		error_log('TajMap: Test AJAX endpoint called');
		wp_send_json_success(['message' => 'AJAX is working', 'timestamp' => current_time('mysql')]);
	}

	public function ajax_check_user_status() {
		$this->verify_nonce('tajmap_pb_frontend');
		$user = null;
		if (is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$user = [
				'id' => $current_user->ID,
				'name' => $current_user->display_name,
				'email' => $current_user->user_email,
				'logged_in' => true
			];
		}
		wp_send_json_success(['user' => $user]);
	}

	public function ajax_save_plot() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$plot_name = isset($_POST['plot_name']) ? sanitize_text_field(wp_unslash($_POST['plot_name'])) : '';
		$street = isset($_POST['street']) ? sanitize_text_field(wp_unslash($_POST['street'])) : '';
		$sector = isset($_POST['sector']) ? sanitize_text_field(wp_unslash($_POST['sector'])) : '';
		$block = isset($_POST['block']) ? sanitize_text_field(wp_unslash($_POST['block'])) : '';
		$coordinates = isset($_POST['coordinates']) ? wp_kses_post(wp_unslash($_POST['coordinates'])) : '';
		$status = isset($_POST['status']) && $_POST['status'] === 'sold' ? 'sold' : 'available';
		$base_image_id = isset($_POST['base_image_id']) ? intval($_POST['base_image_id']) : null;

		$data = [
			'plot_name' => $plot_name,
			'street' => $street,
			'sector' => $sector,
			'block' => $block,
			'coordinates' => $coordinates,
			'status' => $status,
			'base_image_id' => $base_image_id,
			'updated_at' => current_time('mysql'),
		];

		if ($id > 0) {
			$wpdb->update(TAJMAP_PB_TABLE_PLOTS, $data, ['id' => $id]);
			wp_send_json_success(['id' => $id]);
		} else {
			$data['created_at'] = current_time('mysql');
			$wpdb->insert(TAJMAP_PB_TABLE_PLOTS, $data);
			wp_send_json_success(['id' => $wpdb->insert_id]);
		}
	}

	public function ajax_delete_plot() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		if ($id > 0) {
			$wpdb->delete(TAJMAP_PB_TABLE_PLOTS, ['id' => $id]);
		}
		wp_send_json_success();
	}

	public function ajax_set_status() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$status = isset($_POST['status']) && $_POST['status'] === 'sold' ? 'sold' : 'available';
		if ($id > 0) {
			$wpdb->update(TAJMAP_PB_TABLE_PLOTS, ['status' => $status, 'updated_at' => current_time('mysql')], ['id' => $id]);
		}
		wp_send_json_success();
	}

	public function ajax_save_lead() {
		$this->verify_nonce('tajmap_pb_public');
		global $wpdb;
		$plot_id = isset($_POST['plot_id']) ? intval($_POST['plot_id']) : 0;
		$phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
		$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
		$message = isset($_POST['message']) ? wp_kses_post(wp_unslash($_POST['message'])) : '';

		if ($plot_id <= 0 || empty($phone) || empty($email) || !is_email($email)) {
			wp_send_json_error(['message' => 'Invalid input'], 400);
		}

		$wpdb->insert(TAJMAP_PB_TABLE_LEADS, [
			'plot_id' => $plot_id,
			'phone' => $phone,
			'email' => $email,
			'message' => $message,
			'status' => 'new',
			'created_at' => current_time('mysql'),
		]);
		wp_send_json_success(['id' => $wpdb->insert_id]);
	}

	public function ajax_set_lead_status() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;
		$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		$status = isset($_POST['status']) && $_POST['status'] === 'contacted' ? 'contacted' : 'new';
		if ($id > 0) {
			$wpdb->update(TAJMAP_PB_TABLE_LEADS, ['status' => $status], ['id' => $id]);
		}
		wp_send_json_success();
	}

	public function handle_export_csv() {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized', 403);
		}
		$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
		if (!wp_verify_nonce($nonce, 'tajmap_pb_export')) {
			wp_die('Invalid nonce', 403);
		}
		global $wpdb;
		$rows = $wpdb->get_results(
			'SELECT l.id, l.phone, l.email, l.message, l.status, l.created_at, p.plot_name, p.street, p.sector, p.block FROM ' . TAJMAP_PB_TABLE_LEADS . ' l LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id ORDER BY l.created_at DESC',
			ARRAY_A
		);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="tajmap_leads_' . date('Ymd_His') . '.csv"');
		$fh = fopen('php://output', 'w');
		fputcsv($fh, ['ID','Phone','Email','Message','Status','Created At','Plot Name','Street','Sector','Block']);
		foreach ($rows as $r) {
			fputcsv($fh, [$r['id'],$r['phone'],$r['email'],$r['message'],$r['status'],$r['created_at'],$r['plot_name'],$r['street'],$r['sector'],$r['block']]);
		}
		fclose($fh);
		exit;
	}

	// Frontend Routes
	public function register_frontend_routes() {
		add_rewrite_rule('^plots/?$', 'index.php?tajmap_page=plots', 'top');
		add_rewrite_rule('^plots/([^/]+)/?$', 'index.php?tajmap_page=plot&plot_id=$matches[1]', 'top');
		add_rewrite_rule('^gallery/?$', 'index.php?tajmap_page=gallery', 'top');
		add_rewrite_rule('^dashboard/?$', 'index.php?tajmap_page=dashboard', 'top');
		add_rewrite_rule('^contact/?$', 'index.php?tajmap_page=contact', 'top');

		// Query vars
		add_filter('query_vars', function($vars) {
			$vars[] = 'tajmap_page';
			$vars[] = 'plot_id';
			return $vars;
		});
	}

	public function handle_frontend_routes() {
		$page = get_query_var('tajmap_page');
		if (!$page) return;

		switch ($page) {
			case 'plots':
				$this->render_plot_selection_page();
				break;
			case 'plot':
				$this->render_plot_details_page();
				break;
			case 'gallery':
				$this->render_gallery_page();
				break;
			case 'dashboard':
				$this->render_dashboard_page();
				break;
			case 'contact':
				$this->render_contact_page();
				break;
		}
		exit;
	}

	public function render_plot_selection_page() {
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		wp_localize_script('tajmap-frontend', 'TajMapFrontend', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_frontend'),
			'homeUrl' => home_url(),
		]);
		include TAJMAP_PB_PATH . 'templates/frontend/plot-selection.php';
	}

	public function render_plot_details_page() {
		$plot_id = intval(get_query_var('plot_id'));
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		wp_localize_script('tajmap-frontend', 'TajMapFrontend', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_frontend'),
			'homeUrl' => home_url(),
			'plotId' => $plot_id,
		]);
		include TAJMAP_PB_PATH . 'templates/frontend/plot-details.php';
	}

	public function render_gallery_page() {
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		include TAJMAP_PB_PATH . 'templates/frontend/gallery.php';
	}

	public function render_dashboard_page() {
		if (!is_user_logged_in()) {
			wp_redirect(wp_login_url(home_url('/dashboard')));
			exit;
		}
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		wp_localize_script('tajmap-frontend', 'TajMapFrontend', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_frontend'),
			'homeUrl' => home_url(),
		]);
		include TAJMAP_PB_PATH . 'templates/frontend/dashboard.php';
	}

	public function render_contact_page() {
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		include TAJMAP_PB_PATH . 'templates/frontend/contact.php';
	}

	// Shortcodes
	public function landing_page_shortcode($atts) {
		ob_start();
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		wp_localize_script('tajmap-frontend', 'TajMapFrontend', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_frontend'),
			'homeUrl' => home_url(),
		]);
		include TAJMAP_PB_PATH . 'templates/frontend/landing.php';
		return ob_get_clean();
	}

	public function plot_selection_shortcode($atts) {
		error_log('TajMap: plot_selection_shortcode called at ' . current_time('mysql'));
		error_log('TajMap: Plugin constants: ' . print_r([
			'TAJMAP_PB_URL' => defined('TAJMAP_PB_URL') ? TAJMAP_PB_URL : 'NOT_DEFINED',
			'TAJMAP_PB_VERSION' => defined('TAJMAP_PB_VERSION') ? TAJMAP_PB_VERSION : 'NOT_DEFINED',
			'TAJMAP_PB_PATH' => defined('TAJMAP_PB_PATH') ? TAJMAP_PB_PATH : 'NOT_DEFINED'
		], true));
		
		ob_start();
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		// Do NOT enqueue the simple frontend script here; the interactive template contains its own JS.
		// Instead, inject the TajMapFrontend config inline for the template to consume.
		echo '<script>window.TajMapFrontend = ' . wp_json_encode([
			'ajaxUrl' => 'http://localhost/Gunbroker/wp-admin/admin-ajax.php',
			'nonce' => wp_create_nonce('tajmap_pb_frontend'),
			'homeUrl' => home_url(),
			'pluginName' => 'TAJMAP_PLOT_BOOKING',
		]) . ';</script>';
		include TAJMAP_PB_PATH . 'templates/frontend/plot-selection-interactive.php';
		return ob_get_clean();
	}

	public function gallery_shortcode($atts) {
		ob_start();
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		include TAJMAP_PB_PATH . 'templates/frontend/gallery.php';
		return ob_get_clean();
	}

	public function user_dashboard_shortcode($atts) {
		ob_start();
		if (!is_user_logged_in()) {
			return '<p>Please <a href="' . wp_login_url() . '">login</a> to view your dashboard.</p>';
		}
		wp_enqueue_style('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.css', [], TAJMAP_PB_VERSION);
		wp_enqueue_script('tajmap-frontend', TAJMAP_PB_URL . 'assets/frontend.js', ['jquery'], TAJMAP_PB_VERSION, true);
		wp_localize_script('tajmap-frontend', 'TajMapFrontend', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tajmap_pb_frontend'),
			'homeUrl' => home_url(),
		]);
		include TAJMAP_PB_PATH . 'templates/frontend/dashboard.php';
		return ob_get_clean();
	}

	// Utility Methods
	public function get_user_page_url() {
		// Check if a page with the shortcode already exists
		$pages = get_posts([
			'post_type' => 'page',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => [
				[
					'key' => '_wp_page_template',
					'value' => 'default',
					'compare' => '='
				]
			],
			's' => '[tajmap_plot_selection]'
		]);

		if (!empty($pages)) {
			return get_permalink($pages[0]->ID);
		}

		// Create a new page if none exists
		$page_id = wp_insert_post([
			'post_title' => 'Available Plots',
			'post_content' => '[tajmap_plot_selection]',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_name' => 'available-plots'
		]);

		if ($page_id && !is_wp_error($page_id)) {
			return get_permalink($page_id);
		}

		// Fallback to home URL with shortcode parameter
		return home_url('/?tajmap_plot_selection=1');
	}

	// AJAX Handlers
	public function ajax_get_plot_details() {
		$this->verify_nonce('tajmap_pb_frontend');
		global $wpdb;
		$id = isset($_POST['plot_id']) ? intval($_POST['plot_id']) : 0;
		$plot = $wpdb->get_row($wpdb->prepare(
			'SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE id = %d',
			$id
		), ARRAY_A);

		if (!$plot) {
			wp_send_json_error(['message' => 'Plot not found'], 404);
		}

		$plot['base_image_url'] = $plot['base_image_id'] ? wp_get_attachment_image_url((int) $plot['base_image_id'], 'full') : '';
		wp_send_json_success(['plot' => $plot]);
	}

	public function ajax_save_user() {
		$this->verify_nonce('tajmap_pb_frontend');
		global $wpdb;

		$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
		$phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
		$first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
		$last_name = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';

		if (empty($email) || !is_email($email)) {
			wp_send_json_error(['message' => 'Valid email is required'], 400);
		}

		$existing = $wpdb->get_row($wpdb->prepare(
			'SELECT id FROM ' . TAJMAP_PB_TABLE_USERS . ' WHERE email = %s',
			$email
		));

		$data = [
			'email' => $email,
			'phone' => $phone,
			'first_name' => $first_name,
			'last_name' => $last_name,
		];

		if ($existing) {
			$wpdb->update(TAJMAP_PB_TABLE_USERS, $data, ['id' => $existing->id]);
			$user_id = $existing->id;
		} else {
			$data['created_at'] = current_time('mysql');
			$wpdb->insert(TAJMAP_PB_TABLE_USERS, $data);
			$user_id = $wpdb->insert_id;
		}

		wp_send_json_success(['user_id' => $user_id]);
	}

	public function ajax_save_saved_plot() {
		$this->verify_nonce('tajmap_pb_frontend');
		global $wpdb;

		$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
		$plot_id = isset($_POST['plot_id']) ? intval($_POST['plot_id']) : 0;

		if ($user_id <= 0 || $plot_id <= 0) {
			wp_send_json_error(['message' => 'Invalid parameters'], 400);
		}

		$existing = $wpdb->get_row($wpdb->prepare(
			'SELECT id FROM ' . TAJMAP_PB_TABLE_SAVED_PLOTS . ' WHERE user_id = %d AND plot_id = %d',
			$user_id, $plot_id
		));

		if (!$existing) {
			$wpdb->insert(TAJMAP_PB_TABLE_SAVED_PLOTS, [
				'user_id' => $user_id,
				'plot_id' => $plot_id,
				'created_at' => current_time('mysql'),
			]);
		}

		wp_send_json_success();
	}

	public function ajax_get_saved_plots() {
		$this->verify_nonce('tajmap_pb_frontend');
		global $wpdb;

		$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

		if ($user_id <= 0) {
			wp_send_json_error(['message' => 'Invalid user'], 400);
		}

		$saved_plots = $wpdb->get_results($wpdb->prepare(
			'SELECT sp.*, p.plot_name, p.street, p.sector, p.block, p.status FROM ' . TAJMAP_PB_TABLE_SAVED_PLOTS . ' sp
			 JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = sp.plot_id
			 WHERE sp.user_id = %d ORDER BY sp.created_at DESC',
			$user_id
		), ARRAY_A);

		wp_send_json_success(['saved_plots' => $saved_plots]);
	}

	// Admin AJAX Handlers
	public function ajax_get_leads() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;

		$status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
		$search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

		$where = [];
		$params = [];

		if ($status && $status !== 'all') {
			$where[] = 'l.status = %s';
			$params[] = $status;
		}

		if ($search) {
			$where[] = '(l.email LIKE %s OR l.phone LIKE %s OR p.plot_name LIKE %s)';
			$params[] = '%' . $wpdb->esc_like($search) . '%';
			$params[] = '%' . $wpdb->esc_like($search) . '%';
			$params[] = '%' . $wpdb->esc_like($search) . '%';
		}

		$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

		$leads = $wpdb->get_results($wpdb->prepare(
			'SELECT l.*, p.plot_name, p.street, p.sector, p.block FROM ' . TAJMAP_PB_TABLE_LEADS . ' l
			 LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id
			 ' . $where_clause . ' ORDER BY l.created_at DESC',
			$params
		), ARRAY_A);

		wp_send_json_success(['leads' => $leads]);
	}

	public function ajax_get_lead_details() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;

		$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;

		if ($lead_id <= 0) {
			wp_send_json_error(['message' => 'Invalid lead ID'], 400);
		}

		$lead = $wpdb->get_row($wpdb->prepare(
			'SELECT l.*, p.plot_name, p.street, p.sector, p.block FROM ' . TAJMAP_PB_TABLE_LEADS . ' l
			 LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id WHERE l.id = %d',
			$lead_id
		), ARRAY_A);

		if (!$lead) {
			wp_send_json_error(['message' => 'Lead not found'], 404);
		}

		$history = $wpdb->get_results($wpdb->prepare(
			'SELECT lh.*, u.first_name, u.last_name FROM ' . TAJMAP_PB_TABLE_LEAD_HISTORY . ' lh
			 LEFT JOIN ' . TAJMAP_PB_TABLE_USERS . ' u ON u.id = lh.user_id WHERE lh.lead_id = %d ORDER BY lh.created_at ASC',
			$lead_id
		), ARRAY_A);

		wp_send_json_success(['lead' => $lead, 'history' => $history]);
	}

	public function ajax_add_lead_note() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;

		$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
		$note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';

		if ($lead_id <= 0 || empty($note)) {
			wp_send_json_error(['message' => 'Invalid parameters'], 400);
		}

		$wpdb->insert(TAJMAP_PB_TABLE_LEAD_HISTORY, [
			'lead_id' => $lead_id,
			'user_id' => get_current_user_id(),
			'action' => 'note_added',
			'details' => $note,
			'created_at' => current_time('mysql'),
		]);

		wp_send_json_success();
	}

	public function ajax_get_analytics() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}
		global $wpdb;

		// Total plots
		$total_plots = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS);

		// Available plots
		$available_plots = $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s',
			'available'
		));

		// Sold plots
		$sold_plots = $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s',
			'sold'
		));

		// Total leads
		$total_leads = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS);

		// Leads by status
		$leads_by_status = $wpdb->get_results(
			'SELECT status, COUNT(*) as count FROM ' . TAJMAP_PB_TABLE_LEADS . ' GROUP BY status',
			ARRAY_A
		);

		// Recent leads (last 30 days)
		$recent_leads = $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE created_at >= %s',
			date('Y-m-d H:i:s', strtotime('-30 days'))
		));

		// Conversion rate (leads that became interested or closed)
		$converted_leads = $wpdb->get_var($wpdb->prepare(
			'SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE status IN (%s, %s)',
			'interested', 'closed'
		));

		$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 2) : 0;

		wp_send_json_success([
			'total_plots' => $total_plots,
			'available_plots' => $available_plots,
			'sold_plots' => $sold_plots,
			'total_leads' => $total_leads,
			'recent_leads' => $recent_leads,
			'conversion_rate' => $conversion_rate,
			'leads_by_status' => $leads_by_status,
		]);
	}

	// Settings AJAX handlers
	public function ajax_save_settings() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}

		$settings_json = isset($_POST['settings']) ? sanitize_text_field(wp_unslash($_POST['settings'])) : '';
		if (empty($settings_json)) {
			wp_send_json_error(['message' => 'No settings data provided'], 400);
		}

		$settings = json_decode($settings_json, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			wp_send_json_error(['message' => 'Invalid settings data'], 400);
		}

		// Sanitize settings
		$sanitized_settings = $this->sanitize_settings($settings);

		// Save settings
		$result = update_option('tajmap_pb_settings', $sanitized_settings);

		if ($result !== false) {
			wp_send_json_success(['message' => 'Settings saved successfully']);
		} else {
			wp_send_json_error(['message' => 'Failed to save settings'], 500);
		}
	}

	public function ajax_test_configuration() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}

		$settings = get_option('tajmap_pb_settings', []);
		$issues = [];

		// Test email configuration
		if (!empty($settings['company_email']) && !is_email($settings['company_email'])) {
			$issues[] = 'Invalid company email address';
		}

		// Test database connection
		global $wpdb;
		if (!$wpdb->last_error) {
			$test_query = $wpdb->get_var('SELECT 1');
			if ($test_query !== '1') {
				$issues[] = 'Database connection issue';
			}
		} else {
			$issues[] = 'Database error: ' . $wpdb->last_error;
		}

		// Test file permissions
		$upload_dir = wp_upload_dir();
		if (!is_writable($upload_dir['basedir'])) {
			$issues[] = 'Upload directory is not writable';
		}

		if (empty($issues)) {
			wp_send_json_success(['message' => 'Configuration test passed']);
		} else {
			wp_send_json_error(['message' => 'Configuration issues found: ' . implode(', ', $issues)]);
		}
	}

	public function ajax_export_settings() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized', 403);
		}

		$settings = get_option('tajmap_pb_settings', []);
		$export_data = [
			'export_date' => current_time('mysql'),
			'plugin_version' => TAJMAP_PB_VERSION,
			'settings' => $settings
		];

		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename="tajmap_settings_' . date('Ymd_His') . '.json"');
		echo json_encode($export_data, JSON_PRETTY_PRINT);
		exit;
	}

	public function ajax_reset_settings() {
		$this->verify_nonce('tajmap_pb_admin');
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized'], 403);
		}

		// Delete settings to reset to defaults
		$result = delete_option('tajmap_pb_settings');

		if ($result !== false) {
			wp_send_json_success(['message' => 'Settings reset to default']);
		} else {
			wp_send_json_error(['message' => 'Failed to reset settings'], 500);
		}
	}

	private function sanitize_settings($settings) {
		$sanitized = [];
		
		foreach ($settings as $key => $value) {
			if (is_array($value)) {
				$sanitized[$key] = $this->sanitize_settings($value);
			} else {
				switch ($key) {
					case 'company_name':
					case 'development_name':
					case 'company_phone':
					case 'default_currency':
					case 'measurement_units':
					case 'map_tile_server':
					case 'google_maps_api_key':
					case 'backup_frequency':
						$sanitized[$key] = sanitize_text_field($value);
						break;
					case 'company_email':
						$sanitized[$key] = sanitize_email($value);
						break;
					case 'default_zoom_level':
					case 'max_plot_area':
					case 'min_plot_area':
					case 'data_retention_days':
						$sanitized[$key] = absint($value);
						break;
					case 'email_notifications':
					case 'sms_notifications':
					case 'auto_assign_leads':
					case 'require_registration':
					case 'enable_analytics':
					case 'enable_lead_scoring':
						$sanitized[$key] = (bool) $value;
						break;
					default:
						$sanitized[$key] = sanitize_text_field($value);
				}
			}
		}
		
		return $sanitized;
	}

	public function handle_export_leads() {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized', 403);
		}
		$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
		if (!wp_verify_nonce($nonce, 'tajmap_pb_export')) {
			wp_die('Invalid nonce', 403);
		}
		global $wpdb;
		$leads = $wpdb->get_results(
			'SELECT l.id, l.phone, l.email, l.message, l.status, l.source, l.created_at, p.plot_name, p.street, p.sector, p.block FROM ' . TAJMAP_PB_TABLE_LEADS . ' l LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id ORDER BY l.created_at DESC',
			ARRAY_A
		);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="tajmap_leads_' . date('Ymd_His') . '.csv"');
		$fh = fopen('php://output', 'w');
		fputcsv($fh, ['ID','Phone','Email','Message','Status','Source','Created At','Plot Name','Street','Sector','Block']);
		foreach ($leads as $lead) {
			fputcsv($fh, [$lead['id'],$lead['phone'],$lead['email'],$lead['message'],$lead['status'],$lead['source'],$lead['created_at'],$lead['plot_name'],$lead['street'],$lead['sector'],$lead['block']]);
		}
		fclose($fh);
		exit;
	}

	public function handle_export_analytics() {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized', 403);
		}
		$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
		if (!wp_verify_nonce($nonce, 'tajmap_pb_export')) {
			wp_die('Invalid nonce', 403);
		}
		global $wpdb;

		// Calculate analytics data
		$total_plots = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS);
		$available_plots = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s', 'available'));
		$sold_plots = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s', 'sold'));
		$total_leads = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS);
		$recent_leads = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE created_at >= %s', date('Y-m-d H:i:s', strtotime('-30 days'))));
		$converted_leads = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE status IN (%s, %s)', 'interested', 'closed'));
		$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 2) : 0;

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="tajmap_analytics_' . date('Ymd_His') . '.csv"');
		$fh = fopen('php://output', 'w');
		fputcsv($fh, ['Metric','Value']);
		fputcsv($fh, ['Total Plots', $total_plots]);
		fputcsv($fh, ['Available Plots', $available_plots]);
		fputcsv($fh, ['Sold Plots', $sold_plots]);
		fputcsv($fh, ['Total Leads', $total_leads]);
		fputcsv($fh, ['Recent Leads (30 days)', $recent_leads]);
		fputcsv($fh, ['Conversion Rate (%)', $conversion_rate]);
		fclose($fh);
		exit;
	}
}
