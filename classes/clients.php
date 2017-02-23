<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class wpcable_clients {
  
  public $tables = array();
  
  public function __construct() {
    
    global $wpdb;
    
    $this->tables = array(
      'transactions' => $wpdb->prefix .'codeable_transcactions',
      'clients' => $wpdb->prefix . 'codeable_clients',
      'amounts' => $wpdb->prefix . 'codeable_amounts'
    );
    
  }
  
  public function get_clients($from_month = '', $from_year = '', $to_month = '', $to_year = '') {

    global $wpdb;
  
    $clients = array();
    
    $firstdate = '';
    $lastdate = '';
    
    $wpcable_stats = new wpcable_stats;

    // get first and last task if no date is set
    if ($from_month == '' && $from_year == '') {
      $get_first_task   = $wpcable_stats->get_first_task();
      $firstdate = $get_first_task['dateadded'];
    } else {
      $firstdate = $from_year.'-'.$from_month.'-01';
    }
    if ($to_month == '' && $to_year == '') {
      $get_last_task    = $wpcable_stats->get_last_task();
      $lastdate = $get_last_task['dateadded'];
    } else {
      $lastdate = $to_year.'-'.$to_month.'-'.date('t', strtotime($to_year.'-'.$to_month.'-01 23:59:59'));
    }
    
    $query = " 
      SELECT
        *
      FROM 
        ". $this->tables['transactions'] ." 
        LEFT JOIN ". $this->tables['clients'] ."
      ON
        ". $this->tables['transactions'] .".client_id = ". $this->tables['clients'] .".client_id
        LEFT JOIN ". $this->tables['amounts'] ."
      ON 
        ". $this->tables['transactions'] .".task_ID = ". $this->tables['amounts'] .".task_ID
      WHERE 
            `description` = 'task_completion' OR `description` = 'partial_refund'
        AND (dateadded BETWEEN '". $firstdate ."' AND '". $lastdate ."')
    ";
    
    $result = $wpdb->get_results( $query, ARRAY_A );
    
    // echo '<pre>'.print_r($result, true).'</pre>';
    
    $client_variance = array();
    
    // loop transactions
    foreach($result as $tr) {
            
      $clients['clients'][$tr['client_id']]['client_id']        = $tr['client_id'];
      $clients['clients'][$tr['client_id']]['revenue']          = (strpos($tr['description'], 'refund') !== false ? $clients['clients'][$tr['client_id']]['revenue'] : $clients['clients'][$tr['client_id']]['revenue'] + $tr['credit_revenue_amount']);
      $clients['clients'][$tr['client_id']]['full_name']        = $tr['full_name'];
      $clients['clients'][$tr['client_id']]['role']             = $tr['role'];
      $clients['clients'][$tr['client_id']]['avatar']           = $tr['large'];
      $clients['clients'][$tr['client_id']]['total_tasks']      = $clients['clients'][$tr['client_id']]['total_tasks'] + 1;
      $clients['clients'][$tr['client_id']]['tasks']            = (strpos($tr['task_type'], 'subtask') === false ? $clients['clients'][$tr['client_id']]['tasks'] + 1 : $clients['clients'][$tr['client_id']]['tasks']);
      $clients['clients'][$tr['client_id']]['subtasks']         = (strpos($tr['task_type'], 'subtask') !== false ? $clients['clients'][$tr['client_id']]['subtasks'] + 1 : $clients['clients'][$tr['client_id']]['subtasks']);
      $clients['clients'][$tr['client_id']]['last_sign_in_at']  = $tr['last_sign_in_at'];
      $clients['clients'][$tr['client_id']]['timezone_offset']  = $tr['timezone_offset'];
      
      
      $clients['totals']['refunds']   = (strpos($tr['description'], 'refund') !== false ? $clients['totals']['refunds'] + 1 : $clients['totals']['refunds']);
      $clients['totals']['completed'] = (strpos($tr['description'], 'refund') === false ? $clients['totals']['completed'] + 1 : $clients['totals']['completed']);
      $clients['totals']['subtasks']  = (strpos($tr['task_type'], 'subtask') !== false ? $clients['totals']['subtasks'] + 1 : $clients['totals']['subtasks']);
      $clients['totals']['tasks']     = (strpos($tr['task_type'], 'subtask') === false ? $clients['totals']['tasks'] + 1 : $clients['totals']['tasks']);
      
      $clients['clients'][$tr['client_id']]['transactions'][] = array(
        'id'              => $tr['id'],
        'description'     => $tr['description'],
        'dateadded'       => $tr['dateadded'],
        'fee_percentage'  => $tr['fee_percentage'],
        'fee_amount'      => $tr['fee_amount'],
        'task_type'       => $tr['task_type'],
        'task_id'         => $tr['task_id'],
        'task_title'      => $tr['task_title'],
        'parent_task_id'  => $tr['parent_task_id'],
        'preferred'       => $tr['preferred'],
        'pro'             => $tr['pro'],
        'revenue'         => $tr['credit_revenue_amount'],
        'is_refund'       => (strpos($tr['description'], 'refund') !== false ? 1 : 0),
      );
      
      
      // foreach($clients['clients'] as $client) {
        // foreach($client['transactions'] as $tra) {
          // $client_variance[$client['client_id']][] = $tra['revenue'];
        // }
        
        
        // $clients['clients'][$client['client_id']]['variance'] = $this->standard_deviation($client_variance[$client['client_id']]);
      // }
      
      
    }
    
    return $clients;
    
  }
  
  
  public function standard_deviation($sample){
    if(is_array($sample)){
      $mean = array_sum($sample) / count($sample);
      foreach($sample as $key => $num) $devs[$key] = pow($num - $mean, 2);
      return sqrt(array_sum($devs) / (count($devs) - 1));
    }
  }
  
}
