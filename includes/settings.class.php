<?php
/**
 * Both used for accessing / updating settings AND testing specific conditions based on settings
 */

if ( ! class_exists( '_ui_SSL_Enforcer_Settings' ) ) {

	class _ui_SSL_Enforcer_Settings extends _ui_SSL_Enforcer_Base {
		const pluginPrefix = '_ui_ssl_enforcer_';
				
		/**
		 * NOTE: No need for a Singleton Factory pattern for the plugin main class ... ^_^
		 */
		
		public static function get_instance() {
			$local_instance = new self( true );
		}

	
		public function __construct( $plugin_init = false ) {

			//add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

			/**
			 * WordPress should redirect back-end / admin URLs just
			 * fine, but the front-end may need some help. Hook the
			 * 'init' action and check the protocol if FORCE_SSL is
			 * true.
			 */
			if( !empty( $plugin_init ) ) {
				$this->check_ssl_constants();
				
			}
		}
	
		
	
		function _get_settings( $default_value = array() ) {
			$return = $default_value;
			
			$arrSettings = get_option( $this->add_plugin_prefix( 'settings' ), $default_value );
			
			return $return;
		}
	
		function get_setting( $name = '', $default_value = null ) {
			$return = $default_value;
			
			if( !empty( $name ) ) {
				$setting_name = sanitize_key( $name );
			}
			
			if( !empty( $setting_name ) ) {
			
				$arrSettings = $this->_get_settings();
				
				if( !empty( $arrSettings ) && is_array( $arrSettings ) && isset( $arrSettings[ $setting_name ] ) && $arrSettings[ $setting_name ] !== $default_value ) {
					$return = $arrSettings[ $setting_name ];
				}
			}
			
			return $return;
		}
		
		function check_ssl_constants() {
			if ( ! defined( 'FORCE_SSL' ) && $this->get_setting( 'enable_force_ssl', true ) !== false ) {
				define( 'FORCE_SSL', true );
			}

			if ( ! defined( 'FORCE_SSL_ADMIN' ) && $this->get_setting( 'enable_force_ssl_admin', true ) !== false ) {
				define( 'FORCE_SSL_ADMIN', true );
			}

			if ( ! defined( 'FORCE_SSL_LOGIN' ) && $this->get_setting( 'enable_force_ssl_login', true ) !== false ) {
				define( 'FORCE_SSL_LOGIN', true );
			}
		}
		
		function check_plugin_constants() {
			
			if( ! defined( '_UI_SSL_ENFORCER_DOM_PARSER' ) && $this->get_setting( 'enable_dom_parser', false ) !== false ) {
				define( '_UI_SSL_ENFORCER_DOM_PARSER' );
				
				if( ! class_exists( 'simple_html_dom' ) ) {
					require_once( 'includes/simple_html_dom.php' );
				}
				
			}
			
			if( ! defined( '_UI_SSL_ENFORCER_USE_OUTPUT_BUFFER' ) && $this->get_setting( 'enable_output_buffer', false ) !== false ) {	
				define( '_UI_SSL_ENFORCER_USE_OUTPUT_BUFFER' );
			}
			
			/**
			 * @since 1.5.2
			 * Also see @link https://www.fastcomet.com/tutorials/security/resolving-issues-with-hsts and @link https://really-simple-ssl.com/knowledge-base/what-does-hsts-mean/
			 */
			 
			if( ! defined( '_UI_SSL_ENFORCER_USE_HSTS' ) && $this->get_setting( 'enable_hsts', false ) !== false ) {
				define( '_UI_SSL_ENFORCER_USE_HSTS', true );
			}
			
		}
	
		public static function setup_constants() {
			$enforcer_settings = new self();
			$enforcer_settings->check_ssl_constants();
			$enforcer_settings->check_plugin_constants();
		}
	}
}
