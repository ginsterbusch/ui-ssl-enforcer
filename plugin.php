<?php
/**
 * Plugin Name: UI SSL Enforcer
 * Text Domain: ui-ssl-enforcer
 * Plugin URI: https://github.com/ginsterbusch/ui-ssl-enforcer
 * Author: Fabian Wolf
 * Author URI: https://usability-idealist.net
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: Combines several techniques and strategies to enforce SSL everywhere. Partially based upon <a hred="https://surniaulula.com/extend/plugins/jsm-force-ssl/">JSM's Force SSL / HTTPS</a> and <a href="https://wordpress.org/plugins/http-https-remover/">HTTP / HTTPS Remover</a>. Hooks into the content filters. Supports Hyper Cache out of the box.
 * Requires PHP: 5.6
 * Requires At Least: 3.6
 * Tested Up To: 5.2.4
 * Version: 1.5.1
 */

if( defined( '_UI_SSL_ENFORCER_DOM_PARSER' ) && _UI_SSL_ENFORCER_DOM_PARSER != false ) {
	
	if( ! class_exists( 'simple_html_dom' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'includes/simple_html_dom.php' );
	}
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/base.class.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/enforcer.class.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/settings.class.php' );


/**
 * Define some standard WordPress constants, if not already defined. These
 * constants can be pre-defined as false in wp-config.php to turn disable a
 * specific forced SSL feature.
 */

/**
 * @since 1.4
 */
if( class_exists( '_ui_SSL_Enforcer_Settings' ) ) {
	_ui_SSL_Enforcer_Settings::setup_constants();
	
} else { // fallback option
	
	if ( ! defined( 'FORCE_SSL' ) ) {
		define( 'FORCE_SSL', true );
	}

	if ( ! defined( 'FORCE_SSL_ADMIN' ) ) {
		define( 'FORCE_SSL_ADMIN', true );
	}

	if ( ! defined( 'FORCE_SSL_LOGIN' ) ) {
		define( 'FORCE_SSL_LOGIN', true );
	}
}



/**
 * Init main class
 */

if( class_exists( '_ui_SSL_Enforcer' ) ) {
	add_action( 'plugins_loaded', array( '_ui_SSL_Enforcer', 'get_instance' ) );
	//_ui_SSL_Enforcer::get_instance();
}

