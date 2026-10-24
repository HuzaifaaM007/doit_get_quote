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

namespace DOIT_Get_Quote\Internals;

use DOIT_Get_Quote\Engine\Base;

/**
 * Post Types and Taxonomies
 */
class PostTypes extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() { // phpcs:ignore
		parent::initialize();

		\add_action( 'init', array( $this, 'load_cpts' ) );
		// Add bubble notification for cpt pending
		\add_action( 'admin_menu', array( $this, 'pending_cpt_bubble' ), 999 );
	}


	/**
	 * Load CPT and Taxonomies on WordPress
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_cpts() { //phpcs:ignore
		// Create Custom Post Type https://github.com/johnbillion/extended-cpts/wiki
		$demo_cpt = \register_extended_post_type(
				'demo',
				array(
					// Show all posts on the post type archive:
					'archive'            => array(
						'nopaging' => true,
					),
					'slug'               => 'demo',
					'show_in_rest'       => true,
					'dashboard_activity' => true,
					'capability_type'    => array( 'demo', 'demoes' ),
					// Add some custom columns to the admin screen
					'admin_cols'         => array(
						'featured_image' => array(
							'title'          => 'Featured Image',
							'featured_image' => 'thumbnail',
						),
						'title',
						'genre'          => array(
							'taxonomy' => 'demo-section',
						),
						'custom_field'   => array(
							'title'    => 'By Lib',
							'meta_key' => '_demo_' . D_TEXTDOMAIN . '_text', // phpcs:ignore
							'cap'      => 'manage_options',
						),
						'date'           => array(
							'title'   => 'Date',
							'default' => 'ASC',
						),
					),
					// Add a dropdown filter to the admin screen:
					'admin_filters'      => array(
						'genre' => array(
							'taxonomy' => 'demo-section',
						),
					),
			),
			array(
				// Override the base names used for labels:
				'singular' => \__( 'Demo', D_TEXTDOMAIN ),
				'plural'   => \__( 'Demos', D_TEXTDOMAIN ),
			)
		);

		$demo_cpt->add_taxonomy( 'demo-section', array( 'hierarchical' => false, 'show_ui' => false ) );
		// Create Custom Taxonomy https://github.com/johnbillion/extended-taxos
		\register_extended_taxonomy(
			'demo-section',
			'demo',
			array(
				// Use radio buttons in the meta box for this taxonomy on the post editing screen:
				'meta_box'         => 'radio',
				// Show this taxonomy in the 'At a Glance' dashboard widget:
				'dashboard_glance' => true,
				// Add a custom column to the admin screen:
				'admin_cols'       => array(
					'featured_image' => array(
						'title'          => 'Featured Image',
						'featured_image' => 'thumbnail',
					),
				),
				'slug'             => 'demo-cat',
				'show_in_rest'     => true,
				'capabilities'     => array(
					'manage_terms' => 'manage_demoes',
					'edit_terms'   => 'manage_demoes',
					'delete_terms' => 'manage_demoes',
					'assign_terms' => 'read_demo',
				),
			),
			array(
				// Override the base names used for labels:
				'singular' => \__( 'Demo Category', D_TEXTDOMAIN ),
				'plural'   => \__( 'Demo Categories', D_TEXTDOMAIN ),
			)
		);
	}

	/**
	 * Bubble Notification for pending cpt<br>
	 * NOTE: add in $post_types your cpts<br>
	 *
	 *        Reference:  http://wordpress.stackexchange.com/questions/89028/put-update-like-notification-bubble-on-multiple-cpts-menus-for-pending-items/95058
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pending_cpt_bubble() {
		global $menu;

		$post_types = array( 'demo' );

		foreach ( $post_types as $type ) {
			if ( !\post_type_exists( $type ) ) {
				continue;
			}

			// Count posts
			$cpt_count = \wp_count_posts( $type );

			if ( !$cpt_count->pending ) {
				continue;
			}

			// Locate the key of
			$key = self::recursive_array_search_php( 'edit.php?post_type=' . $type, $menu );

			// Not found, just in case
			if ( !$key ) {
				return;
			}

			// Modify menu item
			$menu[ $key ][ 0 ] .= \sprintf( //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				'<span class="update-plugins count-%1$s"><span class="plugin-count">%1$s</span></span>',
				$cpt_count->pending
			);
		}
	}

	/**
	 * Required for the bubble notification<br>
	 *
	 *  Reference:  http://wordpress.stackexchange.com/questions/89028/put-update-like-notification-bubble-on-multiple-cpts-menus-for-pending-items/95058
	 *
	 * @param string $needle First parameter.
	 * @param array  $haystack Second parameter.
	 * @since 1.0.0
	 * @return string|bool
	 */
	private function recursive_array_search_php( string $needle, array $haystack ) {
		foreach ( $haystack as $key => $value ) {
			$current_key = $key;

			if (
				$needle === $value ||
				( \is_array( $value ) &&
				false !== self::recursive_array_search_php( $needle, $value ) )
			) {
				return $current_key;
			}
		}

		return false;
	}

}
