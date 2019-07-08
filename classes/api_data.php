<?php
/**
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class wpcable_api_data {

	/**
	 * List of custom table names.
	 *
	 * @var array
	 */
	public $tables = [];

	/**
	 * An wpcable_api_calls object for communication with the API.
	 *
	 * @var wpcable_api_calls
	 */
	private $api_calls = null;

	/**
	 * Enable API debugging?
	 *
	 * @var bool
	 */
	private $debug = null;

	/**
	 * Initialize the object properties.
	 */
	public function __construct() {
		global $wpdb;

		$this->tables = [
			'transcactions' => $wpdb->prefix . 'codeable_transcactions',
			'clients'       => $wpdb->prefix . 'codeable_clients',
			'amounts'       => $wpdb->prefix . 'codeable_amounts',
			'tasks'         => $wpdb->prefix . 'codeable_tasks',
		];

		$this->api_calls = wpcable_api_calls::inst();

		$this->debug = defined( 'WP_DEBUG' ) ? WP_DEBUG : false;
	}

	/**
	 * Fetches the profile details AND THE AUTH_TOKEN from codeable.
	 *
	 * Must be called before any other store_* functions to ensure that we have
	 * a valid auth_token.
	 *
	 * @param string $email    E-Mail-Address of the current user.
	 * @param string $password Password of the current user.
	 * @return void
	 */
	public function store_profile( $email, $password ) {
		codeable_page_requires_login( __( 'API Refresh', 'wpcable' ) );

		$account_details = $this->api_calls->self();

		update_option( 'wpcable_account_details', $account_details );
	}

	/**
	 * Fetch transactions from the API and store them in our custom tables.
	 *
	 * @param  int $maxpage Optionally limit the number of crawled pages.
	 * @return int The number of transactions stored.
	 */
	public function store_transactions( $maxpage = 999999 ) {
		global $wpdb;

		codeable_page_requires_login( __( 'API Refresh', 'wpcable' ) );

		if ( $this->debug ) {
			$wpdb->show_errors();
		}

		$total = 0;
		$page  = 1;

		while ( $page < $maxpage ) {
			$single_page = $this->api_calls->transactions_page( $page ++ );

			if ( 2 === $page ) {
				update_option( 'wpcable_average', $single_page['average_task_size'] );
				update_option( 'wpcable_balance', $single_page['balance'] );
				update_option( 'wpcable_revenue', $single_page['revenue'] );
			}

			if ( empty( $single_page['transactions'] ) ) {
				return $total;
			} else {

				// Get all data to the DB.
				foreach ( $single_page['transactions'] as $tr ) {

					// Check if transactions already exists.
					$check = $wpdb->get_results(
						"SELECT COUNT(1) AS totalrows
						FROM `{$this->tables['transcactions']}`
						WHERE id = '{$tr['id']}';
						"
					);

					// If the record exists then return total.
					if ( $check[0]->totalrows > 0 ) {
						if ( 1 === get_option( 'wpcable_what_to_check' ) ) {
							continue;
						} else {
							return $total;
						}
					}

					$new_tr = [
						'id'             => $tr['id'],
						'description'    => $tr['description'],
						'dateadded'      => date( 'Y-m-d H:i:s', $tr['timestamp'] ),
						'fee_percentage' => $tr['fee_percentage'],
						'fee_amount'     => $tr['fee_amount'],
						'task_type'      => $tr['task']['kind'],
						'task_id'        => $tr['task']['id'],
						'task_title'     => $tr['task']['title'],
						'parent_task_id' => ( $tr['task']['parent_task_id'] > 0 ? $tr['task']['parent_task_id'] : 0 ),
						'preferred'      => $tr['task']['current_user_is_preferred_contractor'],
						'client_id'      => $tr['task_client']['id'],
					];

					// the API is returning some blank rows, ensure we have a valid client_id.
					if ( $new_tr['id'] && is_int( $new_tr['id'] ) ) {
						$insert_transaction = $wpdb->insert(
							$this->tables['transcactions'],
							$new_tr
						);
					}

					if ( $insert_transaction === false ) {
						wp_die(
							'Could not insert transactions ' .
							$tr['id'] . ':' .
							$wpdb->print_error()
						);
					}

					$this->store_client( $tr['task_client'] );
					$this->store_amount(
						$tr['task']['id'],
						$tr['task_client']['id'],
						$tr['credit_amounts'],
						$tr['debit_amounts']
					);

					$total ++;
				}
			}
		}
	}

	/**
	 * Fetch tasks from the API and store them in our custom tables.
	 *
	 * @param  int $maxpage Optionally limit the number of crawled pages.
	 * @return int The number of tasks stored.
	 */
	public function store_tasks( $maxpage = 999999 ) {
		global $wpdb;

		codeable_page_requires_login( __( 'API Refresh', 'wpcable' ) );

		if ( $this->debug ) {
			$wpdb->show_errors();
		}

		$filters = [ 'pending', 'active', 'archived', 'preferred' ];
		$total   = 0;

		foreach ( $filters as $filter ) {
			$page = 1;

			while ( $page < $maxpage ) {
				$single_page = $this->api_calls->tasks_page( $filter, $page ++ );

				if ( empty( $single_page ) ) {
					break;
				} else {

					// Get all data to the DB.
					foreach ( $single_page as $task ) {

						// Check if the task already exists.
						$check = $wpdb->get_results(
							"SELECT COUNT(1) AS totalrows
							FROM `{$this->tables['tasks']}`
							WHERE task_id = '{$task['id']}';
							"
						);

						// If the record exists then continue with next filter.
						if ( $check[0]->totalrows > 0 ) {
							if ( 1 === get_option( 'wpcable_what_to_check' ) ) {
								continue;
							} else {
								break;
							}
						}

						$new_task = [
							'task_id'    => $task['id'],
							'client_id'  => $task['client']['id'],
							'title'      => $task['title'],
							'estimate'   => $task['estimatable'],
							'hidden'     => $task['hidden_by_current_user'],
							'promoted'   => $task['promoted_task'],
							'subscribed' => $task['subscribed_by_current_user'],
							'favored'    => $task['favored_by_current_user'],
							'preferred'  => $task['current_user_is_preferred_contractor'],
							'client_fee' => $task['prices']['client_fee_percentage'],
							'state'      => $task['state'],
							'kind'       => $task['kind'],
						];

						// The API is returning some blank rows, ensure we have a valid id.
						if ( $new_task['task_id'] && is_int( $new_task['task_id'] ) ) {
							$insert_task = $wpdb->insert(
								$this->tables['tasks'],
								$new_task
							);
						}

						if ( $insert_task === false ) {
							wp_die(
								'Could not insert task ' .
								$task['id'] . ':' .
								$wpdb->print_error()
							);
						}

						$this->store_client( $task['client'] );

						$total ++;
					}
				}
			}
		}

		return $total;
	}

	/**
	 * Insert new clients to the clients-table.
	 *
	 * @param  array $client Client details.
	 * @return void
	 */
	private function store_client( $client ) {
		global $wpdb;

		// The API is returning some blank rows, ensure we have a valid client_id.
		if ( ! $client || ! is_int( $client['id'] ) ) {
			return;
		}

		// Check, if the client already exists.
		$check_client = $wpdb->get_results(
			"SELECT COUNT(1) AS totalrows
			FROM `{$this->tables['clients']}`
			WHERE client_id = '{$client['id']}';"
		);

		// When the client already exists, stop here.
		if ( $check_client[0]->totalrows > 0 ) {
			return;
		}

		$new_client = [
			'client_id'       => $client['id'],
			'full_name'       => $client['full_name'],
			'role'            => $client['role'],
			'last_sign_in_at' => date( 'Y-m-d H:i:s', strtotime( $client['last_sign_in_at'] ) ),
			'pro'             => $client['pro'],
			'timezone_offset' => $client['timezone_offset'],
			'tiny'            => $client['avatar']['tiny_url'],
			'small'           => $client['avatar']['small_url'],
			'medium'          => $client['avatar']['medium_url'],
			'large'           => $client['avatar']['large_url'],
		];

		$wpdb->insert( $this->tables['clients'], $new_client );
	}

	/**
	 * Insert pricing details into the amounts table.
	 *
	 * @param  array $client Client details.
	 * @return void
	 */
	private function store_amount( $task_id, $client_id, $credit, $debit ) {
		global $wpdb;

		// The API is returning some blank rows, ensure we have a valid client_id.
		if ( ! $task_id || ! is_int( $task_id ) ) {
			return;
		}

		$new_amount = [
			'task_id'               => $task_id,
			'client_id'             => $client_id,
			'credit_revenue_id'     => $credit[0]['id'],
			'credit_revenue_amount' => $credit[0]['amount'],
			'credit_fee_id'         => $credit[1]['id'],
			'credit_fee_amount'     => $credit[1]['amount'],
			'credit_user_id'        => $credit[2]['id'],
			'credit_user_amount'    => $credit[2]['amount'],
			'debit_cost_id'         => $debit[0]['id'],
			'debit_cost_amount'     => $debit[0]['amount'],
			'debit_user_id'         => $debit[1]['id'],
			'debit_user_amount'     => $debit[1]['amount'],
		];

		$wpdb->insert( $this->tables['amounts'], $new_amount );
	}
}
