<?php

if ( ! class_exists( '_ui_SSL_Enforcer' ) ) {

	class _ui_SSL_Enforcer extends _ui_SSL_Enforcer_Base {
		const pluginPrefix = '_ui_ssl_enforcer_';
		
		private $is_cache_buffer = false;
				
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
				/**
				 * @since 1.4.3
				 */
				
				if( class_exists( '_ui_SSL_Enforcer_Settings' ) ) {
					$this->settings = new _ui_SSL_Enforcer_Settings();
				}
				
				if( !defined( 'FORCE_SSL' ) ) {
					//echo '<!-- Force SSL: ' . ( defined( 'FORCE_SSL' ) ? 'is enabled' : 'is sadly not enabled' ) . ' -->';
				}
				
				if ( defined( 'FORCE_SSL' ) && FORCE_SSL != false && ! is_admin() ) {
					add_action( 'init', array( $this, 'force_ssl_redirect' ), -9000 );
					add_action('wp', array($this, 'force_ssl_redirect'), 40, 3);
					add_action( 'wp_loaded', array( $this, 'force_ssl_redirect' ), 20 );
					
				} elseif( !defined( 'FORCE_SSL' ) && ! is_admin() ) {
					add_action('wp', array($this, 'force_ssl_redirect'), 40, 3);
					
					
				}

				/**
				 * Make sure URLs from the upload directory - like
				 * images in the Media Library - use the correct
				 * protocol.
				 */
				add_filter( 'upload_dir', array( $this, 'upload_dir_urls' ), 1000, 1 );


				/**
				 * Adjust the URL returned by the WordPress
				 * plugins_url() function.
				 */
				add_filter( 'plugins_url', array( $this, 'update_url' ), 1000, 1 );
				
				/**
				 * Adjust script and CSS source urls 
				 * Inspired by SSL Insecure Content Fixer :)
				 */
				add_filter('script_loader_src', array( $this, 'update_resource_url') );
				add_filter('style_loader_src', array( $this, 'update_resource_url' ) );
				
				/**
				 * Adjust URLs in content
				 */


				// catch plugins / themes overriding the user's avatar and breaking it
				add_filter('get_avatar', array($this, 'replace_content_urls'), 9999);

				// filter image links on front end e.g. in calls to wp_get_attachment_image(), wp_get_attachment_image_src(), etc.
				if (!is_admin() || $this->is_ajax() ) {
					add_filter('wp_get_attachment_url', array( $this, 'update_url' ), 100);
				}
				
				// first
				
				add_filter( 'the_content', array( $this, 'replace_all_urls' ), 10, 1 );
				add_filter( 'the_content_feed', array( $this, 'replace_all_urls' ), 10, 1 );
				
				// .. and last call (or so we guess)
				add_filter( 'the_content', array( $this, 'replace_all_urls' ), 1000, 1 );
				add_filter( 'the_content_feed', array( $this, 'replace_all_urls' ), 1000, 1 );
				
				// filter hyper cache (if available)
				if( function_exists( 'hyper_cache_callback' ) ) {
					add_filter( 'cache_buffer', array( $this, 'filter_hyper_cache_content' ) );
					$this->is_cache_buffer = true;
				}
				
				/**
				 * filter simple cache (if available)
				 * @since 1.4.1
				 */
				if( class_exists( 'SC_Advanced_Cache' ) || class_exists( 'SC_Object_Cache' ) || defined( 'SC_VERSION' ) ) {
					add_filter( 'sc_pre_cache_buffer', array( $this, 'filter_simple_cache_buffer' ) );
					$this->is_cache_buffer = true;
				}
				
				/**
				 * Filter WP Fastest Cache buffer (if available)
				 * @since 1.4.2
				 */
				
				if( function_exists( 'wpfc_clear_all_cache' ) || function_exists( 'wpfc_clear_post_cache_by_id' ) ) {
					//apply_filters('wpfc_buffer_callback_filter', $buffer, $extension);
					add_filter( 'wpfc_buffer_callback_filter', array( $this, 'filter_wpfc_buffer' ), 10, 2 );
					$this->is_cache_buffer = true;
				}
				
				/**
				 * Filter Hummingbird (Performance) cache buffer
				 * @since 1.4.4
				 */
				if( class_exists( 'WP_Hummingbird' ) || defined('WPHB_VERSION') ) {
					add_filter( 'wphb_cache_content', array( $this, 'filter_wphb_cache_content' ) );
					$this->is_cache_buffer = true;
				}
				 
				/**
				 * Filter WP Super Cache output buffer
				 * Also see @link https://odd.blog/wp-super-cache-developers/
				 * 
				 * @since 1.4.5
				 */
				 
				if( function_exists( 'wp_cache_phase2' ) || function_exists( 'wpsc_init' ) || defined( 'WPCACHEHOME' ) !== false ) {
					add_filter( 'wpsupercache_buffer', array( $this, 'filter_wpsc_output_buffer' ) );
					$this->is_cache_buffer = true;
				}
				
				
				/**
				 * Force usage of output buffer, even if there might be some caching plugin around
				 * NOTE: Use at own risk!
				 * @since 1.4.6
				 */
				 
				if( defined( '_UI_SSL_ENFORCER_FORCE_BUFFER' ) && _UI_SSL_ENFORCE_FORCE_BUFFER !== false ) {
					$this->is_cache_buffer = false;
				}
				 
				
				/**
				 * No caching tool found, but the output buffer cache is enabled?
				 * NOTE: Preparation for 2.0
				 * @hook ui_ssl_enforcer_output_buffer	Filter hook for the future output buffer / cache for UI SSL Enforcer
				 * @since 1.4.3
				 */
				 
				if( empty( $this->is_cache_buffer ) ) {
					
					if( defined( '_UI_SSL_ENFORCER_OUTPUT_BUFFER' ) && _UI_SSL_ENFORCER_USE_OUTPUT_BUFFER !== false ) {
						add_filter( 'ui_ssl_enforcer_output_buffer', array( $this, 'filter_output_buffer' ) );
					}
				}
				
				
				/**
				 * Filter widget contents, too
				 */
				$widget_filters = apply_filters( self::pluginPrefix . 'get_widget_filters', array(
					'widget_text',
					'widget_text_content', // custom text widget
					
					'widget_custom_html_content', // custom html widget
				) );
				
				if( !empty( $widget_filters ) && is_array( $widget_filters ) ) {
					foreach( $widget_filters as $strFilterHook ) {
						// first call ..
						add_filter( $strFilterHook, array( $this, 'replace_all_urls' ), 10, 1 );
						
						// ... last call
						add_filter( $strFilterHook, array( $this, 'replace_all_urls' ), 1000, 1 );
						
					}
				}
				//add_filter( 'widget_text', array( $this, 'replace_content_urls' ), 1000, 1 );
				
				
				/**
				 * Custom data filter
				 */
				add_filter ('_ui_ssl_enforcer_filter_content', array( $this, 'replace_all_urls' ), 1000, 1 );
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
					/**
					 * Optionally enable HSTS via the @constant _UI_SSL_ENFORCER_USE_HSTS
					 * @since 1.5.2
					 * NOTE: Replace the boolean within the constant with an integer to control the 'max-age' attribute
					 */
					
					if( defined( '_UI_SSL_ENFORCER_USE_HSTS' ) ) {
						$use_hsts = _UI_SSL_ENFORCER_USE_HSTS;
						$hsts_max_age = 31536000;
						
						if( !empty( $use_hsts ) ) {
							if( is_int( $use_hsts ) ) {
								$hsts_max_age = $use_hsts;
							}
					
							header( 'Strict-Transport-Security: max-age=' . $hsts_max_age );
						}
					}
					
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
			
			if ( isset( $cache[ $url ] ) ) {
				return $cache[ $url ];
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
		 * Filter our very own output buffer
		 * 
		 * @since 1.4.3
		 */

		function filter_output_buffer( $buffer = '' ) {
			$return = $buffer;
			
			if( $this->_has_http( $return ) != false ) {
				$return = $this->filter_hyper_cache_content( $return );
			}
			
			return $return;
		}

		/**
		 * Filter WP Super Cache output buffer
		 * @since 1.4.5
		 */
		function filter_wpsc_output_buffer( $buffer = '' ) {
			$return = $buffer;
			
			if( $this->_has_http( $return ) != false ) {
				$return = $this->filter_hyper_cache_content( $return );
			}
			
			return $return;
		}

		/**
		 * Filter Hummingburd Performance cache content
		 * 
		 * @since 1.4.4
		 */
		function filter_wphb_cache_content( $content = '' ) {
			$return = $content;
			
			if( $this->_has_http( $return ) != false ) {
				$return = $this->filter_hyper_cache_content( $return );
			}
			
			return $return;
		}


		/**
		 * Filter WP Fastest Cache content buffer
		 * 
		 * @since 1.4.2
		 */

		function filter_wpfc_buffer( $buffer = '', $extension = '' ) {
			$return = $buffer;
			
			if( $this->_has_http( $return ) != false ) {
				$return = $this->filter_hyper_cache_content( $return );
			}
			
			return $return;
		}

		/**
		 * Filter simple cache content buffer before output (or saving). It's essentially identical to @method filter_hyper_cache_content
		 * @since 1.4.1
		 */
		function filter_simple_cache_buffer( $buffer = '' ) {
			$return = $buffer;
			
			if( !empty( $return ) ) {
				$return = $this->filter_hyper_cache_content( $return );
			}
			
			return $return;
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
			
			/**
			 * Fix for canonical URLs (they should be ABSOLUTE), esp. Yoast SEO
			 * eg. <link rel="canonical" href="//www.westernsattel.de/" />
			 */
			if( stripos( $return, '<link rel="canonical"' ) !== false ) {
				$return = $this->fix_canonical_url( $return );
			}
			
			
			return $return;
		}
		
		/**
		 * Fix issues with the canonical URL in the HEAD
		 * @since 1.5.3
		 */
		
		function fix_canonical_url( $content = '' ) {
			$return = $content;
			
			if( strpos( $return, '<link rel="canonical" href="//' ) !== false ) {
				$prot = 'http';
				
				if( $this->is_https() ) {
					$prot .= 's';
				}
				
				$return = str_replace( '<link rel="canonical" href="//', '<link rel="canonical" href="' . $prot . '://', $return );
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
		 * Method adapted from Really Simple SSL
		 * 
		 * @since 1.5
		 */
		function init_url_list() {
			if( !isset( $this->http_urls ) || empty( $this->http_urls ) ) {
			
			
				$home = str_replace( 'https://', 'http://', get_option('home'));
				$home_no_www = str_replace('://www.', '://', $home);
				$home_yes_www = str_replace('://', '://www.', $home_no_www);

				//for the escaped version, we only replace the home_url, not it's www or non www counterpart, as it is most likely not used
				$escaped_home = str_replace("/", "\/", $home);

				$this->http_urls = array(
					$home_yes_www,
					$home_no_www,
					$escaped_home,
					"src='http://",
					'src="http://',
				);
			}
		}


		/**
		 * Method adopted from Really Simple SSL (@class rssl_mixed_content_fixer / class-mixed-content-fixer.php / @method replace_insecure_links ).
		 * 
		 * @since 1.5
		 */
		
		function replace_insecure_urls( $content = '' ) {
			$return = $content;
			

			//skip if file is xml
			if( substr( $return, 0, 5 ) != '<?xml' ) {
				$this->init_url_list();

				$search_array = apply_filters( '_ui_ssl_enforcer/replace_url_args', $this->http_urls );
				
				if( !empty( $search_array ) ) {
				
					
					$ssl_array = str_replace( array( 'http://', 'http:\/\/' ), array( 'https://', 'https:\/\/' ), $search_array );
					//now replace these links
					$return = str_replace($search_array, $ssl_array, $return);

					/**
					 * replace all http links except hyperlinks
					 * all tags with src attr are already fixed by str_replace
					 */
					 
					$pattern = array(
						'/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
						'/<link [^>]*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
						'/<meta property="og:image" [^>]*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
						'/<form [^>]*?action=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
					);

					$return = preg_replace( $pattern, 'https://', $return );

					/* handle multiple images in srcset */
					/**
					 * NOTE: Original code uses a separate callback:
					$return = preg_replace_callback( '/<img[^\>]*[^\>\S]+srcset=[\'"]\K((?:[^"\'\s,]+\s*(?:\s+\d+[wx])(?:,\s*)?)+)["\']/', array( $this, 'replace_src_set' ), $return );
					* 
					* ie. THIS one - because of PHP 5.2 backward compatiblity .. if you find one WP install that is still running PHP 5.2, you should just ... RUN FAAAAR AWAY! AND GO HIDING!
					* 
					* function replace_src_set($matches) {
						return str_replace("http://", "https://", $matches[0]);
						}
					*/
					

					$return = preg_replace_callback( '/<img[^\>]*[^\>\S]+srcset=[\'"]\K((?:[^"\'\s,]+\s*(?:\s+\d+[wx])(?:,\s*)?)+)["\']/', function( $matches ) {
						return str_replace( 'http://', 'https://', $matches[ 0 ] );
					}, $return );

					$return = str_replace( '<body', '<body data-uisslenforcer=1', $return );
				}
			}

			return apply_filters( '_ui_ssl_enforcer/replace_insecure_urls', $return );

		}


		/**
		 * DOM-aware version of @method replace_content_urls()
		 */
		function replace_html_urls( $content = '', $exclude = array() ) {
			$return = $content;
			
			//if( !empty( $return ) && strpos( $return, 'http://' ) !== false ) {
			if( $this->_has_http( $return ) ) {
				if( class_exists( 'simple_html_dom' ) ) {

					/**
					 * @see https://dev.w3.org/html5/html-author/
					 */
					
					$arrKnownRefTags = apply_filters( $this->add_plugin_prefix( 'get_ref_tags' ), array(
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
		
		function replace_text_urls( $content = '', $exclude = array() ) {
			$return = $content;
			
			if( $this->_has_http( $return ) ) {
				$return = str_replace( 'http://', 'https://', $return );
			}
			
			return $return;
		}

		/**
		 * Instead of output buffer parsing, we just fetch the most important filter contents ..
		 */

		function replace_content_urls( $content = '', $exclude = array() ) {
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
