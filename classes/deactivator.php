<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.0.3
 * @package    wpcable
 * @author     Justin Frydman <justin.frydman@gmail.com>
 */
class wpcable_deactivator {

	/**
	 * Fired on plugin deactivation.
	 *
	 * Removes all plugin tables and wp_option data
	 *
	 * @since    0.0.3
	 */
	public static function deactivate() {
		self::remove_cronjobs();
	}

	/**
	 * Remove all scheduled jobs
	 *
	 * @since    0.0.3
	 */
	public static function remove_cronjobs() {

		// find out when the last event was scheduled
		$timestamp = wp_next_scheduled( 'wpcable_cronjob' );
		// unschedule previous event if any
		wp_unschedule_event( $timestamp, 'wpcable_cronjob' );
		// clear cron upon plugin deactivation
		wp_clear_scheduled_hook( 'wpcable_cronjob' );

	}

}