<?php
/**
 * Helper functions related to number/string formatting.
 *
 * @package wpcable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function wpcable_money( $money ) {
	return number_format( $money, 2, '.', ',' );
}

function wpcable_compare_values($from = 0, $to = 0) {
  	return $from >= $to ? 'increase' : 'decrease';
}
