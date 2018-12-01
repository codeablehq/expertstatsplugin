<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class wpcable_stats {

	public $tables = array();

	public function __construct() {

		global $wpdb;

		$this->tables = array(
			'transcactions' => $wpdb->prefix . 'codeable_transcactions',
			'clients'       => $wpdb->prefix . 'codeable_clients',
			'amounts'       => $wpdb->prefix . 'codeable_amounts'
		);

	}

	public function get_dates_totals( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        SUM(fee_amount) as fee_amount,
	        SUM(credit_fee_amount) as contractor_fee,
	        SUM(credit_revenue_amount) as revenue,
	        SUM(debit_user_amount) as total_cost,
	        count(1) as tasks
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion'
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
	    ";

		// check cache
		$cache_key = 'date_totals_' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );

		$single_result = array_shift( $result );

		return $single_result;

	}

	public function get_days( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        fee_amount as fee_amount,
	        credit_fee_amount as contractor_fee,
	        credit_revenue_amount as revenue,
	        debit_user_amount as total_cost,
	        dateadded
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion'
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
	    ";

		// check cache
		$cache_key = 'days_' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );

		$days_totals = array();
		foreach ( $result as $single_payment ) {

			$datekey = date( 'Ymd', strtotime( $single_payment['dateadded'] ) );

			if ( isset( $days_totals[ $datekey ] ) ) {

				$days_totals[ $datekey ]['fee_amount'] += $single_payment['fee_amount'];
				$days_totals[ $datekey ]['contractor_fee'] += $single_payment['contractor_fee'];
				$days_totals[ $datekey ]['revenue'] += $single_payment['revenue'];
				$days_totals[ $datekey ]['total_cost'] += $single_payment['total_cost'];
				$days_totals[ $datekey ]['tasks'] = $days_totals[ $datekey ]['tasks'] + 1;

			} else {

				$days_totals[ $datekey ]          = $single_payment;
				$days_totals[ $datekey ]['tasks'] = 1;

			}
		}

		return $days_totals;

	}

	public function get_dates_average( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        AVG(fee_amount) as fee_amount,
	        AVG(credit_fee_amount) as contractor_fee,
	        AVG(credit_revenue_amount) as revenue,
	        AVG(debit_user_amount) as total_cost
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion'
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
	    ";

		// check cache
		$cache_key = 'dates_average' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );

		$single_result = array_shift( $result );

		return $single_result;

	}

	public function get_months_average( $from_month, $from_year, $to_month, $to_year ) {

		$get_month_range_totals = $this->get_month_range_totals( $from_month, $from_year, $to_month, $to_year );

		$fee_amount = $contractor_fee = $revenue = $total_cost = $tasks = array();

		foreach ( $get_month_range_totals as $month ) {

			$fee_amount[]     = $month['fee_amount'];
			$contractor_fee[] = $month['contractor_fee'];
			$revenue[]        = $month['revenue'];
			$total_cost[]     = $month['total_cost'];
			$tasks[]          = $month['tasks'];

		}

		$averages = array(
			'fee_amount'     => round( ( array_sum( $fee_amount ) / count( $fee_amount ) ), 2 ),
			'contractor_fee' => round( ( array_sum( $contractor_fee ) / count( $contractor_fee ) ), 2 ),
			'revenue'        => round( ( array_sum( $revenue ) / count( $revenue ) ), 2 ),
			'total_cost'     => round( ( array_sum( $total_cost ) / count( $total_cost ) ), 2 ),
		);

		return $averages;

	}


	public function get_month_totals( $month, $year ) {

		$to_day = date( 't', strtotime( $year . '-' . $month . '-01 23:59:59' ) );

		return $this->get_dates_totals( '01', $month, $year, $to_day, $month, $year );

	}

	public function get_month_range_totals( $from_month = '', $from_year = '', $to_month = '', $to_year = '' ) {

		$totals = array();

		$firstdate = '';
		$lastdate  = '';

		// get first and last task if no date is set
		if ( $from_month == '' && $from_year == '' ) {
			$get_first_task = $this->get_first_task();
			$firstdate      = $get_first_task['dateadded'];
		} else {
			$firstdate = $from_year . '-' . $from_month . '-01';
		}
		if ( $to_month == '' && $to_year == '' ) {
			$get_last_task = $this->get_last_task();
			$lastdate      = $get_last_task['dateadded'];
		} else {
			$lastdate = $to_year . '-' . $to_month . '-' . date( 't', strtotime( $to_year . '-' . $to_month . '-01 23:59:59' ) );
		}


		$begin = new DateTime( $firstdate );
		$end   = new DateTime( $lastdate );

		$interval = DateInterval::createFromDateString( '1 month' );
		$period   = new DatePeriod( $begin, $interval, $end );

		foreach ( $period as $dt ) {

			$totals[ $dt->format( 'Ym' ) ] = $this->get_month_totals( $dt->format( 'm' ), $dt->format( 'Y' ) );

		}

		return $totals;

	}


	public function get_first_task() {

		$query = " 
		      SELECT
		        *
		      FROM 
		        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
		      ON
		        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
		      WHERE 
		            `description` = 'task_completion'
		      ORDER BY " . $this->tables['transcactions'] . ".id ASC
		      LIMIT 0,1
		    ";

		// check cache
		$cache_key = 'first_task';
		$result = $this->check_cache( $cache_key, $query );


		return array_shift( $result );
	}

	public function get_last_task() {

		$query = " 
	      SELECT
	        *
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion'
	      ORDER BY " . $this->tables['transcactions'] . ".id DESC
	      LIMIT 0,1
	    ";

		// check cache
		$cache_key = 'last_task';
		$result = $this->check_cache( $cache_key, $query );

		return array_shift( $result );
	}

	public function get_year_totals( $year ) {

		return $this->get_dates_totals( '01', '01', $year, '31', '12', $year );

	}


	public function get_amounts_range( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        credit_revenue_amount
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion'
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
	    ";

		// check cache
		$cache_key = 'amounts_range_' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );

		$variance   = array(
      '0-100'       => 0,
      '100-300'     => 0,
      '300-500'     => 0,
      '500-1000'    => 0,
      '1000-3000'   => 0,
      '3000-5000'   => 0,
      '5000-10000'  => 0,
      '10000-20000' => 0
    );
		$milestones = array( 0, 100, 300, 500, 1000, 3000, 5000, 10000, 20000 );

		foreach ( $result as $amount ) {
			$revenue = $amount['credit_revenue_amount'];

			for ( $i = 0; $i < count( $milestones ); $i ++ ) {
				if ( $revenue > $milestones[ $i ] && isset( $milestones[ $i + 1 ] ) && $revenue <= $milestones[ $i + 1 ] ) {
					$variance[ $milestones[ $i ] . '-' . $milestones[ $i + 1 ] ] ++;
				}
			}

		}

		return $variance;

	}


	public function get_tasks_per_month( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        DATE_FORMAT(dateadded,'%Y-%m') as dateadded, count(1) as tasks_per_month
	      FROM 
	        " . $this->tables['transcactions'] . "
	      WHERE 
	            `description` = 'task_completion'
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
	      GROUP BY 
	        YEAR(dateadded), MONTH(dateadded) DESC
	      ORDER BY 
	        `dateadded` ASC
	    ";

		// check cache
		$cache_key = 'tasks_per_month_' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );

		return $result;

	}
  
  public function get_tasks_type( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        task_type, 
          COUNT(id) as count,
          SUM(debit_user_amount) as user_amount,
          SUM(credit_revenue_amount) as revenue,
          SUM(credit_fee_amount) as fee
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion' 
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
        GROUP BY task_type
	    ";

		// check cache
		$cache_key = 'tasks_type_' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );
    
    $out = array();
    
    foreach ($result as $res) {
        
        if (!$res['revenue']) { 
          continue; 
        }
        
        $out[$res['task_type']] = $res;

    }

		return $out;

	}
  
  public function get_preferred_count( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year ) {

		$first_date = date( 'Y-m-d H:i:s', strtotime( $from_year . '-' . $from_month . '-' . $from_day ) );
		$last_date  = date( 'Y-m-d H:i:s', strtotime( $to_year . '-' . $to_month . '-' . $to_day . ' 23:59:59' ) );

		$query = " 
	      SELECT
	        preferred, 
          COUNT(id) as count,
          SUM(debit_user_amount) as user_amount,
          SUM(credit_revenue_amount) as revenue,
          SUM(credit_fee_amount) as fee
	      FROM 
	        " . $this->tables['transcactions'] . " LEFT JOIN " . $this->tables['amounts'] . "
	      ON
	        " . $this->tables['transcactions'] . ".task_id = " . $this->tables['amounts'] . ".task_id
	      WHERE 
	            `description` = 'task_completion' 
          AND (preferred = 1 OR preferred = 0)  
	        AND (dateadded BETWEEN '" . $first_date . "' AND '" . $last_date . "')
        GROUP BY preferred
	    ";

		// check cache
		$cache_key = 'preferred_count_' . $first_date . '_' . $last_date;
		$result = $this->check_cache( $cache_key, $query );
    
    $out = array(
      'preferred'     => 0,
      'nonpreferred'  => 0
    );
    
    foreach ($result as $res) {
      if ($res['preferred'] == 1) {
        $out['preferred'] = $res;
      }
      if ($res['preferred'] == 0) {
        $out['nonpreferred'] = $res;
      }
    }

		return $out;

	}
  
  
  // returns an array with all stats
  public function get_all_stats($from_day, $from_month, $from_year, $to_day, $to_month, $to_year, $chart_display_method = 'months') {
    
    $stats = array();
    
    $averages             = $this->get_months_average( $from_month, $from_year, $to_month, $to_year );
    $preferred_count      = $this->get_preferred_count( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );
    $get_amounts_range    = $this->get_amounts_range( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );
    
    $chart_amounts_range  = array();
    $get_available_ranges = array();
    foreach ( $get_amounts_range as $range => $num_of_tasks ) {
      $chart_amounts_range[] = '["' . $range . '", ' . $num_of_tasks . ']';
      $get_available_ranges[] = '"'.$range.'"';
    }
    
    $get_tasks_type =   $this->get_tasks_type( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );
  
    $type_categories     = array();
    $type_contractor_fee = array();
    $type_revenue        = array();
    $type_tasks_count    = array();
    
    foreach ($get_tasks_type as $type => $type_data) {

      $type_categories[ $type ]     = "'" . $type . "'";
      $type_contractor_fee[ $type ] = floatval( $type_data['fee'] );
      $type_revenue[ $type ]        = floatval( $type_data['revenue'] );
      $type_tasks_count[ $type ]    = intval( $type_data['count'] );
    }
    
    $type_tasks_count_json  = json_encode( $type_tasks_count );
    
    if ( $chart_display_method == 'months' ) {

      $month_totals = $this->get_month_range_totals( $from_month, $from_year, $to_month, $to_year );

      $max_month_totals     = max( $month_totals );
      $max_month_totals_key = array_keys( $month_totals, max( $month_totals ) );

      $all_month_totals            = array();
      $all_month_totals['revenue'] = $all_month_totals['total_cost'] = '';
      foreach ( $month_totals as $mt ) {
        $all_month_totals['revenue']    = floatval( $all_month_totals['revenue'] ) + floatval( $mt['revenue'] );
        $all_month_totals['total_cost'] = floatval( $all_month_totals['total_cost'] ) + floatval( $mt['total_cost'] );
      }

      $chart_categories       = array();
      $chart_dates            = array();
      $chart_contractor_fee   = array();
      $chart_revenue          = array();
      $chart_revenue_avg      = array();
      $chart_total_cost       = array();
      $chart_tasks_count      = array();
      $chart_tasks_count_avg  = array();

      foreach ( $month_totals as $yearmonth => $amounts ) {

        $chart_categories[ $yearmonth ]     = "'" . wordwrap( $yearmonth, 4, '-', true ) . "'";
        $chart_dates[]                      = wordwrap( $yearmonth, 4, '-', true );
        $chart_contractor_fee[ $yearmonth ] = floatval( $amounts['fee_amount'] );
        $chart_revenue[ $yearmonth ]        = floatval( $amounts['revenue'] );
        $chart_total_cost[ $yearmonth ]     = floatval( $amounts['total_cost'] );
        $chart_tasks_count[ $yearmonth ]    = intval( $amounts['tasks'] );

      }

      $chart_tasks_count_json = json_encode( $chart_tasks_count );
      $chart_revenue_json     = json_encode( $chart_revenue );

    } else {

      $days_totals = $this->get_days( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );


      $max_month_totals        = max( $days_totals );
      $max_month_totals_key    = array_keys( $days_totals, max( $days_totals ) );
      $max_month_totals_key[0] = wordwrap( $max_month_totals_key[0], 6, '-', true );

      $all_month_totals = array();
      foreach ( $days_totals as $mt ) {
        if (!isset($all_month_totals['revenue'])) { $all_month_totals['revenue'] = 0; }
        if (!isset($all_month_totals['total_cost'])) { $all_month_totals['total_cost'] = 0; }
        
        $all_month_totals['revenue']    = $all_month_totals['revenue'] + $mt['revenue'];
        $all_month_totals['total_cost'] = $all_month_totals['total_cost'] + $mt['total_cost'];
      }

      $chart_categories       = array();
      $chart_dates            = array();
      $chart_contractor_fee   = array();
      $chart_revenue          = array();
      $chart_revenue_avg      = array();
      $chart_total_cost       = array();
      $chart_tasks_count      = array();
      $chart_tasks_count_avg  = array();

      foreach ( $days_totals as $yearmonthday => $amounts ) {

        $date_array = array();
        $date_array = date_parse_from_format( 'Ymd', $yearmonthday );

        $chart_categories[ $yearmonthday ]     = "'" . $date_array['year'] . '-' . sprintf( "%02d", $date_array['month'] ) . '-' . sprintf( "%02d", $date_array['day'] ) . "'";
        $chart_dates[]                         = $date_array['year'] . '-' . sprintf( "%02d", $date_array['month'] ) . '-' . sprintf( "%02d", $date_array['day'] );
        $chart_contractor_fee[ $yearmonthday ] = floatval( $amounts['fee_amount'] );
        $chart_revenue[ $yearmonthday ]        = floatval( $amounts['revenue'] );
        $chart_total_cost[ $yearmonthday ]     = floatval( $amounts['total_cost'] );
        $chart_tasks_count[ $yearmonthday ]    = intval( $amounts['tasks'] );
      }


      $chart_tasks_count_json = json_encode( $chart_tasks_count );
      $chart_revenue_json     = json_encode( $chart_revenue );

    }
    
    $chart_dates_json   = json_encode($chart_dates);
    
    $fromDT   = new DateTime($from_year.'-'.$from_month.'-'.$from_day);
    $toDT     = new DateTime($to_year.'-'.$to_month.'-'.$to_day);

    $datediff = date_diff($fromDT, $toDT);
    
    if ($chart_display_method == 'months') {
      $datediffcount = $datediff->format('%m') + ($datediff->format('%y') * 12) + 1;
    }
    if ($chart_display_method == 'days') {
      $datediffcount = $datediff->format('%a');
    }
    
    $chart_revenue_avg      = array_fill(0, count($chart_revenue), round(array_sum($chart_revenue) / $datediffcount, 2));
    $chart_tasks_count_avg  = array_fill(0, count($chart_tasks_count), round(array_sum($chart_tasks_count) / $datediffcount, 2));
    
    $stats['averages']                = $averages;
    $stats['preferred_count']         = $preferred_count;
    $stats['chart_amounts_range']     = $chart_amounts_range;
    $stats['get_available_ranges']    = $get_available_ranges;
    $stats['type_categories']         = $type_categories;
    $stats['type_contractor_fee']     = $type_contractor_fee;
    $stats['type_revenue']            = $type_revenue;
    $stats['type_tasks_count']        = $type_tasks_count;
    $stats['type_tasks_count_json']   = $type_tasks_count_json;
    $stats['max_month_totals']        = $max_month_totals;
    $stats['max_month_totals_key']    = $max_month_totals_key;
    $stats['all_month_totals']        = $all_month_totals;
    $stats['chart_categories']        = $chart_categories;
    $stats['chart_dates']             = $chart_dates;
    $stats['chart_dates_json']        = $chart_dates_json;
    $stats['chart_contractor_fee']    = $chart_contractor_fee;
    $stats['chart_revenue']           = $chart_revenue;
    $stats['chart_revenue_avg']       = $chart_revenue_avg;
    $stats['chart_total_cost']        = $chart_total_cost;
    $stats['chart_tasks_count']       = $chart_tasks_count;
    $stats['chart_tasks_count_avg']   = $chart_tasks_count_avg;
    $stats['chart_tasks_count_json']  = $chart_tasks_count_json;
    $stats['chart_revenue_json']      = $chart_revenue_json;
    
    return $stats;
  }

	/**
	 * Checks and sets cached data
	 *
	 * @since   0.0.6
	 * @author Justin Frydman
	 *
	 * @param bool $key     The unique cache key
	 * @param bool $query   The query to check
	 *
	 * @return mixed    The raw or cached data
	 * @throws Exception
	 */
	private function check_cache( $key = false, $query = false ) {

		$cache = new wpcable_cache( $key, $query );
		return $cache->check();

	}

}