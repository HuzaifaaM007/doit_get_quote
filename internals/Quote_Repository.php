<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Internals;

/**
 * Saves a quote request as a quote_request post.
 *
 * The post meta keys are the ones the Reply page and the meta box read:
 * _customer_email, _customer_name, _customer_phone and _quote_products.
 */
class Quote_Repository {

	/**
	 * Create the quote request.
	 *
	 * Keys of $quote: name, email, phone (strings), products (array of
	 * id, name, desc, qty, price) and content (plain text for the post body).
	 *
	 * @param array $quote Quote data, already sanitized.
	 * @return int The new post ID, or 0 when it could not be saved.
	 */
	public static function create( array $quote ): int {
		$post_id = \wp_insert_post(
			\wp_slash(
				array(
					'post_title'   => (string) ( $quote['name'] ?? '' ) . '-Quote Request',
					'post_content' => (string) ( $quote['content'] ?? '' ),
					'post_status'  => 'publish',
					'post_type'    => 'quote_request',
				)
			),
			true
		);

		if ( \is_wp_error( $post_id ) || !$post_id ) {
			return 0;
		}

		\update_post_meta( $post_id, '_customer_email', \wp_slash( (string) ( $quote['email'] ?? '' ) ) );
		\update_post_meta( $post_id, '_customer_name', \wp_slash( (string) ( $quote['name'] ?? '' ) ) );
		\update_post_meta( $post_id, '_customer_phone', \wp_slash( (string) ( $quote['phone'] ?? '' ) ) );
		\update_post_meta( $post_id, '_quote_products', \wp_slash( (array) ( $quote['products'] ?? array() ) ) );

		return (int) $post_id;
	}
}