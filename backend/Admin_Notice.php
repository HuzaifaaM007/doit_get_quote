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
 * Shows a one-time notice after a redirect (for example after saving a PDF).
 *
 * Use it from any backend class: Admin_Notice::add( 'success', 'Done.' );
 */
class Admin_Notice extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Queue a notice for the current user. It survives one redirect.
	 *
	 * @param string $type    'success' or 'error'.
	 * @param string $message Plain text message.
	 * @return void
	 */
	public static function add( string $type, string $message ): void {
		\set_transient(
			self::key(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			45
		);
	}

	/**
	 * Print the queued notice, once.
	 *
	 * @return void
	 */
	public function render() {
		$notice = \get_transient( self::key() );

		if ( !$notice ) {
			return;
		}

		\delete_transient( self::key() );

		$class = ( 'error' === ( $notice['type'] ?? '' ) ) ? 'notice-error' : 'notice-success';

		\printf(
			'<div class="notice %s is-dismissible"><p>%s</p></div>',
			\esc_attr( $class ),
			\esc_html( $notice['message'] ?? '' )
		);
	}

	/**
	 * Transient key for the current user.
	 *
	 * @return string
	 */
	private static function key(): string {
		return 'doitgq_admin_notice_' . \get_current_user_id();
	}
}