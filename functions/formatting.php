<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


function wpcable_money( $money ) {
	return number_format( $money, 2, '.', ',' );
}