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
use Override;

/**
 * Post Types and Taxonomies
 */

class PostTypes extends Base
{

	/**
	 * Initialize the class
	 * 
	 * @return void|bool
	 */
	#[Override]
	public function initialize()
	{
		parent::initialize();

		\add_action('init', array($this, 'register_quote_request'));
	}

	/**
	 * Register the quote_request post type.
	 *
	 * @return void
	 */
	public function register_quote_request(): void
	{
		$labels = array(
			'name'          =>  \__('Quote Requests', 'doit_get_quote'),
			'singular_name' =>  \__('Quote Request',  'doit_get_quote'),
		);

		\register_post_type(
			'quote_request',
			array(
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 26,
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
				'supports'        => array('title', 'editor', 'custom-fields'),
			)
		);
	}
}
