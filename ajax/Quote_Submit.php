<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Ajax;

use DOIT_Get_Quote\Engine\Base;
use DOIT_Get_Quote\Internals\Forms;
use DOIT_Get_Quote\Internals\Mailer;
use DOIT_Get_Quote\Internals\Quote_Repository;

/**
 * Receives the quote form from the popup (admin-ajax.php?action=doitgq_send_quote),
 * saves it as a quote_request and emails the admin.
 *
 * The form is the one selected in the settings. The customer is found by
 * field type and id (see Backend\Form_Editor): an Email field is the email,
 * a Text field with "name", "phone" or "company" in its id fills those values,
 * and a Textarea with "message" in its id is the message.
 *
 * The request is saved first and the email is sent after, so a quote is not
 * lost when the mail server is down.
 */
class Quote_Submit extends Base {

	const ACTION       = 'doitgq_send_quote';
	const NONCE_ACTION = 'doitgq_quote_nonce';

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		\add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Handle the submission and answer with JSON.
	 *
	 * @return void
	 */
	public function handle() {
		if ( !\check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			\wp_send_json_error( \__( 'Security check failed. Please reload the page and try again.', 'doit_get_quote' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$data = \wp_unslash( $_POST );
		// phpcs:enable

		// Honeypot: a real visitor never fills this hidden field. Pretend it worked.
		if ( !empty( $data['doitgq_website'] ) ) {
			\wp_send_json_success( $this->success_message() );
		}

		$is_cart    = !empty( $data['is_cart'] );
		$is_product = !empty( $data['is_product'] );

		if ( $is_cart === $is_product ) {
			\wp_send_json_error( \__( 'Invalid request.', 'doit_get_quote' ), 400 );
		}

		$form = Forms::get( Forms::selected_id() );

		if ( null === $form ) {
			\wp_send_json_error( \__( 'The quote form is not available. Please contact the store.', 'doit_get_quote' ), 500 );
		}

		$customer = $this->read_form( $form, $data );

		if ( '' !== $customer['error'] ) {
			\wp_send_json_error( $customer['error'], 422 );
		}

		if ( '' === $customer['name'] || '' === $customer['email'] ) {
			\wp_send_json_error( \__( 'Please enter your name and email.', 'doit_get_quote' ), 422 );
		}

		if ( !\is_email( $customer['email'] ) ) {
			\wp_send_json_error( \__( 'Please enter a valid email address.', 'doit_get_quote' ), 422 );
		}

		$quantity = \max( 1, (int) ( $data['quantity'] ?? 1 ) );

		if ( $is_cart ) {
			$items = $this->cart_items();

			if ( empty( $items ) ) {
				\wp_send_json_error( \__( 'Your cart is empty.', 'doit_get_quote' ), 422 );
			}
		} else {
			$product = $this->product( \absint( $data['product_id'] ?? 0 ), $quantity );

			if ( null === $product ) {
				\wp_send_json_error( \__( 'Product not found.', 'doit_get_quote' ), 422 );
			}

			$items = array( $product );
		}

		$fields_text = $this->fields_text( $customer['fields'] );

		$post_id = Quote_Repository::create(
			array(
				'name'     => $customer['name'],
				'email'    => $customer['email'],
				'phone'    => $customer['phone'],
				'products' => $this->stored_products( $items ),
				'content'  => $this->post_content( $customer['email'], $items, $fields_text ),
			)
		);

		if ( !$post_id ) {
			\wp_send_json_error( \__( 'We could not save your request. Please try again.', 'doit_get_quote' ), 500 );
		}

		$sent = ( new Mailer() )->send_admin_notification(
			array(
				'name'        => $customer['name'],
				'email'       => $customer['email'],
				'phone'       => $customer['phone'],
				'company'     => $customer['company'],
				'message'     => $customer['message'],
				'fields_text' => $fields_text,
				'cart_items'  => $is_cart ? $items : array(),
				'product'     => $is_product ? $items[0] : array(),
			)
		);

		// The request is saved either way; note whether the admin was notified.
		\update_post_meta( $post_id, '_notification_sent', $sent ? 1 : 0 );

		\wp_send_json_success( $this->success_message() );
	}

	/**
	 * Read the values of the selected form from the request.
	 *
	 * @param array $form Form definition.
	 * @param array $data Request data (unslashed).
	 * @return array name, email, phone, company, message, fields (label/value pairs), error.
	 */
	private function read_form( array $form, array $data ): array {
		$result = array(
			'name'    => '',
			'email'   => '',
			'phone'   => '',
			'company' => '',
			'message' => '',
			'fields'  => array(),
			'error'   => '',
		);

		foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
			if ( !\is_array( $field ) ) {
				continue;
			}

			$id   = \sanitize_key( (string) ( $field['id'] ?? '' ) );
			$type = Forms::clean_type( $field['type'] ?? 'text' );

			// Files are not uploaded by this handler, so such fields are skipped.
			if ( '' === $id || 'file' === $type ) {
				continue;
			}

			$label = (string) ( $field['label'] ?? $id );
			$value = $this->clean_value( $type, $data[ $id ] ?? '' );

			if ( '' === $value && !empty( $field['required'] ) && 'hidden' !== $type ) {
				$result['error'] = \sprintf(
					/* translators: %s: field label */
					\__( 'Please fill in: %s', 'doit_get_quote' ),
					$label
				);

				return $result;
			}

			if ( '' === $value ) {
				continue;
			}

			if ( 'email' === $type && '' === $result['email'] ) {
				$result['email'] = $value;
			}

			if ( 'text' === $type ) {
				foreach ( array( 'name', 'phone', 'company' ) as $key ) {
					if ( '' === $result[ $key ] && false !== \strpos( $id, $key ) ) {
						$result[ $key ] = $value;
					}
				}
			}

			if ( 'textarea' === $type && '' === $result['message'] && false !== \strpos( $id, 'message' ) ) {
				$result['message'] = $value;
			}

			$result['fields'][] = array( $label, $value );
		}

		if ( '' === $result['phone'] && '' !== $result['email'] ) {
			$user = \get_user_by( 'email', $result['email'] );

			if ( $user ) {
				$result['phone'] = (string) \get_user_meta( $user->ID, 'billing_phone', true );
			}
		}

		return $result;
	}

	/**
	 * Clean one submitted value by field type.
	 *
	 * @param string $type Field type.
	 * @param mixed  $raw  Raw value (a list for checkboxes with several options).
	 * @return string
	 */
	private function clean_value( string $type, $raw ): string {
		if ( \is_array( $raw ) ) {
			$parts = array();

			foreach ( $raw as $part ) {
				if ( \is_scalar( $part ) && '' !== \sanitize_text_field( (string) $part ) ) {
					$parts[] = \sanitize_text_field( (string) $part );
				}
			}

			return \implode( ', ', $parts );
		}

		if ( !\is_scalar( $raw ) ) {
			return '';
		}

		$raw = (string) $raw;

		if ( 'email' === $type ) {
			return \sanitize_email( $raw );
		}

		if ( 'textarea' === $type ) {
			return \sanitize_textarea_field( $raw );
		}

		$value = \sanitize_text_field( $raw );

		// A single checkbox is submitted as "1".
		if ( 'checkbox' === $type && '1' === $value ) {
			return \__( 'Yes', 'doit_get_quote' );
		}

		return $value;
	}

	/**
	 * The items of the current cart.
	 *
	 * @return array
	 */
	private function cart_items(): array {
		if ( !\function_exists( 'WC' ) ) {
			return array();
		}

		if ( null === \WC()->cart && \function_exists( 'wc_load_cart' ) ) {
			\wc_load_cart();
		}

		if ( null === \WC()->cart ) {
			return array();
		}

		$items = array();

		foreach ( \WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( $product instanceof \WC_Product ) {
				$items[] = $this->describe( $product, (int) ( $cart_item['quantity'] ?? 1 ) );
			}
		}

		return $items;
	}

	/**
	 * One published product, or null.
	 *
	 * @param int $product_id Product ID.
	 * @param int $quantity   Quantity asked for.
	 * @return array|null
	 */
	private function product( int $product_id, int $quantity ): ?array {
		if ( !$product_id || !\function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = \wc_get_product( $product_id );

		if ( !$product instanceof \WC_Product || 'publish' !== $product->get_status() ) {
			return null;
		}

		return $this->describe( $product, $quantity );
	}

	/**
	 * What the emails and the quote need to know about a product.
	 *
	 * @param \WC_Product $product  Product.
	 * @param int         $quantity Quantity.
	 * @return array
	 */
	private function describe( \WC_Product $product, int $quantity ): array {
		$edit_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

		return array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'desc'      => $product->get_description(),
			'qty'       => $quantity,
			'price'     => $product->get_price(),
			'url'       => $product->get_permalink(),
			'admin_url' => \admin_url( 'post.php?post=' . $edit_id . '&action=edit' ),
			'stock'     => $product->is_in_stock() ? \__( 'In stock', 'doit_get_quote' ) : \__( 'Out of stock', 'doit_get_quote' ),
		);
	}

	/**
	 * The items as saved in _quote_products (what the Reply page reads).
	 *
	 * @param array $items Items from describe().
	 * @return array
	 */
	private function stored_products( array $items ): array {
		$stored = array();

		foreach ( $items as $item ) {
			$stored[] = array(
				'id'    => $item['id'],
				'name'  => $item['name'],
				'desc'  => $item['desc'],
				'qty'   => $item['qty'],
				'price' => $item['price'],
			);
		}

		return $stored;
	}

	/**
	 * The submitted fields as text, for the email and the post body.
	 *
	 * @param array $fields Label/value pairs.
	 * @return string
	 */
	private function fields_text( array $fields ): string {
		$text = "Submitted custom Form Fields from customer:\n\n";

		foreach ( $fields as $pair ) {
			$text .= $pair[0] . ': ' . $pair[1] . "\n";
		}

		return $text;
	}

	/**
	 * Plain text body of the quote_request post.
	 *
	 * @param string $email       Customer email.
	 * @param array  $items       Items.
	 * @param string $fields_text Submitted fields as text.
	 * @return string
	 */
	private function post_content( string $email, array $items, string $fields_text ): string {
		$lines = '';

		foreach ( $items as $item ) {
			$lines .= '- ' . $item['name'] . ' (Qty: ' . $item['qty'] . ")\n";
		}

		return "Customer Email: " . $email . "\n\nProducts:\n" . $lines . "\n" . $fields_text;
	}

	/**
	 * What the customer sees after a successful request.
	 *
	 * @return string
	 */
	private function success_message(): string {
		return \__( 'Thank you! Your quote request has been sent.', 'doit_get_quote' );
	}
}