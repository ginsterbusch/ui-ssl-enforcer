<?php

if ( ! class_exists( '_ui_SSL_Enforcer_Admin' ) ) {

	class _ui_SSL_Enforcer_Admin extends _ui_SSL_Enforcer_Base {
		const pluginPrefix = '_ui_ssl_enforcer_';
				
		public static function get_instance() {
			$local_instance = new self( );
		}


		public function __construct( ) {

			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			add_action( 'admin_menu', array( $this, 'add_admin_screens' ) );
		}
		
		function load_textdomain() {
			load_plugin_textdomain( 'ui-ssl-enforcer-admin', false, 'ui-ssl-enforcer/languages/' );
		}
	

		function add_admin_screens() {
			$hook_suffix = add_submenu_page( 
				'tools.php', 
				__( 'UI SSL Enforcer Settings', 'ui-ssl-enforcer-admin' ), 
				__('UI SSL Enforcer', 'ui-ssl-enforcer-admin' ),
				'manage_options',
				self::$pluginPrefix . 'settings',
				array( $this, 'admin_page_settings' )
			);
		}
		
		/**
		 * Possible settings:
		 * - enable simple cache / output buffer mode => disabled when using hyper cache
		 * - enable / disable constants
		 * - enable / disable URL filters
		 * - add custom URL filters
		 */
		function admin_page_settings( ) {
			$strAction = ( !empty( $_POST[ 'action' ] ) ? $_POST[ 'action' ] : $_GET[ 'action' ] );
			
			if( !empty( $strAction ) ) {
				$strAction = sanitize_key( $strAction );
			}
			
			if( $strAction == 'save' ) {
				$this->save_settings();
			}
			
		}
		
		function save_settings( ) {
			
		
			
		}
	}
}
