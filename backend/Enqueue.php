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

namespace DOIT_Get_Quote\Backend;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use DOIT_Get_Quote\Engine\Base;

/**
 * This class contain the Enqueue stuff for the backend
 */
class Enqueue extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
			return;
		}

		\add_action( AssetManager::ACTION_SETUP, array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets with Inpyside library https://inpsyde.github.io/assets
	 *
	 * @param \Inpsyde\Assets\AssetManager $asset_manager The class.
	 * @return void
	 */
	public function enqueue_assets( AssetManager $asset_manager ) {
		// Load admin style sheet and JavaScript.
		$assets = $this->enqueue_admin_styles();

		if ( !empty( $assets ) ) {
			foreach ( $assets as $asset ) {
				$asset_manager->register( $asset );
			}
		}

		$assets = $this->enqueue_admin_scripts();

		if ( !empty( $assets ) ) {
			foreach ( $assets as $asset ) {
				$asset_manager->register( $asset );
			}
		}

	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function enqueue_admin_styles() {
		$admin_page = \get_current_screen();
		$styles     = array();

		$styles[1] = new Style( D_TEXTDOMAIN . '-admin-style', \plugins_url( 'assets/build/plugin-admin.css', D_PLUGIN_ABSOLUTE ) );
		$styles[1]->forLocation( Asset::BACKEND )->withVersion( D_VERSION );
		$styles[1]->withDependencies( 'dashicons' );

		return $styles;
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function enqueue_admin_scripts() {
		$admin_page = \get_current_screen();
		$scripts    = array();
		$scripts[1] = new Script( D_TEXTDOMAIN . '-settings-admin', \plugins_url( 'assets/build/plugin-admin.js', D_PLUGIN_ABSOLUTE ) );
		$scripts[1]->forLocation( Asset::BACKEND )->withVersion( D_VERSION );
		$scripts[1]->dependencies();

		return $scripts;
	}

}
