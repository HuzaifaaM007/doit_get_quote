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

use DOIT_Get_Quote\Engine\Base;

/**
 * Create the settings page in the backend
 */
class Settings_Page extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
			return;
		}

		// Add the options page and menu item.
		\add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		$realpath        = (string) \realpath( __DIR__ );
		$plugin_basename = \plugin_basename( \plugin_dir_path( $realpath ) . D_TEXTDOMAIN . '.php' );
		\add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		/*
		 * Add a settings page for this plugin to the Settings menu
		 *
		 * @TODO:
		 *
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities

		add_options_page( __( 'Page Title', D_TEXTDOMAIN ), D_NAME, 'manage_options', D_TEXTDOMAIN, array( $this, 'display_plugin_admin_page' ) );
		 *
		 */
		/*
		 * Add a settings page for this plugin to the main menu
		 *
		 */
		\add_menu_page( \__( 'DOIT_Get_Quote Settings', D_TEXTDOMAIN ), D_NAME, 'manage_options', D_TEXTDOMAIN, array( $this, 'display_plugin_admin_page' ), 'dashicons-hammer', 90 );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_plugin_admin_page() {
		include_once D_PLUGIN_ROOT . 'backend/views/admin.php';
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links Array of links.
	 * @return array
	 */
	public function add_action_links( array $links ) {
		return \array_merge(
			array(
				'settings' => '<a href="' . \admin_url( 'options-general.php?page=' . D_TEXTDOMAIN ) . '">' . \__( 'Settings', D_TEXTDOMAIN ) . '</a>',
			),
			$links
		);
	}

}
