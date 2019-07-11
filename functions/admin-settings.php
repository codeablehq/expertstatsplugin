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
	$nonce = false;

	if ( ! empty( $_REQUEST['_wpnonce'] ) ) {
		$nonce = wp_unslash( $_REQUEST['_wpnonce'] );
	}

	if ( $nonce && wp_verify_nonce( $nonce, 'logout' ) ) {
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

	$wpcable_email    = get_option( 'wpcable_email' );
	$wpcable_rate     = get_option( 'wpcable_rate', 80 );
	$wpcable_fee_type = get_option( 'wpcable_fee_type', 'client' );

	$logout_url = wp_nonce_url(
		add_query_arg( 'action', 'logout' ),
		'logout'
	);

	?>
	<div class="wrap wpcable_wrap">
		<form method="post" action="options.php">
			<h2><?php _e( 'Codeable settings', 'wpcable' ); ?></h2>

			<table class="form-table">
				<tbody>

				<?php settings_fields( 'wpcable_group', '_wpnonce', false ); ?>
				<input
					type="hidden"
					name="_wp_http_referer"
					value="<?php echo remove_query_arg( ['success', 'error' ] ); ?>"
				/>
				<?php do_settings_sections( 'wpcable_group' ); ?>

				<tr>
					<th scope="row">
						<label class="wpcable_label" for="wpcable_rate">
							<?php _e( 'Your hourly rate', 'wpcable' ); ?>
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
							<?php _e( 'Used as default value on the estimate page', 'wpcable' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label class="wpcable_label" for="wpcable_fee_type">
							<?php _e( 'Fee calculation', 'wpcable' ); ?>
						</label>
					</th>
					<td>
						<select id="wpcable_fee_type" name="wpcable_fee_type">
							<option value="full" <?php selected( 'full', $wpcable_fee_type ); ?>>
								<?php _e( 'My rate is what I want to get paid, without any fees', 'wpcable' ); ?>
							</option>
							<option value="client" <?php selected( 'client', $wpcable_fee_type ); ?>>
								<?php _e( 'My rate includes my fee (10%) but not the client fee', 'wpcable' ); ?>
							</option>
							<option value="none" <?php selected( 'none', $wpcable_fee_type ); ?>>
								<?php _e( 'My rate includes all fees', 'wpcable' ); ?>
							</option>
						</select>
						<p class="description">
							<?php _e( 'This information is used on the estimate page', 'wpcable' ); ?>
						</p>
					</td>
				</tr>
				<?php if ( codeable_api_logged_in() ) : ?>
					<tr>
						<th scope="row">
							<label class="wpcable_label" for="wpcable_email">
								<?php _e( 'Account', 'wpcable' ); ?>
							</label>
						</th>
						<td>
							<p>
								<?php
								printf(
									__( 'You are currently logged in as %s. %sLog out and clear all data%s', 'wpcable' ),
									'<b>' . $wpcable_email . '</b>',
									'<a href="' . $logout_url . '">',
									'</a>'
								);
								?>
							</p>
						</td>
					</tr>
				<?php else : ?>
					<tr>
						<th scope="row">
							<label class="wpcable_label" for="wpcable_email">
								<?php _e( 'Email', 'wpcable' ); ?>
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
							<p class="description"><?php _e( 'This is the email address you use to log into app.codeable.com', 'wpcable' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label class="wpcable_label" for="wpcable_password">
								<?php _e( 'Password', 'wpcable' ); ?>
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
							<p class="description"><?php _e( 'Your Codeable password is not stored anywhere!<br />With your password we generate an auth_token, that is saved encrypted in your DB.', 'wpcable' ); ?></p>
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
