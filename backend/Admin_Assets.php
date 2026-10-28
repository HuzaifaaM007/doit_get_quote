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
 * Loads the built admin script and style (assets/build/plugin-admin.*) on the
 * plugin's own screens only: the Reply page and the WooCommerce settings tab.
 *
 * They are built from assets/src/plugin-admin.js by `npm run build`.
 */
class Admin_Assets extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the built files.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( !$this->is_plugin_screen() ) {
			return;
		}

		$root  = \dirname( __DIR__ );
		$base  = $root . '/assets/build/plugin-admin';
		$url   = \plugins_url( 'assets/build/plugin-admin', $root . '/doit_get_quote.php' );
		$asset = array(
			'dependencies' => array(),
			'version'      => false,
		);

		if ( \is_readable( $base . '.asset.php' ) ) {
			$asset = include $base . '.asset.php';
		}

		if ( \file_exists( $base . '.js' ) ) {
			\wp_enqueue_script( 'doitgq-admin', $url . '.js', $asset['dependencies'], $asset['version'], true );
		}

		if ( \file_exists( $base . '.css' ) ) {
			\wp_enqueue_style( 'doitgq-admin', $url . '.css', array(), $asset['version'] );
		}
	}

	/**
	 * Is the current admin screen one of ours?
	 *
	 * @return bool
	 */
	private function is_plugin_screen(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- only compares slugs.
		$page = isset( $_GET['page'] ) ? \sanitize_key( \wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) ? \sanitize_key( \wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:enable

		return Reply_Page::SLUG === $page || ( 'wc-settings' === $page && Settings_Tab::TAB === $tab );
	}
}