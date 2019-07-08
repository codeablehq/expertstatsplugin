<?php
/**
 * Helper functions used by other modules.
 *
 * @package wpcable
 */

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
