<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Backend;

use DOIT_Get_Quote\Engine\Base;

/**
 * The "Reply to quote" screen.
 *
 * - adds a "Reply" link under each quote request in the list table,
 * - registers a hidden admin page with the quotation / invoice form,
 *
 * The form is submitted to Backend\Pdf_Handler.
 */
class Reply_Page extends Base {

	const SLUG = 'quote-request-reply';

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'admin_menu', array( $this, 'add_page' ) );
		\add_filter( 'post_row_actions', array( $this, 'add_row_action' ), 10, 2 );
	}

	/**
	 * Register the page without a menu entry.
	 *
	 * The 'options.php' parent hides it from the menu. The capability matches
	 * Pdf_Handler, so nobody can open a form they are not allowed to submit.
	 *
	 * @return void
	 */
	public function add_page() {
		\add_submenu_page(
			'options.php',
			\__( 'Reply to Quote Request', 'doit_get_quote' ),
			\__( 'Reply to Quote', 'doit_get_quote' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Add the "Reply" link to the quote requests list.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Current row's post.
	 * @return array
	 */
	public function add_row_action( $actions, $post ) {
		if ( 'quote_request' !== $post->post_type || !\current_user_can( 'manage_options' ) ) {
			return $actions;
		}

		$url = \add_query_arg(
			array(
				'page'    => self::SLUG,
				'post_id' => $post->ID,
			),
			\admin_url( 'admin.php' )
		);

		$actions['reply'] = '<a href="' . \esc_url( $url ) . '">' . \esc_html__( 'Reply', 'doit_get_quote' ) . '</a>';

		return $actions;
	}

	/**
	 * Print the page.
	 *
	 * @return void
	 */
	public function render() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page.
		$post_id = isset( $_GET['post_id'] ) ? \absint( $_GET['post_id'] ) : 0;
		$post    = $post_id ? \get_post( $post_id ) : null;

		if ( !$post || 'quote_request' !== $post->post_type || !\current_user_can( 'edit_post', $post_id ) ) {
			\wp_die( \esc_html__( 'Invalid request.', 'doit_get_quote' ), 403 );
		}

		$email = (string) \get_post_meta( $post_id, '_customer_email', true );
		$name  = (string) \get_post_meta( $post_id, '_customer_name', true );
		$phone = (string) \get_post_meta( $post_id, '_customer_phone', true );

		// Older requests may not have the name / phone saved: try the customer's account.
		if ( '' === $name || '' === $phone ) {
			$user = \get_user_by( 'email', $email );

			if ( $user ) {
				$name  = '' === $name ? (string) $user->first_name : $name;
				$phone = '' === $phone ? (string) \get_user_meta( $user->ID, 'billing_phone', true ) : $phone;
			}
		}

		$items = \get_post_meta( $post_id, '_quote_products', true );

		$data = array(
			'post_id'  => $post_id,
			'items'    => \is_array( $items ) ? \array_values( $items ) : array(),
			'name'     => $name,
			'phone'    => $phone,
			// Change the default with: add_filter( 'doitgq_default_tax_rate', fn() => 5.0 );
			'tax_rate' => (float) \apply_filters( 'doitgq_default_tax_rate', 17.0 ),
			'pdf_file' => (string) \get_post_meta( $post_id, '_quote_pdf_file', true ),
			'pdf_time' => (int) \get_post_meta( $post_id, '_quote_pdf_generated_at', true ),
		);

		include __DIR__ . '/views/reply-page.php';
	}
}