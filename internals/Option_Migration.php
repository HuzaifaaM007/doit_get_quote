<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Internals;

use DOIT_Get_Quote\Engine\Base;

/**
 * Copies the settings saved by the old plugin (wcgpq_*) to the new names
 * (doitgq_*), once.
 *
 * The old options are only read, never changed or deleted, so the old plugin
 * keeps working if it is reactivated. A new option that already exists is
 * never overwritten.
 */
class Option_Migration extends Base {

	const DONE_OPTION = 'doitgq_options_migrated';
	const MISSING     = '__doitgq_missing__';

	/**
	 * Option names without the prefix.
	 */
	const OPTIONS = array(
		'forms',
		'form_type',
		'mailing_service',
		'mailer_username',
		'mailer_password',
		'sender_mail',
		'admin_email',
		'email_subject',
		'email_template_for_cart_quote',
		'email_template_for_product_quote',
		'location_product_list',
		'location_product_details',
		'location_cart',
	);

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'admin_init', array( $this, 'migrate' ), 5 );
	}

	/**
	 * Copy the options, unless it was already done.
	 *
	 * @return void
	 */
	public function migrate() {
		if ( \get_option( self::DONE_OPTION ) ) {
			return;
		}

		foreach ( self::OPTIONS as $name ) {
			$old = \get_option( 'wcgpq_' . $name, self::MISSING );

			if ( self::MISSING === $old ) {
				continue;
			}

			if ( self::MISSING !== \get_option( 'doitgq_' . $name, self::MISSING ) ) {
				continue;
			}

			\update_option( 'doitgq_' . $name, $old );
		}

		\update_option( self::DONE_OPTION, 1 );
	}
}