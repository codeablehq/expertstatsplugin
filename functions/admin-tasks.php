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

function wpcable_ajax_update_task() {
	if ( empty( $_POST['_wpnonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpcable-task' ) ) {
		return;
	}

	$task = $_POST['task'];

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
 * Called when the settings page is loaded - process actions such as logout.
 *
 * @return void
 */
function codeable_load_tasks_page() {
	$nonce = false;
}
add_action( 'load-codeable-stats_page_codeable_tasks', 'codeable_load_tasks_page' );

/**
 * Render the settings page.
 *
 * @return void
 */
function codeable_tasks_callback() {
	codeable_page_requires_login( __( 'Your tasks', 'wpcable' ) );
	codeable_admin_notices();

	$wpcable_tasks = new wpcable_tasks();

	$task_list = $wpcable_tasks->get_tasks();

	?>
	<div class="wrap wpcable_wrap tasks">
		<h1
			class="list-title"
			data-none="<?php echo esc_attr( __( 'No tasks', 'wpcable' ) ); ?>"
			data-one="<?php echo esc_attr( __( 'One task', 'wpcable' ) ); ?>"
			data-many="<?php echo esc_attr( __( '[NUM] tasks', 'wpcable' ) ); ?>"
		>
			<?php _e( 'Your tasks', 'wpcable' ); ?>
		</h1>

		<ul class="subsubsub">
			<li class="all">
				<a href="#state=all">
					<?php _e( 'All', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
			<li class="published">
				| <a href="#state=published">
					<?php _e( 'Published', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
			<li class="estimated">
				| <a href="#state=estimated">
					<?php _e( 'Estimated', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
			<li class="completed">
				| <a href="#state=completed">
					<?php _e( 'Completed', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
			<li class="refunded">
				| <a href="#state=refunded">
					<?php _e( 'Refunded', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
		</ul>
		<table class="widefat striped">
			<thead>
				<tr>
					<th class="col-client"><?php _e( 'Client', 'wpcable' ); ?></th>
					<th class="col-workroom"><?php _e( 'Workroom', 'wpcable' ); ?></th>
					<th class="col-title"><?php _e( 'Title', 'wpcable' ); ?></th>
					<th class="col-notes"><?php _e( 'Notes', 'wpcable' ); ?></th>
				</tr>
			</thead>
			<tbody class="task-list"></tbody>
		</table>
		<div class="notes-editor-layer" style="display:none">
			<div class="notes-editor">
				<h2 class="task-title"></h2>
				<textarea></textarea>
				<div class="buttons">
					<button class="button btn-cancel">Cancel</button>
					<button class="button button-primary btn-save">Save</button>
				</div>
			</div>
		</div>
		<?php codeable_last_fetch_info(); ?>
	</div>
	<script type="text/html" id="tmpl-list-item">
	<tr
		class="list-item"
		id="task-{{{ data.task_id }}}"
		<# if (data.color ) { #> style="--color: {{{ data.color }}}" <# } #>
	>
		<td class="col-client">
			<span class="tooltip right autosize" tabindex="0">
				<div class="tooltip-text">{{{ data.client_name }}}</div>
				<img src="{{{ data.avatar }}}" />
			</span>
		</td>
		<td class="col-workroom">
			<a href="https://app.codeable.io/tasks/{{{ data.task_id }}}" target="_blank">
				<strong>#{{{ data.task_id }}}</strong>
			</a>
		</td>
		<td class="col-title">
			<div>{{{ data.title }}}</div>
			<div class="row-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=codeable_estimate') ); ?>&fee={{{ data.client_fee }}}"><?php _e( 'Estimate', 'wpcable' ); ?></a>
				<ul class="color-flag">
					<li style="background-color:"></li>
					<li style="background-color:#CC0000"></li>
					<li style="background-color:#FF8800"></li>
					<li style="background-color:#007E33"></li>
					<li style="background-color:#0099CC"></li>
					<li style="background-color:#00695C"></li>
					<li style="background-color:#0D47A1"></li>
					<li style="background-color:#9933CC"></li>
					<li style="background-color:#D81B60"></li>
					<li style="background-color:#CCCCCC"></li>
				</ul>
			</div>
		</td>
		<td class="col-notes">
			<div class="notes-body">{{{ data.notes_html }}}</div>
		</td>
	</ul>
	</script>
	<script>
	window.wpcable=window.wpcable||{};
	wpcable.tasks=<?php echo json_encode( $task_list ); ?>;
	wpcable.update_task_nonce=<?php echo json_encode( wp_create_nonce( 'wpcable-task' ) ); ?>;
	</script>
	<?php
}
