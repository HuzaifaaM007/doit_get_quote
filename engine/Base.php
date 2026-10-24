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

namespace DOIT_Get_Quote\Engine;

/**
 * Base skeleton of the plugin
 */
class Base {

	/**
	 * @var array The settings of the plugin.
	 */
	public $settings = array();

	/**
	 * Initialize the class and get the plugin settings
	 *
	 * @return bool
	 */
	public function initialize() {
		$this->settings = \d_get_settings();

		return true;
	}

}
