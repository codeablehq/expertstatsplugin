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

		$variance   = array();
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