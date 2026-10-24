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

namespace DOIT_Get_Quote\Internals;

use DecodeLabs\Tagged as Html;
use DOIT_Get_Quote\Engine\Base;

/**
 * Shortcodes of this plugin
 */
class Shortcode extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		parent::initialize();

		\add_shortcode( 'foobar', array( $this, 'foobar_func' ) );
	}

	/**
	 * Shortcode example
	 *
	 * @param array $atts Parameters.
	 * @since 1.0.0
	 * @return string
	 */
	public static function foobar_func( array $atts ) {
		\shortcode_atts( array( 'foo' => 'something', 'bar' => 'something else' ), $atts );

		return Html::{'span.foo'}( 'foo = ' . $atts['foo'] ) . Html::{'span.bar'}( 'bar = ' . $atts['bar'] );
	}

}
