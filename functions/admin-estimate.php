<?php
/**
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function codeable_estimate_callback() {
	$rate           = (float) get_option( 'wpcable_rate', 80 );
	$fee_type       = get_option( 'wpcable_fee_type', 'client' );
	$fee_contractor = 10;
	$fee_client     = 17.5;

	if ( isset( $_GET['fee_client'] ) && is_numeric( $_GET['fee_client'] ) ) {
		$fee_client = (float) $_GET['fee_client'];
	}
	if ( isset( $_GET['fee_contractor'] ) && is_numeric( $_GET['fee_contractor'] ) ) {
		if ( 'full' === $fee_type ) {
			$fee_contractor = (float) $_GET['fee_contractor'];
		}
	}
	if ( isset( $_GET['rate'] ) && is_numeric( $_GET['rate'] ) ) {
		$rate = (float) $_GET['rate'];
	}
	$admin_estimate_template = apply_filters('wpcable_admin_estimate_template', WPCABLE_TEMPLATE_DIR.'/admin-estimate.php') ;
	ob_start();
	require_once $admin_estimate_template;
	echo ob_get_clean();
}
