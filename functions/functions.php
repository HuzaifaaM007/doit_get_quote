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

/**
 * Get the settings of the plugin in a filterable way
 *
 * @since 1.0.0
 * @return array
 */
function d_get_settings() {
	return apply_filters( 'd_get_settings', get_option( D_TEXTDOMAIN . '-settings' ) );
}
