<?php
/**
 * Plugin Name: WP Codeable
 * Plugin URI: https://github.com/codeablehq/blackbook/wiki/Expert-Stats-Plugin
 * Description: Get your Codeable data
 * Version: 0.0.8
 * Author: Spyros Vlachopoulos
 * Contributors: Panagiotis Synetos, John Leskas, Justin Frydman, Jonathan Bossenger, Rob Scott
 * Author URI: https://codeable.io/developers/spyros-vlachopoulos/
 * License: GPL2
 * Text Domain: wpcable
 * GitHub Plugin URI: https://github.com/codeablehq/expertstatsplugin
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


// Load plugin textdomain
add_action( 'plugins_loaded', 'wpcable_load_textdomain' );
function wpcable_load_textdomain() {
	load_plugin_textdomain( 'wpcable', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/** Define wpcable constants */
define( 'WPCABLE_TEMPLATE_DIR', dirname( __FILE__ ) . '/templates' );
define( 'WPCABLE_CLASSES_DIR', dirname( __FILE__ ) . '/classes' );
define( 'WPCABLE_FUNCTIONS_DIR', dirname( __FILE__ ) . '/functions' );
define( 'WPCABLE_ASSESTS_DIR', dirname( __FILE__ ) . '/assets' );
define( 'WPCABLE_DIR', dirname( __FILE__ ) );
define( 'WPCABLE_URI', rtrim( plugin_dir_url( __FILE__ ), '/' ) );


final class wpcable {

	public function __construct() {

		$this->includes();

	}

	private function includes() {
		// require_once( 'classes/cpt.php' );
		require_once( WPCABLE_CLASSES_DIR . '/object_cache.php' );
		require_once( WPCABLE_FUNCTIONS_DIR . '/admin-settings.php' );
		require_once( WPCABLE_FUNCTIONS_DIR . '/admin-page.php' );
		require_once( WPCABLE_FUNCTIONS_DIR . '/formatting.php' );
		require_once( WPCABLE_FUNCTIONS_DIR . '/pert-calculator.php' );
		require_once( WPCABLE_CLASSES_DIR . '/api_calls.php' );
		require_once( WPCABLE_CLASSES_DIR . '/transactions.php' );
		require_once( WPCABLE_CLASSES_DIR . '/stats.php' );
		require_once( WPCABLE_CLASSES_DIR . '/clients.php' );

	}


}

new wpcable();


// create a scheduled event (if it does not exist already)
function wpcable_cronstarter_activation() {
	if ( ! wp_next_scheduled( 'wpcable_cronjob' ) ) {
		// wp_schedule_event( time(), 'daily', 'wpcable_cronjob' );
	}
}

// and make sure it's called whenever WordPress loads
add_action( 'wp', 'wpcable_cronstarter_activation' );


// on install
function wpcable_install() {
	global $wpdb;

	$wpcable_transcactions_version = '0.0.2';

	if ( get_option( 'wpcable_transcactions_version' ) != $wpcable_transcactions_version ) {


		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

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
		      PRIMARY KEY  (id),
		      KEY client_id (client_id)
		    ) $charset_collate;";

		$dbDelta = dbDelta( $sql );


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

		$dbDelta = dbDelta( $sql );


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
		      PRIMARY KEY  (client_id)
		    ) $charset_collate;";

		$dbDelta = dbDelta( $sql );

		update_option( 'wpcable_transcactions_version', $wpcable_transcactions_version );

		// set default scan method
		if ( get_option( 'wpcable_what_to_check' ) == false ) {
			update_option( 'wpcable_what_to_check', '0' );
		}

	}


}

register_activation_hook( __FILE__, 'wpcable_install' );

// on deactivation
function wpcable_deactivate() {

	require_once plugin_dir_path( __FILE__ ) . 'classes/deactivator.php';
	wpcable_deactivator::deactivate();

}

register_deactivation_hook( __FILE__, 'wpcable_deactivate' );


function wpcable_admin_scripts( $hook ) {

	if ( $hook != 'toplevel_page_codeable_transcactions_stats' ) {
		return;
	}
	wp_enqueue_style( 'gridcss', plugins_url( 'assets/css/grid12.css', __FILE__ ) );
	wp_enqueue_style( 'wpcablecss', plugins_url( 'assets/css/wpcable.css', __FILE__ ) );
	wp_enqueue_style( 'ratycss', plugins_url( 'assets/css/jquery.raty.css', __FILE__ ) );
	wp_enqueue_style( 'datatablecss', plugins_url( 'assets/css/jquery.dataTables.min.css', __FILE__ ) );

	wp_enqueue_script(
		'highchartsjs',
		plugins_url( 'assets/js/highcharts.js', __FILE__ ),
		array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ),
		time(),
		true
	);
	wp_enqueue_script(
		'highcharts_export_js',
		plugins_url( 'assets/js/exporting.js', __FILE__ ),
		array( 'jquery', 'highchartsjs' ),
		time(),
		true
	);
	wp_enqueue_script(
		'highcharts_offline_export_js',
		plugins_url( 'assets/js/offline-exporting.js', __FILE__ ),
		array( 'jquery', 'highcharts_export_js' ),
		time(),
		true
	);
	wp_enqueue_style( 'jquery-ui-datepicker' );

	wp_enqueue_script( 'highcharts3djs', plugins_url( 'assets/js/highcharts-3d.js', __FILE__ ), array( 'highchartsjs' ) );
	wp_enqueue_script( 'ratyjs', plugins_url( 'assets/js/jquery.raty.js', __FILE__ ) );
	wp_enqueue_script( 'datatablesjs', plugins_url( 'assets/js/jquery.dataTables.min.js', __FILE__ ) );
	wp_enqueue_script( 'matchheightjs', plugins_url( 'assets/js/jquery.matchHeight-min.js', __FILE__ ) );
	wp_enqueue_script( 'wpcablejs', plugins_url( 'assets/js/wpcable.js', __FILE__ ) );
}

add_action( 'admin_enqueue_scripts', 'wpcable_admin_scripts' );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpcable_action_links' );

function wpcable_action_links( $links ) {
	$links = array( '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=codeable_settings' ) ) . '">Settings</a>' ) + $links;

	return $links;
}
