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

	?>
	<div class="wrap wpcable_wrap">
		<form method="post" action="options.php">
			<?php settings_fields( 'wpcable_group' ); ?>
			<?php do_settings_sections( 'wpcable_group' ); ?>

			<h2><?php esc_html_e( 'Task list', 'wpcable' ); ?></h2>
			<p>
				<?php
				printf(
					esc_html__( 'Adjust behavior of %syour task list%s.', 'wpcable' ),
					'<a href="' . admin_url( 'admin.php?page=codeable_tasks') . '">',
					'</a>'
				);
				?>
			</p>

			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label class="wpcable_label" for="wpcable_cancel_after_days">
							<?php esc_html_e( 'Flag task as canceled', 'wpcable' ); ?>
						</label>
					</th>
					<td>
						<input type="number" name="wpcable_cancel_after_days" id="wpcable_cancel_after_days" min="14" max="720" value="<?php echo (int) $wpcable_cancel_after_days; ?>" /> days
						<p class="description">
							<?php esc_html_e( 'Adds the "canceled" flag to a task that had no activity for the given number of days. Default is 180 days.', 'wpcable' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label class="wpcable_label" for="wpcable_tasks_stop_at_page">
							<?php esc_html_e( 'Stop pulling tasks after page', 'wpcable' ); ?>
						</label>
					</th>
					<td>
						<input type="number" name="wpcable_tasks_stop_at_page" id="wpcable_tasks_stop_at_page" min="0" max="720" value="<?php echo (int) $wpcable_tasks_stop_at_page; ?>" /> pages
						<p class="description">
							<?php esc_html_e( 'Pull a specific number of pages for the tasks section. Default is 0 (pull all). We suggest setting it to 0 to retrieve ALL tasks the first time, and then set it to 1 or 2 to retrieve/update only the latest 1/2 pages.', 'wpcable' ); ?>
						</p>
					</td>
				</tr>
				</tbody>
			</table>

			<hr />
			<h2><?php esc_html_e( 'Estimates', 'wpcable' ); ?></h2>
			<p>
				<?php
				printf(
					esc_html__( 'Customize the defaults for %syour estimates%s.', 'wpcable' ),
					'<a href="' . admin_url( 'admin.php?page=codeable_estimate') . '">',
					'</a>'
				);
				?>
			</p>

			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label class="wpcable_label" for="wpcable_rate">
							<?php esc_html_e( 'Your hourly rate', 'wpcable' ); ?>
						</label>
					</th>
					<td>
						<input
							id="wpcable_rate"
							type="number"
							min="35"
							max="1000"
							style="width:80px"
							name="wpcable_rate"
							value="<?php echo (float) $wpcable_rate; ?>"
						/> $
						<p class="description">
							<?php esc_html_e( 'Used as default value on the estimate page', 'wpcable' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label class="wpcable_label" for="wpcable_fee_type">
							<?php esc_html_e( 'Fee calculation', 'wpcable' ); ?>
						</label>
					</th>
					<td>
						<select id="wpcable_fee_type" name="wpcable_fee_type">
							<option value="full" <?php selected( 'full', $wpcable_fee_type ); ?>>
								<?php esc_html_e( 'My rate is what I want to get paid, without any fees', 'wpcable' ); ?>
							</option>
							<option value="client" <?php selected( 'client', $wpcable_fee_type ); ?>>
								<?php esc_html_e( 'My rate includes my fee (10%) but not the client fee', 'wpcable' ); ?>
							</option>
							<option value="none" <?php selected( 'none', $wpcable_fee_type ); ?>>
								<?php esc_html_e( 'My rate includes all fees', 'wpcable' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'This information is used on the estimate page', 'wpcable' ); ?>
						</p>
					</td>
				</tr>
				</tbody>
			</table>

			<hr />
			<h2><?php esc_html_e( 'Codeable API', 'wpcable' ); ?></h2>
			<p>
				<?php esc_html_e( 'Log into your Codeable account.', 'wpcable' ); ?><br />
				<?php esc_html_e( 'During login this plugin obtains a user-specific auth-token which is used to fetch data from the API later. Your password is not saved anywhere!', 'wpcable' ); ?>
			</p>

			<table class="form-table">
				<tbody>
				<?php if ( codeable_api_logged_in() ) : ?>
					<tr>
						<th scope="row">
							<label class="wpcable_label" for="wpcable_email">
								<?php esc_html_e( 'Account', 'wpcable' ); ?>
							</label>
						</th>
						<td>
							<p>
								<?php
								printf(
									__( 'You are currently logged in as %s', 'wpcable' ),
									'<b>' . $wpcable_email . '</b>'
								);
								?>
								<input type="hidden" name="wpcable_email" value="<?php echo esc_attr( $wpcable_email ); ?>" />
							</p>
							<p>
								<a href="<?php echo esc_url( $logout_url ); ?>" class="button" ><?php esc_html_e( 'Log out', 'wpcable' ); ?></a>
								<a href="<?php echo esc_url( $flush_data_url ); ?>" class="button danger" onclick="return confirm('<?php echo esc_attr( $flush_data_warning ); ?>')"><?php esc_html_e( 'Clear all data', 'wpcable' ); ?></a>
							</p>
						</td>
					</tr>
				<?php else : ?>
					<tr>
						<th scope="row">
							<label class="wpcable_label" for="wpcable_email">
								<?php esc_html_e( 'Email', 'wpcable' ); ?>
							</label>
						</th>
						<td>
							<input
								id="wpcable_email"
								type="email"
								name="wpcable_email"
								class="regular-text"
								value="<?php echo esc_attr( $wpcable_email ); ?>"
								autocomplete="email"
							/>
							<p class="description"><?php esc_html_e( 'This is the email address you use to log into app.codeable.com', 'wpcable' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label class="wpcable_label" for="wpcable_password">
								<?php esc_html_e( 'Password', 'wpcable' ); ?>
							</label>
						</th>
						<td>
							<input
								id="wpcable_password"
								type="password"
								name="wpcable_password"
								class="regular-text"
								value=""
								autocomplete="password"
							/>
							<p class="description"><?php esc_html_e( 'Your Codeable password is not stored anywhere!<br />With your password we generate an auth_token, that is saved encrypted in your DB.', 'wpcable' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>

			<div class="action-buttons">
				<?php submit_button( __( 'Save Changes', 'wpcable' ) ); ?>
			</div>

		</form>

		<?php codeable_last_fetch_info(); ?>
	</div>
	<?php
}
