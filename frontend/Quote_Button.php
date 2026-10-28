<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Frontend;

use DOIT_Get_Quote\Engine\Base;

/**
 * Prints the "Get a Quote" button where the settings allow it:
 *  - product lists (shop, categories, shortcodes), not inside a single product page,
 *  - the single product page,
 *  - the cart page.
 *
 * Each location is a WooCommerce setting saved as 'yes' or 'no'.
 */
class Quote_Button extends Base {

	/**
	 * Was a button printed in this request? The popup is printed only then.
	 *
	 * @var bool
	 */
	private static $needed = false;

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_list_button' ), 11 );
		\add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_details_button' ) );
		\add_action( 'woocommerce_after_cart_totals', array( $this, 'render_cart_button' ) );
	}

	/**
	 * Has a button been printed in this request?
	 *
	 * @return bool
	 */
	public static function is_needed(): bool {
		return self::$needed;
	}

	/**
	 * Button under each product of a list.
	 *
	 * @return void
	 */
	public function render_list_button() {
		if ( !$this->is_enabled( 'doitgq_location_product_list' ) || \is_product() ) {
			return;
		}

		$product = $this->current_product();

		if ( $product ) {
			$this->button( 'product', $product->get_id() );
		}
	}

	/**
	 * Button next to "Add to cart" on the product page.
	 *
	 * @return void
	 */
	public function render_details_button() {
		if ( !$this->is_enabled( 'doitgq_location_product_details' ) ) {
			return;
		}

		$product = $this->current_product();

		if ( $product ) {
			$this->button( 'product', $product->get_id() );
		}
	}

	/**
	 * Button under the cart totals.
	 *
	 * @return void
	 */
	public function render_cart_button() {
		if ( !$this->is_enabled( 'doitgq_location_cart' ) || !\function_exists( 'WC' ) || null === \WC()->cart || \WC()->cart->is_empty() ) {
			return;
		}

		$this->button( 'cart', 0 );
	}

	/**
	 * Print the button.
	 *
	 * @param string $type       'product' or 'cart'.
	 * @param int    $product_id Product ID (0 for the cart).
	 * @return void
	 */
	private function button( string $type, int $product_id ) {
		self::$needed = true;

		Public_Assets::enqueue();

		\printf(
			'<button type="button" class="doitgq-get-quote-button button doitgq-%1$s" data-type="%1$s" data-product-id="%2$d">%3$s</button>',
			\esc_attr( $type ),
			(int) $product_id,
			\esc_html__( 'Get a Quote', 'doit_get_quote' )
		);
	}

	/**
	 * A location is on only when its saved value is exactly 'yes'.
	 *
	 * A saved 'no' is a non-empty string, so a plain truthiness test would
	 * show the button even when the box is unchecked.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	private function is_enabled( string $option ): bool {
		return 'yes' === \get_option( $option, 'no' );
	}

	/**
	 * The product of the current loop or page, or null.
	 *
	 * @return \WC_Product|null
	 */
	private function current_product() {
		global $product;

		return $product instanceof \WC_Product ? $product : null;
	}
}