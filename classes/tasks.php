<?php

/**
 * Backend logic to manage tasks.
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class wpcable_tasks {


	/**
	 * List of used DB tables, with prefix.
	 *
	 * @var array
	 */
	public $tables = [];

	/**
	 * Initialize internal properties.
	 */
	public function __construct() {
		global $wpdb;

		$this->tables = [
			'tasks'   => $wpdb->prefix . 'codeable_tasks',
			'clients' => $wpdb->prefix . 'codeable_clients',
		];
	}

	/**
	 * Returns a list of all tasks.
	 *
	 * @return array
	 */
	public function get_tasks() {
		$query = "
			SELECT
				task.*,
				client.full_name AS `client_name`,
				client.medium AS `avatar`
			FROM
				`{$this->tables['tasks']}` AS task
			INNER JOIN `{$this->tables['clients']}` AS client
				ON client.client_id = task.client_id
			ORDER BY task.last_activity DESC, task.task_id DESC
		";

		// Check cache.
		$cache_key = 'tasks_list';
		$result    = $this->check_cache( $cache_key, $query );

		$result = array_map( [ $this, 'sanitize_task' ], $result );

		return $result;
	}

	/**
	 * Takes a single DB row as input and sanitizes some values.
	 *
	 * @param  array $task Raw DB value.
	 * @return array Sanitized task.
	 */
	public function sanitize_task( $task ) {
		date_default_timezone_set( get_option( 'timezone_string' ) );

		$task['estimate']      = (bool) $task['estimate'];
		$task['hidden']        = (bool) $task['hidden'];
		$task['promoted']      = (bool) $task['promoted'];
		$task['subscribed']    = (bool) $task['subscribed'];
		$task['favored']       = (bool) $task['favored'];
		$task['preferred']     = (bool) $task['preferred'];
		$task['client_fee']    = (float) $task['client_fee'];
		$task['value']         = (float) $task['value'];
		$task['value_client']  = (float) $task['value_client'];
		$task['last_activity'] = (int) $task['last_activity'];
		$task['last_sync']     = (int) $task['last_sync'];

		$task['last_activity_date'] = date_i18n(
			get_option( 'date_format' ),
			$task['last_activity']
		);
		$task['last_activity_time'] = date_i18n(
			get_option( 'time_format' ),
			$task['last_activity'],
			false
		);

		return $task;
	}

	/**
	 * Update the specified task in the DB.
	 *
	 * @param array $task
	 */
	public function update_task( $task ) {
		global $wpdb;
		$wpdb->show_errors();

		if ( ! is_array( $task ) || empty( $task['task_id'] ) ) {
			return;
		}

		$valid_fields = [
			'task_id' => '',
			'state'   => '',
			'notes'   => '',
			'flag'    => '',
		];

		$task = array_intersect_key( $task, $valid_fields );

		$wpdb->update(
			$this->tables['tasks'],
			$task,
			[ 'task_id' => $task['task_id'] ]
		);
	}

	/**
	 * Checks and sets cached data
	 *
	 * @since   0.0.6
	 * @author Justin Frydman
	 *
	 * @param bool $key     The unique cache key.
	 * @param bool $query   The query to check.
	 *
	 * @return mixed    The raw or cached data.
	 */
	private function check_cache( $key = false, $query = false ) {
		$cache = new wpcable_cache( $key, $query );
		return $cache->check();
	}
}
