<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://justinfrydman.com/
 * @since      0.0.3
 *
 * @package    wpcable
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	exit;
}

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Fired when the plugin is deleted.
 *
 * This class defines all code necessary to run during the plugin's deletion routine.
 *
 * @since      0.0.3
 * @package    wpcable
 * @author     Justin Frydman <justin.frydman@gmail.com>
 */
class wpcable_uninstall {

	/**
	 * Fired on plugin deactivation.
	 *
	 * Removes all plugin tables and wp_option data
	 *
	 * @since    0.0.3
	 */
	public static function uninstall() {

		// sanity checks
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		check_ajax_referer( 'updates' );

		// good to go
		self::remove_plugin_options();
		self::remove_plugin_tables();
	}

	/**
	 * Removes plugin options from $prefix_options
	 *
	 * @since    0.0.3
	 */
	public static function remove_plugin_options() {

		$prefix = 'wpcable_';

		$options = array(
			$prefix . 'account_details',
			$prefix . 'revenue',
			$prefix . 'balance',
			$prefix . 'average',
			$prefix . 'what_to_check',
			$prefix . 'transcactions_version'
		);

		foreach( $options as $option ) {
			delete_option( $option );
		}

	}

	/*
	 * Remove plugin generated tables
	 *
	 * @since    0.0.3
	 */
	public static function remove_plugin_tables() {
		global $wpdb;

		$prefix = $wpdb->prefix;

		$tables = array(
			$prefix . 'codeable_transcactions',
			$prefix . 'codeable_amounts',
			$prefix . 'codeable_clients',
		);

		$wpdb->query( 'DROP TABLE IF EXISTS ' . implode( ',', $tables ) );
	}
}

wpcable_uninstall::uninstall();