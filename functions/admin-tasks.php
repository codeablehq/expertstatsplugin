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
		'label' => __( 'Good chances', 'wpcable' ),
		'color' => '#00b0ff',
	];
	$color_flags['neutral'] = [
		'label' => __( 'Normal', 'wpcable' ),
		'color' => '#80d8ff',
	];
	$color_flags['tough'] = [
		'label' => __( 'Needs effort', 'wpcable' ),
		'color' => '#607d8b',
	];
	$color_flags['pessimistic'] = [
		'label' => __( 'Unlikely', 'wpcable' ),
		'color' => '#90a4ae',
	];
	$color_flags['lost'] = [
		'label' => __( 'Lost, Cancelled, Unresponsive', 'wpcable' ),
		'color' => '#cfd8dc',
	];

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
			<li class="hired">
				| <a href="#state=hired">
					<?php _e( 'Hired', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
			<li class="paid">
				| <a href="#state=paid">
					<?php _e( 'Paid', 'wpcable' ); ?>
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
			<li class="canceled">
				| <a href="#state=canceled">
					<?php _e( 'Canceled', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
			<li class="lost">
				| <a href="#state=lost">
					<?php _e( 'Lost', 'wpcable' ); ?>
					<span class="count"></span>
				</a>
			</li>
		</ul>
		<div class="tablenav top">
			<div class="group">
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
			<div class="group">
			<?php
			foreach ( $color_flags as $flag => $info ) {
				printf(
					'<label class="filter flag-%1$s"><span class="tooltip autosize small" tabindex="0"><span class="tooltip-text">%2$s</span><input type="checkbox" data-flag="%1$s" /><div class="color"></div></span></label>',
					esc_attr( $flag ),
					esc_html( $info['label'] )
				);
			}
			?>
			</div>
		</div>
		<table class="widefat striped">
			<thead>
				<tr>
					<th class="col-client"><?php _e( 'Client', 'wpcable' ); ?></th>
					<th class="col-workroom"><?php _e( 'Workroom', 'wpcable' ); ?></th>
					<th class="col-value"><?php _e( 'Value', 'wpcable' ); ?></th>
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
		class="list-item<# if ( '1' === data.hidden ) { #> task-hidden<#} #><# if ('1' === data.subscribed ) { #> task-subscribed<#} #><# if ('1' === data.favored ) { #> task-favored<#} #><# if ('1' === data.promoted ) { #> task-promoted<#} #><# if ( data.flag ) { #> flag-{{{ data.flag }}}<# } #>"
		id="task-{{{ data.task_id }}}"
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
		<td class="col-value">
			<# if ( data.value > 0 ) { #>
				<span class="your-value tooltip autosize">
					<span class="tooltip-text"><?php _e( 'Your earnings', 'wpcable' ); ?></span>
					<span>$ {{{ parseInt( data.value ) }}}</span>
				</span><br />
				<small class="client-value tooltip bottom autosize">
					<span class="tooltip-text"><?php _e( 'Paid by the client', 'wpcable' ); ?></span>
					<span class="value">$ {{{ parseInt( data.value_client ) }}}</span>
				</small>
			<# } #>
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
				<# } #>
				<# if ( '1' === data.favored ) { #>
					<span class="tooltip bottom small autosize" tabindex="0">
						<span class="tooltip-text"><?php _e( 'Favored', 'wpcable' ); ?></span>
						️❤️
					</span>
				<# } #>
				<# if ( '1' === data.subscribed ) { #>
					<span class="tooltip bottom small autosize" tabindex="0">
						<span class="tooltip-text"><?php _e( 'Subscribed', 'wpcable' ); ?></span>
						👁
					</span>
				<# } #>
				</span>
			</div>
			<div class="row-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=codeable_estimate') ); ?>&fee_client={{{ data.client_fee }}}"><?php _e( 'Estimate', 'wpcable' ); ?></a>
				<ul class="color-flag">
				<?php
				foreach ( $color_flags as $flag => $info ) {
					printf(
						'<li data-flag="%1$s" <# if ( "%1$s" == data.flag ) { #> class="current"<# } #>><span class="tooltip autosize small" tabindex="0"><span class="tooltip-text">%2$s</span><div class="color"></div></span></li>',
						esc_attr( $flag ),
						esc_html( $info['label'] )
					);
				}
				?>
				</ul>
			</div>
		</td>
		<td class="col-notes">
			<div class="notes-body">{{{ data.notes_html }}}</div>
		</td>
	</ul>
	</script>
	<style>
	<?php
	foreach ( $color_flags as $flag => $info ) {
		printf(
			'.flag-%1$s, [data-flag="%1$s"] { --color: %2$s }',
			esc_attr( $flag ),
			$info['color']
		);
	}
	?>
	</style>
	<script>
	window.wpcable=window.wpcable||{};
	wpcable.tasks=<?php echo json_encode( $task_list ); ?>;
	wpcable.update_task_nonce=<?php echo json_encode( wp_create_nonce( 'wpcable-task' ) ); ?>;
	</script>
	<?php
}
