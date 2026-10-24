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

namespace DOIT_Get_Quote\Ajax;

use DOIT_Get_Quote\Engine\Base;

/**
 * AJAX in the public
 */
class Ajax extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( !\apply_filters( 'doit_get_quote_d_ajax_initialize', true ) ) {
			return;
		}

		// For not logged user
		\add_action( 'wp_ajax_nopriv_your_method', array( $this, 'your_method' ) );
	}

	/**
	 * The method to run on ajax
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function your_method() {
		$return = array(
			'message' => 'Saved',
			'ID'      => 1,
		);

		\wp_send_json_success( $return );
		// wp_send_json_error( $return );
	}

}
