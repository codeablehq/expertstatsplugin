<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class wpcable_transcactions
{

  private $api_calls = '';
  private $debug = false;
  public $tables = array();


  public function __construct($email, $password)
  {

    global $wpdb;

    $this->tables = array(
      'transcactions' => $wpdb->prefix . 'codeable_transcactions',
      'clients'       => $wpdb->prefix . 'codeable_clients',
      'amounts'       => $wpdb->prefix . 'codeable_amounts'
    );

    $this->api_calls = new wpcable_api_calls($email, $password);

  }


  // return the number of transcactions stored
  public function store_transactions($maxpage = 999999)
  {

    global $wpdb;

    if ($this->debug) {
      $wpdb->show_errors();
    }

    $account_details = $this->api_calls->login();

    update_option('wpcable_account_details', $account_details);

    $total = 0;
    $page  = 1;
    while ($page < $maxpage) {

      $single_page = $this->api_calls->transactions_page($page++);

      if ($page == 2) {
        update_option('wpcable_average', $single_page['average_task_size']);
        update_option('wpcable_balance', $single_page['balance']);
        update_option('wpcable_revenue', $single_page['revenue']);
      }

      if (empty($single_page['transactions'])) {
        return $total;
      } else {

        // get all data to the DB                
        foreach ($single_page['transactions'] as $tr) {

          // check if transcactions already exists
          $check = $wpdb->get_results("SELECT count(*) as totalrows FROM " . $this->tables['transcactions'] . " WHERE id = '" . $tr['id'] . "'");

          // if the record exists then return total
          if ($check[0]->totalrows > 0) {

            if (get_option('wpcable_what_to_check') == 1) {
              continue;
            } else {
              return $total;
            }
          }

          $new_tr = array(
            'id'             => $tr['id'],
            'description'    => $tr['description'],
            'dateadded'      => date('Y-m-d H:i:s', $tr['timestamp']),
            'fee_percentage' => $tr['fee_percentage'],
            'fee_amount'     => $tr['fee_amount'],
            'task_type'      => $tr['task']['kind'],
            'task_id'        => $tr['task']['id'],
            'task_title'     => $tr['task']['title'],
            'parent_task_id' => ($tr['task']['parent_task_id'] > 0 ? $tr['task']['parent_task_id'] : 0),
            'preferred'      => $tr['task']['current_user_is_preferred_contractor'],
            'client_id'      => $tr['task_client']['id']
          );

          $insert_transcaction = $wpdb->insert(
            $this->tables['transcactions'],
            $new_tr
          );

          if ($insert_transcaction === false) {
            die('could not insert transcactions ' . $tr['id'] . ':' . $wpdb->print_error());
          }


          // check if transcactions already exists
          $check_client = $wpdb->get_results("SELECT count(*) as totalrows FROM " . $this->tables['clients'] . " WHERE client_id = '" . $tr['task_client']['id'] . "'");

          // if the client record exists then return total
          if (!($check_client[0]->totalrows > 0)) {

            $new_client = array(
              'client_id'       => $tr['task_client']['id'],
              'full_name'       => $tr['task_client']['full_name'],
              'role'            => $tr['task_client']['role'],
              'last_sign_in_at' => date('Y-m-d H:i:s', strtotime($tr['task_client']['last_sign_in_at'])),
              'pro'             => $tr['task_client']['pro'],
              'timezone_offset' => $tr['task_client']['timezone_offset'],
              'tiny'            => $tr['task_client']['avatar']['tiny_url'],
              'small'           => $tr['task_client']['avatar']['small_url'],
              'medium'          => $tr['task_client']['avatar']['medium_url'],
              'large'           => $tr['task_client']['avatar']['large_url'],
            );

            $insert_client = $wpdb->replace(
              $this->tables['clients'],
              $new_client
            );

          }

          $new_amount = array(
            'task_id'               => $tr['task']['id'],
            'client_id'             => $tr['task_client']['id'],
            'credit_revenue_id'     => $tr['credit_amounts'][0]['id'],
            'credit_revenue_amount' => $tr['credit_amounts'][0]['amount'],
            'credit_fee_id'         => $tr['credit_amounts'][1]['id'],
            'credit_fee_amount'     => $tr['credit_amounts'][1]['amount'],
            'credit_user_id'        => $tr['credit_amounts'][2]['id'],
            'credit_user_amount'    => $tr['credit_amounts'][2]['amount'],
            'debit_cost_id'         => $tr['debit_amounts'][0]['id'],
            'debit_cost_amount'     => $tr['debit_amounts'][0]['amount'],
            'debit_user_id'         => $tr['debit_amounts'][1]['id'],
            'debit_user_amount'     => $tr['debit_amounts'][1]['amount'],
          );

          $insert_amount = $wpdb->insert(
            $this->tables['amounts'],
            $new_amount
          );

          $total++;


        }

      }
    }

  }

}
