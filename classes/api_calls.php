<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class wpcable_api_calls {

	private $email = '';
	private $password = '';
	public $auth_token = '';


	public function __construct( $email, $password ) {

		$this->email    = $email;
		$this->password = $password;
	}

	public function login() {

		$args             = array( 'email' => $this->email, 'password' => $this->password );
		$url              = 'https://api.codeable.io/users/login';
		$login_call       = $this->get_curl( $url, $args );
		$this->auth_token = $login_call['auth_token'];

		// return $this->auth_token;
		return $login_call;
	}


	public function transactions_page( $page = 1 ) {

		$url                = 'https://api.codeable.io/users/me/transactions';
		$args               = array( 'page' => $page );
		$CURLOPT_HTTPHEADER = array(
			"Authorization: Bearer " . $this->auth_token
		);

		$transaction = $this->get_curl( $url, $args, 'get', $CURLOPT_HTTPHEADER );

		return $transaction;

	}

	public function transactions_full( $maxpage = 999999 ) {

		$transactions = array();

		$page = 1;
		while ( $page < $maxpage ) {
			$single_page = $this->transactions_page( $page ++ );

			if ( empty( $single_page['transactions'] ) ) {
				return $transactions;
			} else {
				$transactions = array_merge( $transactions, $single_page['transactions'] );
			}
		}

	}


	function get_curl( $url, $args, $method = 'post', $CURLOPT_HTTPHEADER = '' ) {

		try {
			$ch = curl_init();

			if ( false === $ch ) {
				throw new Exception( 'failed to initialize' );
			}

			if ( $method == 'get' ) {
				if ( ! empty( $args ) ) {
					$url = $url . '?' . http_build_query( $args );
				}
			} else {
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args );
			}


			# Setup request to send json via POST.
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

			if ( ! empty( $CURLOPT_HTTPHEADER ) ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER );
			}

			# Send request.
			$content = curl_exec( $ch );

			if ( false === $content ) {
				// throw new Exception(curl_error($ch), curl_errno($ch));

				echo '<pre>' . print_r( $url, true ) . '</pre>';

				echo '<pre>';
				print_r( curl_error( $ch ) );
				echo '</pre>';

				echo '<pre>';
				print_r( curl_errno( $ch ) );
				echo '</pre>';

				die;
			}
			curl_close( $ch );


			return json_decode( $content, true );

		} catch ( Exception $e ) {

			trigger_error( sprintf(
				'Curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage() ),
				E_USER_ERROR );

		}
	}


}
