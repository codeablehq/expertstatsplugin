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
	register_setting( 'wpcable_group', 'wpcable' );
	register_setting( 'wpcable_group', 'wpcable_what_to_check' );

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
 * @param string $value
 * @param string $option
 * @param string $old_value
 * @return void
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

	$wpcable_email         = get_option( 'wpcable_email' );
	$wpcable_what_to_check = get_option( 'wpcable_what_to_check' );

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
						<label class="wpcable_label" for="wpcable_what_to_check">
							<?php _e( 'Import range', 'wpcable' ); ?>
						</label>
					</th>
					<td>
						<p>
							<label>
								<input
									type="radio"
									name="wpcable_what_to_check"
									value="0"
									<?php checked( 0, $wpcable_what_to_check ); ?>
								/>
								<?php _e( 'New items since last import', 'wpcable' ); ?>
								<span class="tooltip bottom" tabindex="0">
									<span class="tooltip-text">
										<?php _e( 'This is the recommended setting. It does a chronological import that stops when an imported item already exists in your DB.', 'wpcable' ); ?>
									</span>
									<i class="dashicons dashicons-info"></i>
								</span>
							</label>
						</p>
						<p>
							<label>
								<input
									type="radio"
									name="wpcable_what_to_check"
									value="2"
									<?php checked( 2, $wpcable_what_to_check ); ?>
								/>
								<?php _e( 'Always process all items', 'wpcable' ); ?>
								<span class="tooltip bottom" tabindex="0">
									<span class="tooltip-text">
										<?php _e( 'Use this when you hit timeouts or errors. This setting does not update existing entries, but always checks the entire API results for missing items.', 'wpcable' ); ?>
									</span>
									<i class="dashicons dashicons-info"></i>
								</span>
							</label>
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
