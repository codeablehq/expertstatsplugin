<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wpcable_register_menu_page() {
	add_menu_page(
		__( 'Codeable Stats', 'wpcable' ),
		__( 'Codeable Stats', 'wpcable' ),
		'manage_options',
		'codeable_transcactions_stats',
		'codeable_transcactions_stats_cb',
		plugins_url( 'assets/images/codeable_16x16.png', dirname( __FILE__ ) ),
		85
	);
}

add_action( 'admin_menu', 'wpcable_register_menu_page' );

function codeable_transcactions_stats_cb() {

	add_thickbox();

	$account_details   = get_option( 'wpcable_account_details' );
	$average_task_size = get_option( 'wpcable_average' );
	$balance           = get_option( 'wpcable_balance' );
	$revenue           = get_option( 'wpcable_revenue' );

	$wpcable_stats = new wpcable_stats;

	$get_first_task = $wpcable_stats->get_first_task();
	$get_last_task  = $wpcable_stats->get_last_task();

	$first_day   = date( 'd', strtotime( $get_first_task['dateadded'] ) );
	$first_month = date( 'm', strtotime( $get_first_task['dateadded'] ) );
	$first_year  = date( 'Y', strtotime( $get_first_task['dateadded'] ) );
	$last_day    = date( 'd', strtotime( $get_last_task['dateadded'] ) );
	$last_month  = date( 'm', strtotime( $get_last_task['dateadded'] ) );
	$last_year   = date( 'Y', strtotime( $get_last_task['dateadded'] ) );

	if ( ! isset( $_GET['date_from'] ) ) {
		$_GET['date_from'] = $first_year . '-' . $first_month;
		$from_day          = $first_day;
		$from_month        = $first_month;
		$from_year         = $first_year;
	} else {
		$from_day   = '01';
		$from_month = date( 'm', strtotime( $_GET['date_from'] . '-01' ) );
		$from_year  = date( 'Y', strtotime( $_GET['date_from'] . '-01' ) );
	}

	if ( ! isset( $_GET['date_to'] ) ) {
		$_GET['date_to'] = $last_year . '-' . $last_month;
		$to_day          = $last_day;
		$to_month        = $last_month;
		$to_year         = $last_year;
	} else {
		$to_day   = date( 't', strtotime( $_GET['date_to'] . '-01' ) );
		$to_month = date( 'm', strtotime( $_GET['date_to'] . '-01' ) );
		$to_year  = date( 'Y', strtotime( $_GET['date_to'] . '-01' ) );
	}

	if ( ! isset( $_GET['chart_display_method'] ) ) {
		$_GET['chart_display_method'] = 'months';
		$chart_display_method         = 'months';
	} else {
		$chart_display_method = $_GET['chart_display_method'];
	}

	$averages        = $wpcable_stats->get_months_average( $from_month, $from_year, $to_month, $to_year );
	$all_averages    = $wpcable_stats->get_dates_average( $first_day, $first_month, $first_year, $last_day, $last_month, $last_year );
	$preferred_count = $wpcable_stats->get_preferred_count( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );
	$wpcable_clients = new wpcable_clients;
	$clients_data    = $wpcable_clients->get_clients();

	$get_amounts_range   = $wpcable_stats->get_amounts_range( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );
	$chart_amounts_range = array();
	foreach ( $get_amounts_range as $range => $num_of_tasks ) {
		$chart_amounts_range[] = '["' . $range . '", ' . $num_of_tasks . ']';
	}

	$get_tasks_type =   $wpcable_stats->get_tasks_type( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );
  
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

		$month_totals = $wpcable_stats->get_month_range_totals( $from_month, $from_year, $to_month, $to_year );

		$max_month_totals     = max( $month_totals );
		$max_month_totals_key = array_keys( $month_totals, max( $month_totals ) );

		$all_month_totals            = array();
		$all_month_totals['revenue'] = $all_month_totals['total_cost'] = '';
		foreach ( $month_totals as $mt ) {
			$all_month_totals['revenue']    = $all_month_totals['revenue'] + $mt['revenue'];
			$all_month_totals['total_cost'] = $all_month_totals['total_cost'] + $mt['total_cost'];
		}

		$chart_categories       = array();
		$chart_contractor_fee   = array();
		$chart_revenue          = array();
		$chart_revenue_avg      = array();
		$chart_total_cost       = array();
		$chart_tasks_count      = array();
		$chart_tasks_count_avg  = array();

		foreach ( $month_totals as $yearmonth => $amounts ) {

			$chart_categories[ $yearmonth ]     = "'" . wordwrap( $yearmonth, 4, '-', true ) . "'";
			$chart_contractor_fee[ $yearmonth ] = floatval( $amounts['fee_amount'] );
			$chart_revenue[ $yearmonth ]        = floatval( $amounts['revenue'] );
			$chart_total_cost[ $yearmonth ]     = floatval( $amounts['total_cost'] );
			$chart_tasks_count[ $yearmonth ]    = intval( $amounts['tasks'] );

		}

		$chart_tasks_count_json = json_encode( $chart_tasks_count );
		$chart_revenue_json     = json_encode( $chart_revenue );

	} else {

		$days_totals = $wpcable_stats->get_days( $from_day, $from_month, $from_year, $to_day, $to_month, $to_year );


		$max_month_totals        = max( $days_totals );
		$max_month_totals_key    = array_keys( $days_totals, max( $days_totals ) );
		$max_month_totals_key[0] = wordwrap( $max_month_totals_key[0], 6, '-', true );

		$all_month_totals = array();
		foreach ( $days_totals as $mt ) {
			$all_month_totals['revenue']    = $all_month_totals['revenue'] + $mt['revenue'];
			$all_month_totals['total_cost'] = $all_month_totals['total_cost'] + $mt['total_cost'];
		}

		$chart_categories       = array();
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
			$chart_contractor_fee[ $yearmonthday ] = floatval( $amounts['fee_amount'] );
			$chart_revenue[ $yearmonthday ]        = floatval( $amounts['revenue'] );
			$chart_total_cost[ $yearmonthday ]     = floatval( $amounts['total_cost'] );
			$chart_tasks_count[ $yearmonthday ]    = intval( $amounts['tasks'] );
		}


		$chart_tasks_count_json = json_encode( $chart_tasks_count );
		$chart_revenue_json     = json_encode( $chart_revenue );

	}
  
  $fromDT = new DateTime($from_year.'-'.$from_month.'-'.$from_day);
  $toDT = new DateTime($to_year.'-'.$to_month.'-'.$to_day);

  $datediff = date_diff($fromDT, $toDT);
  
  if ($chart_display_method == 'months') {
    $datediffcount = $datediff->format('%m') + ($datediff->format('%y') * 12) + 1;
  }
  if ($chart_display_method == 'days') {
    $datediffcount = $datediff->format('%a');
  }
  
  $chart_revenue_avg      = array_fill(0, count($chart_revenue), round(array_sum($chart_revenue) / $datediffcount, 2));
  $chart_tasks_count_avg = array_fill(0, count($chart_tasks_count), round(array_sum($chart_tasks_count) / $datediffcount, 2));

	?>

    <div class="wrap cable_stats_wrap">
        <h1><?php echo __( 'Codeable Stats', 'wpcable' ); ?></h1>

        <div class="clearfix spacer"></div>

        <div class="flex-row first-stats-row">

            <div class="box user-info">
                <div class="avatar">
                    <img class="round" src="<?php echo $account_details['avatar']['large_url']; ?>"/>
                    <div class="rating" data-score="<?php echo $account_details['stats']['avg_rating']; ?>"></div>
                </div>
                <div class="details">
                    <div class="codeable-logo">
						<?php echo '<img src="' . esc_url( plugins_url( 'assets/images/codeable-full.png', dirname( __FILE__ ) ) ) . '" > '; ?>
                    </div>
                    <span class="name"><?php echo $account_details['first_name'] . ' ' . $account_details['last_name']; ?></span>
                    <span class="label role"><?php echo $account_details['role']; ?></span>
                    <span class="user-id"><?php echo __( 'ID', 'wpcable' ) . ': <a href="https://app.codeable.io/tasks/new?preferredContractor=' . $account_details['id'] . '" target="_blank" title="' . __( 'Direct hire link', 'wpcable' ) . '">' . $account_details['id']; ?></a></span>
                </div>
            </div>

            <div class="box completed-tasks">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Completed Tasks', 'wpcable' ); ?></div>
                        <div class="value"><?php echo $account_details['completed_tasks_count']; ?></div>
                    </div>
                    <div class="footerdata">
                        <span class="label"><?php echo __( 'Tasks', 'wpcable' ); ?></span>: <span
                                class="value"><?php echo $account_details['tasks_count']; ?></span><br/>
                        <span class="label"><?php echo __( 'Refunded', 'wpcable' ); ?></span>: <span
                                class="value"><?php echo $clients_data['totals']['refunds']; ?></span>
                    </div>
                </div>
            </div>

            <div class="box balance">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Balance', 'wpcable' ); ?></div>
                        <div class="value">$<?php echo wpcable_money( $balance ); ?></div>
                    </div>
                </div>
            </div>

            <div class="box total-revenue">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Total Revenue', 'wpcable' ); ?></div>
                        <div class="value">$<?php echo wpcable_money( $revenue ); ?></div>
                    </div>
                    <div class="footerdata">
                        <span class="label"><?php echo __( 'Since', 'wpcable' ); ?></span>: <span
                                class="value"><?php echo $get_first_task['dateadded']; ?></span>
                    </div>
                </div>
            </div>

        </div>
        <!-- end row  -->

        <!--  Row  -->
        <div class="flex-row second-stats-row">

            <div class="box conversions">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Conversions', 'wpcable' ); ?></div>
                    </div>
                    <table class="stats_table">
                        <tbody>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( 'Month conversion', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value"><?php echo $account_details['stats']['estimated_completed_conversion_1_month'] * 100; ?>
                                    %</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( '6 Month conversion', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value"><?php echo $account_details['stats']['estimated_completed_conversion_6_months'] * 100; ?>
                                    %</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( 'Average task', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value">$<?php echo wpcable_money( $average_task_size ) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( 'Average including subtasks', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value">$<?php echo wpcable_money( $all_averages['revenue'] ) ?></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="box monthly-averages">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Monthly Averages', 'wpcable' ); ?></div>
                    </div>
                    <table class="stats_table">
                        <tbody>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( 'Revenue', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value">$<?php echo wpcable_money( $averages['revenue'] ); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( 'Total', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value">$<?php echo wpcable_money( $averages['total_cost'] ); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="label"><?php echo __( 'Fees', 'wpcable' ); ?></span>
                            </td>
                            <td>
                                <span class="value">$<?php echo wpcable_money( $averages['contractor_fee'] ); ?></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <small><?php echo __( 'The above are from', 'wpcable' ) . ' ' . $from_month . '-' . $from_year . ' ' . __( 'to', 'wpcable' ) . ' ' . $to_month . '-' . $to_year; ?></small>
                </div>
            </div>

        </div>


        <div class="flex-row box date-filters">
            <form method="get" id="date_form" data-start-year="<?php echo $first_year; ?>"
                  data-end-year="<?php echo $last_year; ?>">
                <input type="hidden" name="page" value="codeable_transcactions_stats"/>

                <div class="section">
                    <label class="label" for="date_from"><?php echo __( 'Start date', 'wpcable' ); ?></label>
                    <input class="datepicker" type="text" id="date_from" name="date_from"
                           value="<?php echo $_GET['date_from']; ?>"
                           data-icon="<?php echo WPCABLE_URI . '/assets/images/icon_datepicker_blue.png'; ?>">
                </div>


                <div class="section">
                    <label class="label" for="date_to"><?php echo __( 'End date', 'wpcable' ); ?></label>
                    <input class="datepicker" type="text" id="date_to" name="date_to"
                           value="<?php echo $_GET['date_to']; ?>"
                           data-icon="<?php echo WPCABLE_URI . '/assets/images/icon_datepicker_blue.png'; ?>">
                </div>


                <div class="section">
                    <label class="label"
                           for="chart_display_method"><?php echo __( 'Chart display', 'wpcable' ); ?></label>:&nbsp;&nbsp;
					<?php echo __( 'Months', 'wpcable' ); ?>&nbsp;<input type="radio" name="chart_display_method"
                                                                         value="months" <?php echo( $chart_display_method == 'months' ? 'checked="checked"' : '' ); ?> />
					<?php echo __( 'Days', 'wpcable' ); ?>&nbsp;<input type="radio" name="chart_display_method"
                                                                       value="days" <?php echo( $chart_display_method == 'days' ? 'checked="checked"' : '' ); ?> />
                </div>


                <button class="set-date button button-primary"><?php echo __( 'Set date', 'wpcable' ); ?></button>

            </form>
        </div>


        <div class="clearfix spacer"></div>

        <div class="row bests">
            <div class="col-md-12">
                <h2 class="text-center"><?php echo __( 'Your Highscore for This Range' ); ?></h2>
            </div>

            <div class="col-md-4 text-center">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo $chart_display_method == 'days' ? __( 'Best Day', 'wpcable' ) : __( 'Best Month' ); ?></div>
                        <span class="value"><?php echo wordwrap( $max_month_totals_key[0], 4, '-', true ); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Revenue Best', 'wpcable' ); ?></div>
                        <span class="value">$<?php echo wpcable_money( $max_month_totals['revenue'] ); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="column_inner">
                    <div class="maindata">
                        <div class="label"><?php echo __( 'Revenue', 'wpcable' ); ?></div>
                        <span class="value">$<?php echo wpcable_money( $all_month_totals['revenue'] ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix spacer"></div>

        <div class="row">
            <div class="col-md-12">
                <div class="whitebox">
                    <div id="chart_wrap">


                    </div>
                </div>
            </div>
        </div>

        <div class="clearfix spacer"></div>

        <div class="row">
            <div class="col-md-4">
                <div class="whitebox">
                    <div id="amounts_range_chart">


                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="whitebox">
                    <div id="tasks_per_month_chart">


                    </div>
                </div>
            </div>

        </div>
        
        <div class="clearfix spacer"></div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="whitebox">
                    <div id="preferred_chart">


                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="whitebox">
                    <div id="tasks_type">


                    </div>
                </div>
            </div>


        </div>


        <div class="clearfix spacer"></div>

        <div class="row clients_row">
            <div class="col-md-12">

                <h3><?php echo __( 'Clients Data', 'wpcable' ); ?>
                    <small><em>(<?php echo __( 'all time, order by revenue', 'wpcable' ); ?>)</em></small>
                </h3>

                <div class="whitebox">
                    <div id="clients_wrap">

                        <table class="datatable widefat fixed striped posts" id="clients_table">
                            <thead>
                            <th class="avatar">Avatar</th>
                            <th>Name</th>
                            <th>Last Login</th>
                            <th>Total Tasks</th>
                            <th>Tasks</th>
                            <th>Subtasks</th>
                            <th>Average per task</th>
                            <th>Revenue</th>
                            </thead>
                            <tbody>
							<?php

                            if( is_array($clients_data['clients']) ) {

	                            foreach ( $clients_data['clients'] as $client_id => $client ) {

		                            if ( $client['client_id'] == '' ) {
			                            continue;
		                            }

		                            ?>
                                    <tr id="client_<?php echo $client['client_id']; ?>">
                                        <td class="avatar"><img class="round" src="<?php echo $client['avatar']; ?>"/>
                                        </td>
                                        <td>
                                            <a href="#TB_inline?width=1000&height=550&inlineId=client_info_<?php echo $client_id; ?>"
                                               class="thickbox"><?php echo $client['full_name']; ?></a>
                                            <div style="display:none;" id="client_info_<?php echo $client_id; ?>">

                                                <h2><?php echo $client['full_name']; ?></h2>

                                                <div class="clearfix spacer"></div>

                                                <table class="datatable datatable_inner widefat fixed striped posts">
                                                    <thead>
                                                    <th>Task</th>
                                                    <th>Paid</th>
                                                    <th>Revenue</th>
                                                    <th>Fee</th>
                                                    <th>Refund</th>
                                                    <th>Pref.</th>
                                                    <th>Type</th>
                                                    <th>Pro</th>
                                                    </thead>
                                                    <tbody>
						                            <?php

						                            foreach ( $client['transactions'] as $tron ) {
							                            ?>
                                                        <tr class="<?php echo( $tron['is_refund'] == 1 ? 'refund' : 'complete' ); ?>">
                                                            <td>
                                                                <a href="https://app.codeable.io/tasks/<?php echo( $tron['parent_task_id'] > 0 ? $tron['parent_task_id'] : $tron['task_id'] ); ?>/workroom"
                                                                   target="_blank">
										                            <?php echo ( $tron['task_title'] ? $tron['task_title'] : 'Subtask of: ' . $tron['parent_task_id'] ) . ' - ' . $tron['task_id']; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo $tron['dateadded']; ?></td>
                                                            <td><?php echo wpcable_money( $tron['revenue'] ); ?></td>
                                                            <td><?php echo wpcable_money( $tron['fee_amount'] ); ?></td>
                                                            <td><?php echo( $tron['is_refund'] == 0 ? 'N' : 'Y' ); ?></td>
                                                            <td><?php echo( $tron['preferred'] == 0 ? 'N' : 'Y' ); ?></td>
                                                            <td><?php echo $tron['task_type']; ?></td>
                                                            <td><?php echo( $tron['pro'] == 0 ? 'N' : 'Y' ); ?></td>
                                                        </tr>
							                            <?php
						                            }

						                            ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                        <td><?php echo $client['last_sign_in_at']; ?></td>
                                        <td>
                                            <a href="#TB_inline?width=1000&height=550&inlineId=client_info_<?php echo $client_id; ?>"
                                               class="thickbox"><?php echo intval( $client['total_tasks'] ); ?></a></td>
                                        <td><?php echo intval( $client['tasks'] ); ?></td>
                                        <td><?php echo intval( $client['subtasks'] ); ?></td>
                                        <td>
                                            $<?php echo wpcable_money( $client['revenue'] / intval( $client['total_tasks'] ) ); ?></td>
                                        <td>$<?php echo wpcable_money( $client['revenue'] ); ?></td>
                                    </tr>
		                            <?php
	                            }
                            }

							?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <script>

        jQuery(function () {

            var $chart_tasks_count_json = <?php echo $chart_tasks_count_json; ?>;
            var $chart_revenue_json = <?php echo $chart_revenue_json; ?>;
            var $type_tasks_count_json = <?php echo $type_tasks_count_json; ?>;

            Highcharts.chart('chart_wrap', {
                exporting: {
                    chartOptions: { // specific options for the exported image
                        plotOptions: {
                            series: {
                                dataLabels: {
                                    enabled: true
                                }
                            }
                        }
                    },
                    fallbackToExportServer: false
                },
                chart: {
                    type: 'areaspline'
                },
                title: {
                    text: "<?php echo $chart_display_method == 'days' ? __( 'Daily money chart', 'wpcable' ) : __( 'Monthly Money Chart', 'wpcable' ); ?>"
                },
                subtitle: {
                    text: "<?php echo __( 'You Earned It!', 'wpcable' ); ?>"
                },
                xAxis: {
                    categories: [<?php echo implode( ', ', $chart_categories ); ?>]
                },
                yAxis: {
                    title: {
                        text: '<?php echo __( 'Money ($)', 'wpcable' ); ?>'
                    }
                },
                plotOptions: {
                    line: {
                        dataLabels: {
                            enabled: true
                        },
                        enableMouseTracking: false
                    }
                },
                tooltip: {
                    formatter: function () {
                        return '<b>' + this.x + '</b><br /></br >' + this.series.name + ': $<b>' + this.y + '</b><br /><?php echo __( 'Tasks', 'wpcable' ); ?>: ' + $chart_tasks_count_json[replaceAll(this.x, '-', '')];
                    }
                },
                series: [{
                    name: '<?php echo __( 'Total Cost', 'wpcable' ); ?>',
                    data: [<?php echo implode( ', ', $chart_total_cost ); ?>],
                    visible: true
                }, {
                    name: '<?php echo __( 'Revenue', 'wpcable' ); ?>',
                    data: [<?php echo implode( ', ', $chart_revenue ); ?>]
                }, {
                    name: '<?php echo __( 'Fees', 'wpcable' ); ?>',
                    data: [<?php echo implode( ', ', $chart_contractor_fee ); ?>],
                    visible: true
                }, {
                    name: '<?php echo __( 'Average', 'wpcable' ); ?>',
                    type: 'spline',
                    data: [<?php echo implode( ', ', $chart_revenue_avg ); ?>],
                    visible: true,
                    marker: {
                        enabled: false
                    },
                    dashStyle: 'shortdot'
                }]
            });


            Highcharts.chart('amounts_range_chart', {
                exporting: {
                    chartOptions: { // specific options for the exported image
                        plotOptions: {
                            series: {
                                dataLabels: {
                                    enabled: true
                                }
                            }
                        }
                    },
                    fallbackToExportServer: false
                },
                chart: {
                    type: 'pie',
                    options3d: {
                        enabled: true,
                        alpha: 45
                    }
                },
                title: {
                    text: '<?php echo __( 'Amounts Range', 'wpcable' ); ?>'
                },
                subtitle: {
                    text: '<?php echo __( 'Your tasks budget groups', 'wpcable' ); ?>'
                },
                plotOptions: {
                    pie: {
                        innerSize: 75,
                        depth: 45,
                        dataLabels: {
                            formatter: function () {
                                return this.point.name + ': ' + this.y;
                            }
                        }
                    }
                },
                series: [{
                    name: '<?php echo __( 'Tasks', 'wpcable' ); ?>',
                    data: [
						<?php echo implode( ',', $chart_amounts_range ); ?>
                    ]
                }]
            });


            Highcharts.chart('tasks_per_month_chart', {
                exporting: {
                    chartOptions: { // specific options for the exported image
                        plotOptions: {
                            series: {
                                dataLabels: {
                                    enabled: true
                                }
                            }
                        }
                    },
                    fallbackToExportServer: false
                },
                chart: {
                    type: 'column'
                },
                title: {
                    text: '<?php echo $chart_display_method == 'days' ? __( 'Tasks per Day', 'wpcable' ) : __( 'Tasks per Month', 'wpcable' ); ?>'
                },
                subtitle: {
                    text: '-'
                },
                xAxis: {
                    categories: [
						<?php echo implode( ',', $chart_categories ); ?>
                    ],
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: '<?php echo __( '# of tasks', 'wpcable' ); ?>'
                    }
                },
                tooltip: {
                    formatter: function () {
                        return this.x + '<br />-<br />' + this.series.name + ': <b>' + this.y + '</b><br />Revenue:<b>$' + parseFloat($chart_revenue_json[replaceAll(this.x, '-', '')]) + '</b>';
                    }
                },
                plotOptions: {
                    column: {
                        borderWidth: 0,
                        colorByPoint: true
                    }
                },
                series: [{
                    name: '<?php echo __( 'Tasks', 'wpcable' ); ?>',
                    data: [<?php echo implode( ',', $chart_tasks_count ); ?>]

                },
                {
                    name: '<?php echo __( 'Average', 'wpcable' ); ?>',
                    type: 'spline',
                    data: [<?php echo implode( ',', $chart_tasks_count_avg ); ?>],
                    visible: true,
                    marker: {
                        enabled: false
                    },
                    dashStyle: 'shortdot'

                }]
            });
            
            
            // preferred semi circle donut
            Highcharts.chart('preferred_chart', {
                exporting: {
                    chartOptions: {
                        plotOptions: {
                            series: {
                                dataLabels: {
                                    enabled: true
                                }
                            }
                        }
                    },
                    fallbackToExportServer: false
                },
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: 0,
                    plotShadow: false
                },
                title: {
                    text: 'Preferred vs non Preferred',
                    align: 'center'
                },
                subtitle: {
                  text: '<b>Revenue</b> | Preferred: $<?php echo wpcable_money($preferred_count['preferred']['revenue']); ?> | Non Preferred: $<?php echo wpcable_money($preferred_count['nonpreferred']['revenue']); ?>'
                },
                tooltip: {
                    pointFormat: '{series.name}: <b> {point.y} ( {point.percentage:.1f}% )</b>'
                },
                plotOptions: {
                    pie: {
                        dataLabels: {
                            enabled: true,
                            distance: -50,
                            style: {
                                fontWeight: 'bold',
                                color: 'white'
                            }
                        },
                        startAngle: -90,
                        endAngle: 90,
                        center: ['50%', '75%']
                    }
                },
                series: [{
                    type: 'pie',
                    name: 'Number of tasks',
                    innerSize: '50%',
                    data: [
                        ['Preferred',     <?php echo $preferred_count['preferred']['count']; ?>],
                        ['Non Preferred', <?php echo $preferred_count['nonpreferred']['count']; ?>],
                        {
                            name: 'Proprietary or Undetectable',
                            y: 0.2,
                            dataLabels: {
                                enabled: false
                            }
                        }
                    ]
                }]
            });
            
            
            // task type chart
            Highcharts.chart('tasks_type', {
                exporting: {
                    chartOptions: { // specific options for the exported image
                        plotOptions: {
                            series: {
                                dataLabels: {
                                    enabled: true
                                }
                            }
                        }
                    },
                    fallbackToExportServer: false
                },
                chart: {
                    type: 'column'
                },
                title: {
                    text: '<?php echo __( 'Tasks Types', 'wpcable' ); ?>'
                },
                subtitle: {
                    text: '-'
                },
                xAxis: {
                    categories: [
                      <?php echo implode( ',', $type_categories ); ?>
                    ]
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: '<?php echo __( 'Revenue', 'wpcable' ); ?>'
                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    formatter: function () {
                        return this.x + '<br />-<br />' + this.series.name + ': <b>$' + this.y + '</b><br />Tasks:<b>' + parseFloat($type_tasks_count_json[this.x]) + '</b><br />Average:<b>' + ( parseFloat(this.y / parseFloat($type_tasks_count_json[this.x])).toFixed(2) ) + '</b>';
                    }
                },
                plotOptions: {
                    column: {
                        borderWidth: 0,
                        colorByPoint: true
                    }
                },
                series: [{
                    name: ['<?php echo __( 'Revenue', 'wpcable' ); ?>'],
                    data: [<?php echo implode( ',', $type_revenue ); ?>]

                }]
            });
            

            function escapeRegExp(str) {
                return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
            }

            function replaceAll(str, find, replace) {
                return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
            }


        });
    </script>


	<?php
}
