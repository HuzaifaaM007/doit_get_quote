<?php

/**
 * @package   DOIT_Get_Quote
 * @author    Huzaifa Murtaza <huzaifamurtaza007@gmail.com>
 * @copyright 2026 DOIT Technologies
 * @license   GPL 2.0+
 * @link      
 *
 * Plugin Name:     DOIT_Get_Quote
 * Plugin URI:      @TODO
 * Description:     Product quote plugin 
 * Version:         1.0.0
 * Author:          Huzaifa Murtaza
 * Author URI:      
 * Text Domain:     doit_get_quote
 * License:         GPL 2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * WordPress-Plugin-Boilerplate-Powered: v3.3.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	die('We\'re sorry, but you can not directly access this file.');
}

define('D_VERSION', '1.0.0');
define('D_TEXTDOMAIN', 'doit_get_quote');
define('D_NAME', 'DOIT_Get_Quote');
define('D_PLUGIN_ROOT', plugin_dir_path(__FILE__));
define('D_PLUGIN_ABSOLUTE', __FILE__);
define('D_MIN_PHP_VERSION', '7.4');
define('D_WP_VERSION', '5.3');

add_action(
	'init',
	static function () {
		load_plugin_textdomain(D_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
	}
);

if (version_compare(PHP_VERSION, D_MIN_PHP_VERSION, '<=')) {
	add_action(
		'admin_init',
		static function () {
			deactivate_plugins(plugin_basename(__FILE__));
		}
	);
	add_action(
		'admin_notices',
		static function () {
			echo wp_kses_post(
				sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					__('"DOIT_Get_Quote" requires PHP 5.6 or newer.', D_TEXTDOMAIN)
				)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}

$doit_get_quote_libraries = require D_PLUGIN_ROOT . 'vendor/autoload.php'; //phpcs:ignore

require_once D_PLUGIN_ROOT . 'functions/functions.php';
require_once D_PLUGIN_ROOT . 'functions/debug.php';

// Add your new plugin on the wiki: https://github.com/WPBP/WordPress-Plugin-Boilerplate-Powered/wiki/Plugin-made-with-this-Boilerplate

$requirements = new \Micropackage\Requirements\Requirements(
	'DOIT_Get_Quote',
	array(
		'php'            => D_MIN_PHP_VERSION,
		'php_extensions' => array('mbstring'),
		'wp'             => D_WP_VERSION,
		// 'plugins'            => array(
		// array( 'file' => 'hello-dolly/hello.php', 'name' => 'Hello Dolly', 'version' => '1.5' )
		// ),
	)
);

if (! $requirements->satisfied()) {
	$requirements->print_notice();

	return;
}




if (! wp_installing()) {
	register_activation_hook(D_TEXTDOMAIN . '/' . D_TEXTDOMAIN . '.php', array(new \DOIT_Get_Quote\Backend\ActDeact, 'activate'));
	register_deactivation_hook(D_TEXTDOMAIN . '/' . D_TEXTDOMAIN . '.php', array(new \DOIT_Get_Quote\Backend\ActDeact, 'deactivate'));
	add_action(
		'plugins_loaded',
		static function () use ($doit_get_quote_libraries) {
			new \DOIT_Get_Quote\Engine\Initialize($doit_get_quote_libraries);
		}
	);
}

add_action( 'doing_it_wrong_run', function ($function) {
	if ('_load_textdomain_just_in_time' === $function) {
		error_log(wp_debug_backtrace_summary());
	}
});

add_action( 'doing_it_wrong_run', function ( $function ) {
    if ( '_load_textdomain_just_in_time' === $function ) {
        error_log( wp_debug_backtrace_summary() );
    }
} );