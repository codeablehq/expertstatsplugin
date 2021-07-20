<?php
/**
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register submenu pages.
 *
 * @return void
 */
function wpcable_options() {
	add_submenu_page(
		'codeable_transcactions_stats',
		'Estimate',
		'Estimate',
		'manage_options',
		'codeable_estimate',
		'codeable_estimate_callback'
	);
	add_submenu_page(
		'codeable_transcactions_stats',
		'Settings',
		'Settings',
		'manage_options',
		'codeable_settings',
		'codeable_settings_callback'
	);

	add_action( 'admin_init', 'codeable_register_settings' );
}
add_action( 'admin_menu', 'wpcable_options', 100 );

/**
 * Called when the settings page is loaded - process actions such as logout.
 *
 * @return void
 */
function codeable_load_settings_page() {
	$nonce  = false;
	$action = false;

	if ( empty( $_REQUEST['_wpnonce'] ) ) {
		return;
	}
	if ( empty( $_REQUEST['action'] ) ) {
		return;
	}

	$nonce  = wp_unslash( $_REQUEST['_wpnonce'] );
	$action = wp_unslash( $_REQUEST['action'] );

	if ( 'logout' === $action && wp_verify_nonce( $nonce, $action ) ) {
		codeable_api_logout();
	} elseif ( 'flush_data' === $action && wp_verify_nonce( $nonce, $action ) ) {
		codeable_flush_all_data();
	}
}
add_action( 'load-codeable-stats_page_codeable_settings', 'codeable_load_settings_page' );

/**
 * Register Codeable Stats settings.
 *
 * @return void
 */
function codeable_register_settings() {
	register_setting( 'wpcable_group', 'wpcable_fee_type' );
	register_setting( 'wpcable_group', 'wpcable_rate' );
	register_setting( 'wpcable_group', 'wpcable_cancel_after_days' );
	register_setting( 'wpcable_group', 'wpcable_tasks_stop_at_page' );

	if ( ! codeable_api_logged_in() ) {
		register_setting( 'wpcable_group', 'wpcable_email' );
		register_setting( 'wpcable_group', 'wpcable_password' ); // This is a dummy setting!
	}
}

/**
 * Filter that is called when the user tries to save a password.
 * We intercept the workflow and log into the codeable API without saving the
 * password into the DB.
 *
 * @param  string $value     The new value (i.e., new password).
 * @param  string $old_value Previous value (always empty).
 * @return string Always returns the $old_value.
 */
function codeable_handle_login( $value, $old_value ) {
	if ( ! empty( $_REQUEST['wpcable_email'] ) ) {
		$email = wp_unslash( $_REQUEST['wpcable_email'] );
	} else {
		$email = get_option( 'wpcable_email' );
	}

	if ( $email && $value ) {
		codeable_api_authenticate( $email, $value );
	}

	// Returning the old_value prevents WP from saving the anything.
	return $old_value;
}
add_filter( 'pre_update_option_wpcable_password', 'codeable_handle_login', 10, 2 );

/**
 * Render the settings page.
 *
 * @return void
 */
function codeable_settings_callback() {
	codeable_admin_notices();

	$wpcable_email              = get_option( 'wpcable_email' );
	$wpcable_rate               = get_option( 'wpcable_rate', 80 );
	$wpcable_fee_type           = get_option( 'wpcable_fee_type', 'client' );
	$wpcable_cancel_after_days  = get_option( 'wpcable_cancel_after_days', 180 );
	$wpcable_tasks_stop_at_page = get_option( 'wpcable_tasks_stop_at_page', 0 );

	$logout_url = wp_nonce_url(
		add_query_arg( 'action', 'logout' ),
		'logout'
	);

	$flush_data_url = wp_nonce_url(
		add_query_arg( 'action', 'flush_data' ),
		'flush_data'
	);

	$flush_data_warning = __( 'All your data is deleted from the DB, including your private task notes or color flags. This cannot be undone.\n\nDo you want to clear your data and log out?', 'wpcable' );

	// Hacky way to save settings without a second redirect...
	$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'action', '_wpnonce', 'success', 'error' ] );

	$admin_settings_template = apply_filters('wpcable_admin_settings_template', WPCABLE_TEMPLATE_DIR.'/admin-settings.php') ;
	ob_start();
	require_once $admin_settings_template;
	echo ob_get_clean();
}
