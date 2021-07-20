<?php
/**
 * Tasks list.
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
function wpcable_tasks_menu() {
	add_submenu_page(
		'codeable_transcactions_stats',
		'Tasks',
		'Tasks',
		'manage_options',
		'codeable_tasks',
		'codeable_tasks_callback'
	);
}
add_action( 'admin_menu', 'wpcable_tasks_menu', 50 );

/**
 * Ajax hndler that updates a single task in the DB.
 */
function wpcable_ajax_update_task() {
	if ( empty( $_POST['_wpnonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpcable-task' ) ) {
		return;
	}

	$task = wp_unslash( $_POST['task'] );

	if ( ! $task ) {
		return;
	}

	$wpcable_tasks = new wpcable_tasks();
	$wpcable_tasks->update_task( $task );

	echo 'OK';
	exit;
}
add_action( 'wp_ajax_wpcable_update_task', 'wpcable_ajax_update_task' );

/**
 * Ajax handler that returns a full task list in JSON format.
 */
function wpcable_ajax_reload_tasks() {
	$wpcable_tasks = new wpcable_tasks();

	$task_list = $wpcable_tasks->get_tasks();
	echo wp_json_encode( $task_list );
	exit;
}
add_action( 'wp_ajax_wpcable_reload_tasks', 'wpcable_ajax_reload_tasks' );

/**
 * Render the settings page.
 *
 * @return void
 */
function codeable_tasks_callback() {
	codeable_page_requires_login( __( 'Your tasks', 'wpcable' ) );
	codeable_admin_notices();

	$color_flags = [];
	$color_flags[''] = [
		'label' => __( 'New', 'wpcable' ),
		'color' => '',
	];
	$color_flags['prio'] = [
		'label' => __( 'Priority!', 'wpcable' ),
		'color' => '#cc0000',
	];
	$color_flags['completed'] = [
		'label' => __( 'Won (completed)', 'wpcable' ),
		'color' => '#b39ddb',
	];
	$color_flags['won'] = [
		'label' => __( 'Won (active)', 'wpcable' ),
		'color' => '#673ab7',
	];
	$color_flags['estimated'] = [
		'label' => __( 'Estimated', 'wpcable' ),
		'color' => '#9ccc65',
	];
	$color_flags['optimistic'] = [
		'label' => __( 'Active Rapport', 'wpcable' ),
		'color' => '#00b0ff',
	];
	$color_flags['neutral'] = [
		'label' => __( 'Normal', 'wpcable' ),
		'color' => '#80d8ff',
	];
	$color_flags['tough'] = [
		'label' => __( 'Difficult', 'wpcable' ),
		'color' => '#607d8b',
	];
	$color_flags['pessimistic'] = [
		'label' => __( 'Unresponsive', 'wpcable' ),
		'color' => '#90a4ae',
	];
	$color_flags['lost'] = [
		'label' => __( 'Mark as lost', 'wpcable' ),
		'color' => '#cfd8dc',
	];

	$wpcable_tasks = new wpcable_tasks();

	$task_list = $wpcable_tasks->get_tasks();

	$admin_task_table_template = apply_filters('wpcable_admin_task_table_template', WPCABLE_TEMPLATE_DIR.'/admin-task-table.php') ;
	ob_start();
	require_once $admin_task_table_template;
	echo ob_get_clean();
}
