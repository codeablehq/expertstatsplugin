<?php
/**
 * Helper functions used by other modules.
 *
 * @package wpcable
 */

function wpcable_money( $money ) {
	return number_format( $money, 2, '.', ',' );
}

function wpcable_compare_values( $from = 0, $to = 0 ) {
	return $from >= $to ? 'increase' : 'decrease';
}

function wpcable_date( $value ) {
	if ( is_string( $value ) ) {
		$time = strtotime( $value );
	} elseif ( is_numeric( $value ) ) {
		$time = (int) $value;
	} else {
		return false;
	}

	$format_string = get_option( 'date_format' ) . ' (' . get_option( 'time_format' ) . ')';
	return date_i18n( $format_string, $time );
}

/**
 * Prepares an URL for a redirect to show certain messages.
 *
 * @param string $type   Either [error|success].
 * @param mixed  $values The values to send (a single string or array of strings).
 * @param string $url    Optionally a different target URL.
 * @return string Full URL containing the custom query params.
 */
function codeable_add_message_param( $type, $values, $url = '' ) {
	if ( ! $url ) {
		$url = admin_url( 'admin.php?page=codeable_settings' );
	} else {
		$url = remove_query_arg( [ '_wpnonce', 'action' ], $url );
	}

	if ( 'error' !== $type ) {
		$type = 'success';
	}
	if ( is_scalar( $values ) ) {
		$values = [ $values ];
	}

	$values     = array_merge( $values, codeable_get_message_param( $type, $url ) );
	$values     = array_unique( $values );
	$values_enc = array_map( 'urlencode', $values );
	$values_enc = base64_encode( implode( ',', $values_enc ) );

	return add_query_arg( $type, $values_enc, $url );
}

/**
 * Returns the required message params from the given URL (or current URL, when no
 * URL is specified in params)
 *
 * @param string $type Which messages to return [error|success].
 * @param string $url  Optionally a custom URL to parse.
 * @return array List of message params.
 */
function codeable_get_message_param( $type, $url = '' ) {
	$values = [];

	if ( $url ) {
		$parts = parse_url( $url );
		parse_str( $parts['query'], $params );
	} else {
		$params = $_REQUEST;
	}

	if ( 'error' !== $type ) {
		$type = 'success';
	}

	if ( isset( $params[ $type ] ) ) {
		$values = base64_decode( $params[ $type ] );

		if ( $values ) {
			$values = explode( ',', $values );
			$values = array_map( 'urldecode', $values );
		}
	}

	return is_array( $values ) ? $values : [];
}

/**
 * Check if an SSL warning should be displayed.
 *
 * @return bool
 */
function codeable_ssl_warning() {
	$check = 'auto';
	if ( defined( 'CODEABLE_SSL_CHECK' ) ) {
		$check = CODEABLE_SSL_CHECK;
	}

	if ( 'off' === $check ) {
		return false;
	}

	if ( 'auto' === $check ) {
		if ( '127.0.0.1' === $_SERVER['REMOTE_ADDR'] ) {
			// "Remote" server is on local machine, i.e. development server.
			return false;
		} elseif ( preg_match( '/\.local$/', $_SERVER['HTTP_HOST'] ) ) {
			// A ".local" domain, i.e. development server.
			return false;
		}
	}

	return ! is_ssl();
}

/**
 * Check if an PHP Timout warning should be displayed.
 *
 * @return bool
 */
function codeable_timeout_warning() {
	$timeout = ini_get( 'max_execution_time' );

	if ( $timeout < 120 ) {
		return true;
	}

	return false;
}

/**
 * Outputs default notices on all Codeable Stats pages.
 *
 * @return void
 */
function codeable_admin_notices() {
	$errors  = codeable_get_message_param( 'error' );
	$success = codeable_get_message_param( 'success' );

	if ( $errors ) :
		?>
		<div class="notice error">
			<?php if ( in_array( 'credentials', $errors, true ) ) : ?>
				<p><?php _e( 'Invalid username or password', 'wpcable' ) ?></p>
			<?php else : ?>
				<p><?php echo implode( '<br />', $errors ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	endif;

	if ( $success ) :
		?>
		<div class="notice updated">
			<?php if ( in_array( 'fetched', $success, true ) ) : ?>
				<p><?php _e( 'Fetched latest details from API.', 'wpcable' ) ?></p>
			<?php else : ?>
				<p><?php echo implode( '<br />', $success ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	endif;

	if( codeable_ssl_warning() ) :
		?>
		<div class="update-nag notice">
			<p><?php _e( 'Please consider installing this plugin on a secure website', 'wpcable' ); ?></p>
		</div>
		<?php
	endif;

	if ( codeable_timeout_warning() ) :
		?>
		<div class="update-nag notice">
			<p><?php _e( 'Be sure that you set PHP timeout to 120 or more on your first fetch or if you have deleted the cached data', 'wpcable' ); ?></p>
		</div>
		<?php
	endif;
}

/**
 * Displays a login-nag when the user is not logged in
 */
function codeable_page_requires_login( $page_title ) {
	if ( codeable_api_logged_in() ) {
		return;
	}

	?>
	<div class="wrap">
		<h1><?php echo $pag_titlee; ?></h1>

		<div class="notice error">
			<p>
				<?php
				printf(
					__( 'Please %slog in%s to access your stats.', 'wpcable' ),
					'<a href="' . admin_url( 'admin.php?page=codeable_settings' ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
	</div>
	<?php
	exit;
}

/**
 * Flushes all locally stored data and forgets the authentication token.
 *
 * @return void
 */
function codeable_flush_all_data() {
	global $wpdb;

	$tables = [
		$wpdb->prefix . 'codeable_transcactions' => __( 'Transcactions', 'wpcable' ),
		$wpdb->prefix . 'codeable_clients'       => __( 'Clients', 'wpcable' ),
		$wpdb->prefix . 'codeable_amounts'       => __( 'Amounts', 'wpcable' ),
		$wpdb->prefix . 'codeable_tasks'         => __( 'Tasks', 'wpcable' ),
	];

	$redirect_to = '';

	foreach ( $tables as $db_table => $db_label ) {
		if ( true === $wpdb->query( "TRUNCATE `{$db_table}`;" ) ) {
			$redirect_to = codeable_add_message_param(
				'success',
				sprintf(
					__( 'All %s removed.', 'wpcable' ),
					'<b>' . esc_html( $db_label ) . '</b>'
				),
				$redirect_to
			);
		} else {
			$redirect_to = codeable_add_message_param(
				'error',
				sprintf(
					__( '%s could not be removed.', 'wpcable' ),
					'<b>' . esc_html( $db_label ) . '</b>'
				),
				$redirect_to
			);
		}
	}

	delete_option( 'wpcable_auth_token' );
	delete_option( 'wpcable_email' );
	delete_option( 'wpcable_average' );
	delete_option( 'wpcable_balance' );
	delete_option( 'wpcable_revenue' );
	delete_option( 'wpcable_last_fetch' );
	delete_option( 'wpcable_account_details' );

	$redirect_to = codeable_add_message_param(
		'success',
		sprintf(
			__( 'Forgot your <b>authentication</b> and <b>profile</b> details.', 'wpcable' ),
			esc_html( $db_label )
		),
		$redirect_to
	);

	// Flush object cache.
	wpcable_cache::flush();

	wp_safe_redirect( $redirect_to );
	exit;
}

/**
 * Checks, whether we should (auto) refresh the stats/pull data from API.
 *
 * @return void
 */
function codeable_maybe_refresh_data( $force = false ) {
	$sync_now = $force;

	if ( ! $sync_now ) {
		$last_fetch = (int) get_option( 'wpcable_last_fetch' );

		if ( ! $last_fetch ) {
			$sync_now = true;
		} else {
			$sync_now = time() - $last_fetch > HOUR_IN_SECONDS;
		}
	}

	if ( ! $sync_now ) {
		return;
	}

	$queue = get_option( 'wpcable_api_queue' );
	$data  = new wpcable_api_data();

	if ( empty( $queue ) || ! is_array( $queue ) ) {
		$queue = $data->prepare_queue();
		update_option( 'wpcable_api_queue', $queue );
	}
}

/**
 * Display a notice fo the last API fetch and a refresh-button.
 *
 * @return void
 */
function codeable_last_fetch_info() {
	$last_fetch = get_option( 'wpcable_last_fetch' );

	?>
	<div class="codeable-last-refresh">
		<?php _e( 'Last refresh: ', 'wpcable' ); ?>
		<?php echo wpcable_date( $last_fetch ); ?>
		<span class="tooltip" tabindex="0">
			<span class="tooltip-text"><?php _e( 'API details are fetched once per hour, or when you click the "Refresh" button on the right.', 'wpcable' ); ?></span>
			<i class="dashicons dashicons-info"></i>
		</span>
		|
		<a href="#refresh" class="sync-start">
			<?php _e( 'Refresh', 'wpcable' ); ?>
		</a>
	</div>
	<div class="codeable-sync-progress" style="display:none">
		<span class="spinner is-active"></span>
		<span class="msg"></span>
	</div>
	<?php
}

/**
 * Tests, whether the user is currently logged into the codeable API.
 *
 * @return bool
 */
function codeable_api_logged_in() {
	$api = wpcable_api_calls::inst();

	return $api->auth_token_known();
}

/**
 * Authenticate the user with given email/password and store the auth_token for
 * later usage in the DB (encrypted).
 *
 * @param  string $email    Users email address.
 * @param  string $password Password for authentication.
 * @return void
 */
function codeable_api_authenticate( $email, $password ) {
	$api = wpcable_api_calls::inst();
	$api->login( $email, $password );

	if ( $api->auth_token_known() ) :
		?>
		<div class="updated notice">
			<p>
				<?php _e( 'You are now logged in!', 'wpcable' ); ?>
			</p>
		</div>
		<?php
	endif;
}
