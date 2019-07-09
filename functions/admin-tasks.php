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
		<div class="tablenav top">
			<label class="filter">
				<input type="checkbox" data-filter="no_hidden" />
				<?php _e( 'No hidden tasks', 'wpcable' ); ?>
			</label>
			<label class="filter">
				<input type="checkbox" data-filter="subscribed" />
				👁 <?php _e( 'Subscribed', 'wpcable' ); ?>
			</label>
			<label class="filter">
				<input type="checkbox" data-filter="promoted" />
				📣 <?php _e( 'Promoted', 'wpcable' ); ?>
			</label>
			<label class="filter">
				<input type="checkbox" data-filter="favored" />
				❤️ <?php _e( 'Favored', 'wpcable' ); ?>
			</label>
		</div>
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
		class="list-item<# if ( '1' === data.hidden ) { #> task-hidden<#} #><# if ('1' === data.subscribed ) { #> task-subscribed<#} #><# if ('1' === data.favored ) { #> task-favored<#} #><# if ('1' === data.promoted ) { #> task-promoted<#} #>"
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
			<div>
				<span class="task-title">{{{ data.title }}}</span>
				<span class="task-flags">
				<# if ( '1' === data.promoted ) { #>
					<span class="tooltip bottom small autosize" tabindex="0">
						<span class="tooltip-text"><?php _e( 'Promoted', 'wpcable' ); ?></span>
						📣
					</span>
				<#} #>
				<# if ( '1' === data.favored ) { #>
					<span class="tooltip bottom small autosize" tabindex="0">
						<span class="tooltip-text"><?php _e( 'Favored', 'wpcable' ); ?></span>
						️❤️
					</span>
				<#} #>
				<# if ( '1' === data.subscribed ) { #>
					<span class="tooltip bottom small autosize" tabindex="0">
						<span class="tooltip-text"><?php _e( 'Subscribed', 'wpcable' ); ?></span>
						👁
					</span>
				<#} #>
				</span>
			</div>
			<div class="row-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=codeable_estimate') ); ?>&fee={{{ data.client_fee }}}"><?php _e( 'Estimate', 'wpcable' ); ?></a>
				<ul class="color-flag">
					<li style="background-color:">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">New</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#CC0000">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Priority!</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#b39ddb">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Won (completed)</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#673ab7">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Won (active)</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#9ccc65">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Estimated</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#00b0ff">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Good chances</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#80d8ff">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Normal</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#607d8b">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Needs effort</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#90a4ae">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Unlikely</span>
							<div class="color"></div>
						</span>
					</li>
					<li style="background-color:#cfd8dc">
						<span class="tooltip autosize small" tabindex="0">
							<span class="tooltip-text">Lost, Cancelled, Unresponsive</span>
							<div class="color"></div>
						</span>
					</li>
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
