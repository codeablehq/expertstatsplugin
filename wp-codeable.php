<?php
/**
 * @package wpcable
 *
 * Plugin Name:       Codeable - Expert's Stats
 * Plugin URI:        https://github.com/codeablehq/blackbook/wiki/Expert-Stats-Plugin
 * Description:       Get your Codeable data
 * Version:           0.3.0
 * Author:            Spyros Vlachopoulos
 * Contributors:      Panagiotis Synetos, John Leskas, Justin Frydman, Jonathan Bossenger, Rob Scott, Philipp Stracker
 * Author URI:        https://codeable.io/developers/spyros-vlachopoulos/
 * License:           GPL2
 * Text Domain:       wpcable
 * GitHub Plugin URI: https://github.com/codeablehq/expertstatsplugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load plugin textdomain.
add_action( 'plugins_loaded', 'wpcable_load_textdomain' );
function wpcable_load_textdomain() {
	load_plugin_textdomain(
		'wpcable',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages/'
	);
}

/** Define wpcable constants */
define( 'WPCABLE_TEMPLATE_DIR', dirname( __FILE__ ) . '/templates' );
define( 'WPCABLE_CLASSES_DIR', dirname( __FILE__ ) . '/classes' );
define( 'WPCABLE_FUNCTIONS_DIR', dirname( __FILE__ ) . '/functions' );
define( 'WPCABLE_ASSESTS_DIR', dirname( __FILE__ ) . '/assets' );
define( 'WPCABLE_DIR', dirname( __FILE__ ) );
define( 'WPCABLE_URI', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

require_once WPCABLE_CLASSES_DIR . '/object_cache.php';
require_once WPCABLE_FUNCTIONS_DIR . '/admin-settings.php';
require_once WPCABLE_FUNCTIONS_DIR . '/admin-tasks.php';
require_once WPCABLE_FUNCTIONS_DIR . '/admin-page.php';
require_once WPCABLE_FUNCTIONS_DIR . '/admin-estimate.php';
require_once WPCABLE_FUNCTIONS_DIR . '/helpers.php';
require_once WPCABLE_CLASSES_DIR . '/api_calls.php';
require_once WPCABLE_CLASSES_DIR . '/api_data.php';
require_once WPCABLE_CLASSES_DIR . '/stats.php';
require_once WPCABLE_CLASSES_DIR . '/tasks.php';
require_once WPCABLE_CLASSES_DIR . '/clients.php';

/**
 * Maybe sync with API server.
 *
 * @return void
 */
function wpcable_cronjob() {
	codeable_maybe_refresh_data();
}
add_action( 'wpcable_cronjob', 'wpcable_cronjob' );

/**
 * Create a scheduled event (if it does not exist already).
 */
function wpcable_cronstarter_activation() {
	if ( ! wp_next_scheduled( 'wpcable_cronjob' ) ) {
		wp_schedule_event( time(), 'hourly', 'wpcable_cronjob' );
	}
}

// and make sure it's called whenever WordPress loads
add_action( 'wp', 'wpcable_cronstarter_activation' );

function wpcable_sync_start() {
	$queue = get_option( 'wpcable_api_queue' );
	$data  = new wpcable_api_data();

	// Initialize the API queue on first call.
	if ( ! empty( $queue ) && is_array( $queue ) ) {
		$task = array_shift( $queue );
		wp_send_json_error( [
			'state' => 'RUNNING',
			'step'  => $task,
		] );
	}

	$queue = $data->prepare_queue();
	update_option( 'wpcable_api_queue', $queue );
	wp_send_json_success( [ 'state' => 'READY' ] );
}
add_action( 'wp_ajax_wpcable_sync_start', 'wpcable_sync_start' );

/**
 * Process the next API call and update the DB. When no API call is enqueued this
 * Ajax handler will initialize the API queue.
 */
function wpcable_sync_process() {
	$queue = get_option( 'wpcable_api_queue' );
	$data  = new wpcable_api_data();

	// Initialize the API queue on first call.
	if ( empty( $queue ) || ! is_array( $queue ) ) {
		wp_send_json_error( [ 'state' => 'FINISHED' ] );
	}

	// Process the next pending task.
	$task = array_shift( $queue );
	$next = $data->process_queue( $task );

	// Re-Insert partially completed tasks into the queue.
	if ( $next ) {
		array_unshift( $queue, $next );
	}

	// Store the timestamp of last full-sync in the options table.
	if ( empty( $queue ) ) {
		update_option( 'wpcable_last_fetch', time() );
		delete_option( 'wpcable_api_queue', $queue );
		wp_send_json_error( [ 'state' => 'FINISHED' ] );
	} else {
		update_option( 'wpcable_api_queue', $queue );
		wp_send_json_error( [
			'state' => 'RUNNING',
			'step'  => $task,
		] );
	}
}
add_action( 'wp_ajax_wpcable_sync_process', 'wpcable_sync_process' );

// on install
function wpcable_install() {
	global $wpdb;

	$wpcable_db_version = '0.0.3';

	if ( get_option( 'wpcable_transcactions_version' ) !== $wpcable_db_version ) {

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'codeable_transcactions';

		$sql = "CREATE TABLE {$table_name} (
			`id` int(11) NOT NULL,
			`description` varchar(128) CHARACTER SET utf8 NOT NULL,
			`dateadded` datetime NOT NULL,
			`fee_percentage` decimal(10,0) DEFAULT NULL,
			`fee_amount` decimal(10,0) DEFAULT NULL,
			`task_type` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
			`task_id` int(11) DEFAULT NULL,
			`task_title` text DEFAULT NULL,
			`parent_task_id` int(11) DEFAULT NULL,
			`preferred` int(4) DEFAULT NULL,
			`client_id` int(11) DEFAULT NULL,
			`last_sync` int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id),
			KEY client_id (client_id)
		) $charset_collate;";

		$db_delta = dbDelta( $sql );

		$table_name = $wpdb->prefix . 'codeable_amounts';

		$sql = "CREATE TABLE {$table_name} (
			`task_id` int(11) NOT NULL,
			`client_id` int(11) NOT NULL,
			`credit_revenue_id` int(11) DEFAULT NULL,
			`credit_revenue_amount` int(11) DEFAULT NULL,
			`credit_fee_id` int(11) DEFAULT NULL,
			`credit_fee_amount` int(11) DEFAULT NULL,
			`credit_user_id` int(11) DEFAULT NULL,
			`credit_user_amount` int(11) DEFAULT NULL,
			`debit_cost_id` int(11) DEFAULT NULL,
			`debit_cost_amount` int(11) DEFAULT NULL,
			`debit_user_id` int(11) DEFAULT NULL,
			`debit_user_amount` int(11) DEFAULT NULL,
			PRIMARY KEY  (task_id),
			KEY client_id (client_id)
		) $charset_collate;";

		$db_delta = dbDelta( $sql );

		$table_name = $wpdb->prefix . 'codeable_clients';

		$sql = "CREATE TABLE {$table_name} (
			`client_id` int(11) NOT NULL,
			`full_name` varchar(255) NOT NULL,
			`role` varchar(255) DEFAULT NULL,
			`last_sign_in_at` datetime DEFAULT NULL,
			`pro` int(11) DEFAULT NULL,
			`timezone_offset` int(11) DEFAULT NULL,
			`tiny` varchar(255) DEFAULT NULL,
			`small` varchar(255) DEFAULT NULL,
			`medium` varchar(255) DEFAULT NULL,
			`large` varchar(255) DEFAULT NULL,
			`last_sync` int(11) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (client_id)
		) $charset_collate;";

		$db_delta = dbDelta( $sql );

		$table_name = $wpdb->prefix . 'codeable_tasks';

		$sql = "CREATE TABLE {$table_name} (
			`task_id` int(11) NOT NULL,
			`client_id` int(11) NOT NULL,
			`title` varchar(255) NOT NULL,
			`estimate` bit DEFAULT 0 NOT NULL,
			`hidden` bit DEFAULT 0 NOT NULL,
			`promoted` bit DEFAULT 0 NOT NULL,
			`subscribed` bit DEFAULT 0 NOT NULL,
			`favored` bit DEFAULT 0 NOT NULL,
			`preferred` bit DEFAULT 0 NOT NULL,
			`client_fee` float DEFAULT 17.5 NOT NULL,
			`state` varchar(50) DEFAULT '' NOT NULL,
			`kind` varchar(50) DEFAULT '' NOT NULL,
			`value` float DEFAULT 0 NOT NULL,
			`value_client` float DEFAULT 0 NOT NULL,
			`last_activity` int(11) DEFAULT 0 NOT NULL,
			`last_activity_by` varchar(200) DEFAULT '' NOT NULL,
			`last_sync` int(11) DEFAULT 0 NOT NULL,
			`flag` varchar(20) DEFAULT '' NOT NULL,
			`notes` text DEFAULT '' NOT NULL,
			PRIMARY KEY  (task_id)
		) $charset_collate;";

		$db_delta = dbDelta( $sql );

		update_option( 'wpcable_transcactions_version', $wpcable_db_version );
	}
}

register_activation_hook( __FILE__, 'wpcable_install' );

// on deactivation
function wpcable_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'classes/deactivator.php';
	WpCable_deactivator::deactivate();

}

register_deactivation_hook( __FILE__, 'wpcable_deactivate' );

function wpcable_admin_scripts( $hook ) {
	$plugin_hooks = [
		'toplevel_page_codeable_transcactions_stats',
		'codeable-stats_page_codeable_tasks',
		'codeable-stats_page_codeable_estimate',
		'codeable-stats_page_codeable_settings',
	];

	if ( ! in_array( $hook, $plugin_hooks, true ) ) {
		return;
	}

	wp_enqueue_style(
		'gridcss',
		plugins_url( 'assets/css/grid12.css', __FILE__ )
	);
	wp_enqueue_style(
		'wpcablecss',
		plugins_url( 'assets/css/wpcable.css', __FILE__ )
	);
	wp_enqueue_style(
		'ratycss',
		plugins_url( 'assets/css/jquery.raty.css', __FILE__ )
	);
	wp_enqueue_style(
		'datatablecss',
		plugins_url( 'assets/css/jquery.dataTables.min.css', __FILE__ )
	);
	wp_enqueue_style(
		'simplemde',
		plugins_url( 'assets/css/simplemde.min.css', __FILE__ )
	);

	wp_enqueue_script(
		'highchartsjs',
		plugins_url( 'assets/js/highcharts.js', __FILE__ ),
		[ 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ],
		null,
		true
	);
	wp_enqueue_script(
		'highcharts_export_js',
		plugins_url( 'assets/js/exporting.js', __FILE__ ),
		[ 'jquery', 'highchartsjs' ],
		null,
		true
	);
	wp_enqueue_script(
		'highcharts_offline_export_js',
		plugins_url( 'assets/js/offline-exporting.js', __FILE__ ),
		[ 'jquery', 'highcharts_export_js' ],
		null,
		true
	);
	wp_enqueue_style( 'jquery-ui-datepicker' );

	wp_enqueue_script(
		'highcharts3djs',
		plugins_url( 'assets/js/highcharts-3d.js', __FILE__ ),
		[ 'highchartsjs' ]
	);
	wp_enqueue_script(
		'ratyjs',
		plugins_url( 'assets/js/jquery.raty.js', __FILE__ )
	);
	wp_enqueue_script(
		'datatablesjs',
		plugins_url( 'assets/js/jquery.dataTables.min.js', __FILE__ )
	);
	wp_enqueue_script(
		'matchheightjs',
		plugins_url( 'assets/js/jquery.matchHeight-min.js', __FILE__ )
	);
	wp_enqueue_script(
		'simplemde',
		plugins_url( 'assets/js/simplemde.min.js', __FILE__ )
	);
	wp_enqueue_script(
		'wpcablejs',
		plugins_url( 'assets/js/wpcable.js', __FILE__ ),
		[ 'wp-util' ]
	);
}

add_action( 'admin_enqueue_scripts', 'wpcable_admin_scripts' );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpcable_action_links' );

function wpcable_action_links( $links ) {
	$link = sprintf(
		'<a href="%s">Settings</a>',
		esc_url( get_admin_url( null, 'admin.php?page=codeable_settings' ) )
	);

	array_unshift( $links, $link );

	return $links;
}
