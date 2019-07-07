<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wpcable_settings_fields() {


	// create array for all the fields we want to export
	$wpcable_settings_fields = array(
		'email'    => __( 'email', 'wpcable' ),
		'password' => __( 'password', 'wpcable' ),
	);

	return $wpcable_settings_fields;
}

add_action( 'admin_menu', 'wpcable_options', 100 );

function wpcable_options() {
	add_submenu_page( 'codeable_transcactions_stats', 'Settings', 'Settings', 'manage_options', 'codeable_settings', 'codeable_settings_callback' );
    add_submenu_page( 'codeable_transcactions_stats', 'Estimate', 'Estimate', 'manage_options', 'codeable_estimate', 'codeable_estimate_callback' );
	add_action( 'admin_init', 'codeable_register_settings' );
}

// register settings
function codeable_register_settings() {

	$wpcable_settings_fields = wpcable_settings_fields();

	register_setting( 'wpcable_group', 'wpcable' );
	foreach ( $wpcable_settings_fields as $key => $field ) {
		register_setting( 'wpcable_group', 'wpcable_' . $key );
	}

	register_setting( 'wpcable_group', 'wpcable_what_to_check' );
	register_setting( 'wpcable_group', 'wpcable_skip_zero_months' );
}

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

function codeable_settings_callback() {
	global $wpdb;

    if( ! empty($_GET['wpcable_error']) && $_GET['wpcable_error'] === 'credentials') : ?>

        <div class="notice notice-error">
            <p><?php echo __( 'Invalid username or password', 'wpcable' ) ?></p>
        </div>

    <?php endif;

	if( codeable_ssl_warning() ) : ?>
        <div class="update-nag notice">
            <p><?php echo __( 'Please consider installing this plugin on a secure website', 'wpcable' ); ?></p>
        </div>

	<?php endif;

	set_time_limit( 300 );

	$wpcable_settings_fields = wpcable_settings_fields();

	$wpcable_what_to_check    = get_option( 'wpcable_what_to_check' );
	$wpcable_skip_zero_months = get_option( 'wpcable_skip_zero_months' );

	// truncate all data
	if ( isset( $_GET['flushdata'] ) ) {

		$tables = array(
			$wpdb->prefix . 'codeable_transcactions' => __( 'Transcactions', 'wpcable' ),
			$wpdb->prefix . 'codeable_clients'       => __( 'Clients', 'wpcable' ),
			$wpdb->prefix . 'codeable_amounts'       => __( 'Amounts', 'wpcable' )
		);

		foreach ( $tables as $db_table => $db_label ) {

			if ( $wpdb->query( 'TRUNCATE ' . $db_table ) === true ) {
				?>
                <div class="updated notice">
                    <p><?php echo $db_label . ' (' . $db_table . ') ' . __( 'table truncated!', 'wpcable' ); ?></p>
                </div>
				<?php
			} else {
				?>
                <div class="error notice">
                    <p><?php echo $db_label . ' (' . $db_table . ') ' . __( 'table could not be truncated!', 'wpcable' ); ?></p>
                </div>
				<?php
			}
		}

        // flush object cache
		wpcable_cache::flush();

	}


	if ( isset( $_POST['wpcable_email'] ) && isset( $_POST['wpcable_password'] ) ) {

		$nonce = wp_verify_nonce( $_POST['wpcable_fetch_nonce'], 'wpcable_fetch' );
		$total = 0;

		if ( $nonce ) {
			$email    = sanitize_email( $_POST['wpcable_email'] );
			$password = $_POST['wpcable_password'];

			$wpcable_transcactions = new wpcable_transcactions( $email, $password );
			$total                 = $wpcable_transcactions->store_transactions();

			// flush object cache
			wpcable_cache::flush();

		}

		?>
        <div class="updated notice">
            <p><?php echo $total . ' ' . __( 'new entries', 'wpcable' ); ?>! <a
                        href="<?php echo admin_url( 'admin.php?page=codeable_transcactions_stats' ); ?>"><?php echo __( 'See the stats', 'wpcable' ); ?></a>
            </p>
        </div>
		<?php
	}

	?>
    <div class="wrap wpcable_wrap">
        <form method="post" action="options.php">
            <h2><?php _e( 'Codeable settings', 'wpcable' ); ?></h2>

            <table class="form-table">
                <tbody>


				<?php settings_fields( 'wpcable_group' ); ?>
				<?php do_settings_sections( 'wpcable_group' ); ?>

				<?php
				echo '
              <tr>
                <th scope="row">
                  <label class="wpcable_label" for="wpcable_what_to_check">' . __( 'Scan method', 'wpcable' ) . '</label> 
                </th>
                <td>
                  <select id="wpcable_what_to_check" name="wpcable_what_to_check">
                    <option value="0" ' . ( $wpcable_what_to_check == 0 ? 'selected="selected"' : '' ) . ' >' . __( 'Stop if the transaction id is found (use this if you want to update your data of first time fetch)' ) . '</option>
                    <option value="1" ' . ( $wpcable_what_to_check == 1 ? 'selected="selected"' : '' ) . '>' . __( 'Check everything (use this if you got a time out while fetching)' ) . '</option>
                  </select>
                </td>
                
              </tr>';


				?>


                </tbody>
            </table>

			<?php

			echo '<div class="action-buttons">';
			submit_button( __( 'Save Changes', 'wpcable' ) );
			echo '</div>';
			?>
        </form>
    </div>


    <div class="wrap wpcable_wrap">
        <form method="post" action="admin.php?page=codeable_settings">
            <h2><?php _e( 'Fetch data', 'wpcable' ); ?></h2>

            <table class="form-table">
                <tbody>

				<?php

				wp_nonce_field( 'wpcable_fetch', 'wpcable_fetch_nonce' );
				foreach ( $wpcable_settings_fields as $key => $label ) {

					echo '
              <tr>
                <th scope="row">
                  <label class="wpcable_label" for="wpcable_' . $key . '">' . $label . '</label> 
                </th>
                <td>
                <input id="wpcable_' . $key . '" type="' . ( $key == 'password' ? 'password' : 'text' ) . '" name="wpcable_' . $key . '" value="" autocomplete="new-password" />
                </td>
                
              </tr>';
				}


				?>


                </tbody>
            </table>

            <p>
                <small><?php echo __( 'Be sure that you set PHP timeout to 120 or more on your first fetch or if you have deleted the cached data', 'wpcable' ); ?></small>
            </p>
            <input name="submit" id="" class="button button-large button-action"
                   value="<?php echo __( 'Fetch remote data', 'wpcable' ); ?>" type="submit">
            <a href="admin.php?page=codeable_settings&flushdata=true"
               class="button button-large button-danger"><?php echo __( 'Delete cached data', 'wpcable' ); ?></a>

        </form>
    </div>

    <style>

        .button.button-action {
            background: #5CB85C;
            border-color: #4CAE4C;
            color: #ffffff;
            font-weight: bold;
            -webkit-box-shadow: none;
            -moz-box-shadow: none;
            box-shadow: none;
        }

        .button.button-action:hover {
            background: #449D44;
            border-color: #398439;
            color: #ffffff;
        }

        .button.button-danger {
            background: #D9534F;
            border-color: #D43F3A;
            color: #ffffff;
            -webkit-box-shadow: none;
            -moz-box-shadow: none;
            box-shadow: none;
        }

        .button.button-danger:hover {
            background: #C9302C;
            border-color: #AC2925;
            color: #ffffff;
        }

        .button.button-warning {
            padding-left: 25px;
            padding-right: 25px;
            font-weight: bold;
            background: #F0AD4E;
            border-color: #EEA236;
            color: #ffffff;
            -webkit-box-shadow: none;
            -moz-box-shadow: none;
            box-shadow: none;
        }

        .button.button-warning:hover {
            background: #EC971F;
            border-color: #D58512;
            color: #ffffff;
        }
    </style>
	<?php
}
