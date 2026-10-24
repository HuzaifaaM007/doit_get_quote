<?php
/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @author    Huzaifa Murtaza <huzaifamurtaza007@gmail.com>
 * @copyright 2026 DOIT Technologies
 * @license   GPL 2.0+
 * @link      
 */

$d_debug = new WPBP_Debug( __( 'DOIT_Get_Quote', D_TEXTDOMAIN ) );

/**
 * Log text inside the debugging plugins.
 *
 * @param string $text The text.
 * @return void
 */
function d_log( string $text ) {
	global $d_debug;
	$d_debug->log( $text );
}
