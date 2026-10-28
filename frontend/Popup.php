<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Frontend;

use DOIT_Get_Quote\Engine\Base;
use DOIT_Get_Quote\Internals\Forms;
use DOIT_Get_Quote\Frontend\Quote_Button;

/**
 * Prints the quote popup in the footer, only on pages that have a button.
 *
 * The markup is in templates/popup.php. The script (assets/src/plugin-public.js)
 * opens it, fills the hidden fields and submits the form.
 */
class Popup extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * Print the popup.
	 *
	 * @return void
	 */
	public function render() {
		if ( !Quote_Button::is_needed() ) {
			return;
		}

		$form_id = Forms::selected_id();

		include \dirname( __DIR__ ) . '/templates/popup.php';
	}
}