<?php

/**
 * DOIT_Get_Quote
 *
 * @package   DOIT_Get_Quote
 * @license   GPL 2.0+
 */

namespace DOIT_Get_Quote\Frontend;

use DOIT_Get_Quote\Ajax\Quote_Submit;
use DOIT_Get_Quote\Engine\Base;


/**
 * Loads the built front-end script and style (assets/build/plugin-public.*),
 * only on pages that show a quote button.
 *
 * Quote_Button calls Public_Assets::enqueue() when it prints a button, so
 * pages without a button load nothing. The files are built from
 * assets/src/plugin-public.js by `npm run build`.
 */
class Public_Assets extends Base {

	const HANDLE = 'doitgq-public';

	/**
	 * Already enqueued during this request?
	 *
	 * @var bool
	 */
	private static $enqueued = false;

	/**
	 * Enqueue the script and style, and pass the settings to the script.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( self::$enqueued ) {
			return;
		}

		self::$enqueued = true;

		$root  = \dirname( __DIR__ );
		$base  = $root . '/assets/build/plugin-public';
		$url   = \plugins_url( 'assets/build/plugin-public', $root . '/doit_get_quote.php' );
		$asset = array(
			'dependencies' => array(),
			'version'      => false,
		);

		if ( \is_readable( $base . '.asset.php' ) ) {
			$asset = include $base . '.asset.php';
		}

		if ( \file_exists( $base . '.js' ) ) {
			\wp_enqueue_script( self::HANDLE, $url . '.js', $asset['dependencies'], $asset['version'], true );

			\wp_localize_script(
				self::HANDLE,
				'doitgqQuote',
				array(
					'ajaxUrl' => \admin_url( 'admin-ajax.php' ),
					'action'  => Quote_Submit::ACTION,
					'nonce'   => \wp_create_nonce( Quote_Submit::NONCE_ACTION ),
					'i18n'    => array(
						'sending' => \__( 'Sending...', 'doit_get_quote' ),
						'error'   => \__( 'Something went wrong. Please try again.', 'doit_get_quote' ),
					),
				)
			);
		}

		if ( \file_exists( $base . '.css' ) ) {
			\wp_enqueue_style( self::HANDLE, $url . '.css', array(), $asset['version'] );
		}
	}
}