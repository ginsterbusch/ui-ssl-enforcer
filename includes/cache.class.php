<?php
/**
 * Optional simple Caching class for SSL Enforcer
 * Has to be explicitely enabled (see the documentation for more info).
 */


if ( ! class_exists( '_ui_SSL_Enforcer_Cache' ) ) {
	/**
	 * Is hyper cache enabled and available?
	 */
	
	protected $is_hyper_cache = false;
	
	/**
	 * Is W3 
	

	class _ui_SSL_Enforcer_Cache extends _ui_SSL_Enforcer_Base {
		const pluginPrefix = '_ui_ssl_enforcer_';
				
		/**
		 * NOTE: No need for a Singleton Factory pattern for the plugin main class ... ^_^
		 */
		
		public static function get_instance() {
			new self( true );
		}


		public function __construct( $plugin_init = false ) {

				// filter hyper cache (if available)
				if( function_exists( 'hyper_cache_callback' ) ) {
					add_filter( 'cache_buffer', array( $this, 'filter_hyper_cache_content' ) );
					$this->is_hyper_cache = true;
				}
				
				
			}
		}


		/**
		 * NOTE: No real nead for translation (either), because of no output
		 */
		
		function load_textdomain() {
			load_plugin_textdomain( 'ui-ssl-enforcer', false, 'ui-ssl-enforcer/languages/' );
		}
	

		/**
		 * Redirect from HTTP to HTTPS if the current webpage URL is
		 * not HTTPS. A 301 redirect is considered a best practice when
		 * moving from HTTP to HTTPS. See
		 * https://en.wikipedia.org/wiki/HTTP_301 for more info.
		 */
		function force_ssl_redirect() {
			/**
			 * Make sure web server variables exist in case WP is
			 * being used from the command line.
			 */
			if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
				if ( ! $this->is_https() ) {
					wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301 );
					exit();
				}
			}
		}

		/**
		 * Make sure URLs from the upload directory - like images in
		 * the Media Library - use the correct protocol. Adjusts the
		 * 'url' and 'baseurl' array keys to match the current protocol
		 * being used (HTTP or HTTPS).
		 */
		function upload_dir_urls( $param ) {
			foreach ( array( 'url', 'baseurl' ) as $key ) {
				$param[$key] = $this->update_url( $param[$key] );
			}
			return $param;
		}



		/**
		 * Wrapper for update_url to easily handle @hook script_loader_src
		 */

		function update_resource_url( $url, $handle = '' ) {
			return $this->update_url( $url );
		}

		function update_url( $url ) {
			if ( strpos( $url, '/' ) === 0 ) {	// skip relative urls
				return $url;
			}
			$prot_slash = $this->get_prot() . '://';
			if ( strpos( $url, $prot_slash ) === 0 ) {	// skip correct urls
				return $url;
			}
			return preg_replace( '/^([a-z]+:\/\/)/', $prot_slash, $url );
		}

		function get_prot( $url = '' ) {
			if ( ! empty( $url ) ) {
				return $this->is_https( $url ) ? 'https' : 'http';
			} elseif ( $this->is_https() ) {
				return 'https';
			} elseif ( is_admin() )  {
				if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
					return 'https';
				}
			} elseif ( defined( 'FORCE_SSL' ) && FORCE_SSL ) {
				return 'https';
			}
			return 'http';
		}

		/**
		 * Extend the WordPress is_ssl() function by also checking for
		 * proxy / load-balancing 'HTTP_X_FORWARDED_PROTO' and
		 * 'HTTP_X_FORWARDED_SSL' web server variables.
		 */
		function is_https( $url = '' ) {
			static $cache = array();
			if ( isset( $cache[$url] ) ) {
				return $cache[$url];
			}
			if ( ! empty( $url ) ) {
				if ( strpos( $url, '://' ) && 
					parse_url( $url, PHP_URL_SCHEME ) === 'https' ) {
					return $cache[$url] = true;
				} else {
					return $cache[$url] = false;
				}
			} else {
				if ( is_ssl() ) {
					return $cache[$url] = true;
				} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 
					strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) === 'https' ) {
					return $cache[$url] = true;
				} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 
					strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] ) === 'on' ) {
					return $cache[$url] = true;
				}
			}
			return $cache[$url] = false;
		}
		
		/**
		* detect AJAX call
		* @return bool
		*/
		function is_ajax() {
			$return = false;
			
			if( function_exists('wp_doing_ajax')) {
				$return = wp_doing_ajax();
			} else {
				$return = defined('DOING_AJAX') && DOING_AJAX;
			}

			return $return;
		}
		
		function _has_http( $text = '' ) {
			return ( ( !empty( $text ) && strpos( $text, 'http://' ) !== false ) ? true : false );
		}

		/**
		 * Filter hyper cache data right before output
		 */
		 
		function filter_hyper_cache_content( $content = '' ) {
			$return = $content;
			
			
			
			/**
			if( $this->_has_http( $content ) ) {
				//$return = $this->replace_text_urls( $return );
				$return = $this->replace_html_urls( $return );
			}*/
			
			
			if( $this->_has_http( $return ) ) {
				$return = $this->replace_content_urls( $return );
			}
			
			
			if( $this->_has_http( $return ) ) {
				$return = $this->replace_text_urls( $return );
			}
			
			return $return;
		}

		/**
		 * Replace all non-SSL URLs with their SSL equivalents. 
		 * Combined @method replace_html_urls and @method replace_text_urls
		 */

		function replace_all_urls( $content = '' ) {
			$return = $content;
			
			/**
			 * NOTE: Experimental feature - enable it by adding the constant _UI_SSL_ENFORCER_DOM_PARSER with TRUE as value (ie. define( '_UI_SSL_ENFORCER_DOM_PARSER', true ) )
			 * FIXME: Sometimes leads to errors inside inline script snippets. 
			 */
			
			if( $this->_has_http( $return ) ) {
				if( defined( '_UI_SSL_ENFORCER_DOM_PARSER' ) && _UI_SSL_ENFORCER_DOM_PARSER != false ) {
					$return = apply_filters( self::pluginPrefix . 'replace_html_urls', $this->replace_html_urls( $return ) );
				} else {
					$return = apply_filters( self::pluginPrefix . 'replace_content_urls', $this->replace_content_urls( $return ) );
				}
			}
			
			// replace anything thats still left
			if( $this->_has_http( $return ) ) {
				$return = apply_filters( self::pluginPrefix . 'replace_text_urls', $this->replace_text_urls( $return ) );
			}
			
			return $return;
		}

		/**
		 * DOM-aware version of @method replace_content_urls()
		 */
		function replace_html_urls( $content = '' ) {
			$return = $content;
			
			//if( !empty( $return ) && strpos( $return, 'http://' ) !== false ) {
			if( $this->_has_http( $return ) ) {
				if( class_exists( 'simple_html_dom' ) ) {

					/**
					 * @see https://dev.w3.org/html5/html-author/
					 */
					
					$arrKnownRefTags = apply_filters( self::pluginPrefix . 'get_ref_tags', array(
						'a' => 'href', 'link' => 'href', 'base' => 'href', 'img' => 'src', 'script' => 'src', 'form' => 'action', 'iframe' => 'src', 'embed' => 'src', 'video' => 'src', 'audio' => 'src', 'source' => 'src', 'area' => 'href', 'input' => 'src', 'blockquote' => 'cite',
						
					) );
					
					if( !empty( $arrKnownRefTags ) ) {
					
						$html = new simple_html_dom();
						$html->load( $return );
						
						foreach( $arrKnownRefTags as $strTag => $strRefAttr ) {
							$r = $html->find( $strTag . '[' . $strRefAttr . '^=http:]' );
							
							if( sizeof( $r ) > 0 ) { // parse and replace values
								foreach( $r as $strResultTag ) {
									
									$strResultTag->$strRefAttr = str_replace( 'http://', 'https://', $strResultTag->$strRefAttr );
								}
							}
						}
						
						$parse_result = $html->save();
						
						if( !empty( $parse_result ) ) {
							$return = $parse_result;
						}
					}
					
					
				} else { // reduced, simplistic work-around
					
					// links
					if( strpos( $return, '<a href="http://' ) !== false || strpos( $return, '<link href="http://' ) !== false || strpos( $return, '<area href="http://' ) !== false  || strpos( $return, '<base href="http://' ) !== false ) {
						$return = str_replace( array( '<a href="http://', '<link href="http://', '<area href="http://', '<base href="http://' ), array( '<a href="https://', '<link href="https://', '<area href="https://', '<base href="https://' ), $return );
					}
					
					// images
					if( strpos( $return, '<img src="http://' ) !== false ) {
						$return = str_replace( '<img src="http://', '<img src="https://', $return );
					}
					
					// embed, video, audio
					if( strpos( $return, '<embed src="http://' ) !== false || strpos( $return, '<audio src="http://' ) !== false || strpos( $return, '<video src="http://' ) !== false ) {
						$return = str_replace( array( '<embed src="http://', '<audio src="http://', '<video src="http://', ), array( '<embed src="https://', '<audio src="https://', '<video src="https://' ), $return );
					}
					
					// script sources
					if( strpos( $return, '<script src="http://' ) !== false || strpos( $return, '<script type="text/javascript" src="http://' ) !== false ) {
						$return = str_replace( array('<script src="http://', '<script type="text/javascript" src="http://'), array('<script src="https://', '<script type="text/javascript" src="https://'), $return );
					}
					
					// forms
					
					if( strpos( $return, '<form action="http://' ) !== false || strpos( $return, '<input src="http://' ) !== false ) {
						$return = str_replace( array( '<form action="http://', '<input src="http://', ), array( '<form action="https://', '<input src="http://', ), $return );
					}
				
				}
			
			}
			
			return $return;
		}
		
		/**
		 * Replace URLs everywhere with their SSL equivalent
		 */
		
		function replace_text_urls( $content = '' ) {
			$return = $content;
			
			if( $this->_has_http( $return ) ) {
				$return = str_replace( 'http://', 'https://', $return );
			}
			
			return $return;
		}

		/**
		 * Instead of output buffer parsing, we just fetch the most important filter contents ..
		 */

		function replace_content_urls( $content = '') {
			$return = $content;
		
			if( !empty( $return ) ) {

				$return = str_replace( array('http://'.$_SERVER['HTTP_HOST'],'https://'.$_SERVER['HTTP_HOST']), '//'. $_SERVER['HTTP_HOST'], $return );
				$return = str_replace('content="//' . $_SERVER['HTTP_HOST'], 'content="https://'.$_SERVER['HTTP_HOST'], $return);
				$return = str_replace('> //'.$_SERVER['HTTP_HOST'], '> https://'.$_SERVER['HTTP_HOST'], $return);
				$return = str_replace('"url" : "//', '"url" : "https://', $return);
				$return = str_replace('"url": "//', '"url": "https://', $return);
				
				$arrKnownURLs = apply_filters( self::pluginPrefix . 'get_known_urls', array(
					'google-api' => array(
						'search' =>  '|http://(.*?).googleapis.com|',
						'replace' => '|https://(.*?).googleapis.com|',
						'subject' => '//$1.googleapis.com',
					),
					'google' => array( 
						'search' => '|http://(.*?).google.com|',
						'replace' => '|https://(.*?).google.com|', 
						'subject' => '//$1.google.com'
					),
					'gravatar' => array(
						'search' => '|http://(.*?).gravatar.com|',
						'replace' => '|https://(.*?).gravatar.com|',
						'subject' => '//$1.gravatar.com',
					),
					'w.org' => array( 
						'search' => '|http://(.*?).w.org|',
						'replace' => '|https://(.*?).w.org|',
						'subject' => '//$1.w.org',
					),
				) );
				
				// search for a number of given urls and replace them with their SSL equivalent if found
				if( !empty( $arrKnownURLs ) ) {
					foreach( $arrKnownURLs as $strName => $arrSearchPattern ) {
						$return = preg_replace( array( $arrSearchPattern[ 'search' ], $arrSearchPattern[ 'replace' ] ), $arrSearchPattern[ 'subject' ], $return );
					}
				}
				
				/**
				 * Original patterns
				 */
				/*
				
				$return = preg_replace(array('|http://(.*?).googleapis.com|',	'|https://(.*?).googleapis.com|'), 	'//$1.googleapis.com', $return);
				$return = preg_replace(array('|http://(.*?).google.com|',		'|https://(.*?).google.com|'), 		'//$1.google.com', $return);
				$return = preg_replace(array('|http://(.*?).gravatar.com|',		'|https://(.*?).gravatar.com|'), 	'//$1.gravatar.com', $return);
				$return = preg_replace(array('|http://(.*?).w.org|',			'|https://(.*?).w.org|'), 			'//$1.w.org', $return);
				*/
				
			}
			
			return $return;	
		}
	}
}
