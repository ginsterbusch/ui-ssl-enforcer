=== UI SSL Enforcer - Force SSL / HTTPS ===
Plugin Name: UI SSL Enforcer
Plugin Slug: ui-ssl-enforcer
Text Domain: ui-ssl-enforcer
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl.txt
Tags: redirect, force, ssl, https, force_ssl, force_ssl_admin, force_ssl_login, enforce_ssl, tls, mixed content
Contributors: usability.idealist
Requires PHP: 5.4
Requires At Least: 3.6
Tested Up To: 5.3
Stable Tag: 1.4.5

Simple, fast and effective &mdash; enforce HTTP URLs to HTTPS using WordPress filters and permanent redirects.

== Description ==

**A simple, fast and effective way to make sure that all HTTP URLs get rewritten / redirected to HTTPS** &mdash; including the WordPress upload folder and plugin url paths. Avoids "mixed content" error situations. Simply activate the plugin and you're done. ;-)

The plugin defines the following constants (if not already defined), then makes sure that all HTTP requests are rewritten / redirected to their HTTPS equivalent:

* FORCE_SSL
* FORCE_SSL_ADMIN
* FORCE_SSL_LOGIN

The plugin also hooks the WordPress 'upload_dir' and 'plugins_url' filters to make sure that all URLs match the appropriate protocol.

The plugin checks and honors the following proxy / load-balancing web server variables:

* HTTP_X_FORWARDED_PROTO
* HTTP_X_FORWARDED_SSL

There are no plugin settings &mdash; simply install and activate the plugin.

**Requirements**

Your web server must be configured with an SSL certificate and able to handle HTTPS request. ;-)

== Installation ==

= Automated Install =

1. Go to the wp-admin/ section of your website.
1. Select the *Plugins* menu item.
1. Select the *Add New* sub-menu item.
1. In the *Search* box, enter the plugin name.
1. Click the *Search Plugins* button.
1. Click the *Install Now* link for the plugin.
1. Click the *Activate Plugin* link.

= Semi-Automated Install =

1. Download the plugin ZIP file.
1. Go to the wp-admin/ section of your website.
1. Select the *Plugins* menu item.
1. Select the *Add New* sub-menu item.
1. Click on *Upload* link (just under the Install Plugins page title).
1. Click the *Browse...* button.
1. Navigate your local folders / directories and choose the ZIP file you downloaded previously.
1. Click on the *Install Now* button.
1. Click the *Activate Plugin* link.

== Frequently Asked Questions ==

<h3>Frequently Asked Questions</h3>

* None

== Other Notes ==

<h3>Additional Documentation</h3>

Available filter hooks:

<h4>Filter additional "known" URLs</h4>

**Filter Hook:** `_ui_ssl_enforcer_get_known_urls`

Fetches additional URLs to filter; expects an associative array which contains search pattern, replace pattern and subject. The first and the second are regular expressions (<a href="http://php.net/preg_replace">preg_replace</a>).

**Default values:**
```
array(
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
) 
```

<h4>Add more widget filters</h4>
**Filter hook:** ` _ui_ssl_enforcer_get_widget_filters`

Lets you to add more widget filters; expects an array with widget filter names.

**Defaults to:**
```
array( 'widget_text',
	'widget_text_content', // custom text widget
					
	'widget_custom_html_content', // custom html widget
);```

<h4>Add custom content filters</h4>
**Filter hook:** `_ui_ssl_enforcer_filter_content`

Lets you easily filter ANY kind of content; eg. to add it to the functions.php of your theme or in a specific plugin ..

Expects one parameter, which is the content / text to filter.

<h4>Filtering the output buffer</h4>

**Filter hook:** `ui_ssl_enforcer_output_buffer`

Future option to hook directly into the stand-alone output buffer / simple caching implementation (planned for v2.0).

== Screenshots ==

== Changelog ==

<h3>Repositories</h3>

* [GitHub](https://github.com/ginsterbusch/ui-ssl-enforcer)

<h3>Changelog / Release Notes</h3>

** Version 1.5.1 **

* Another try at fixing the home page SSL redirect (hopefully this works now reliable)

** Version 1.5 **

* Added missing home page SSL redirect
* Added a few filters using the enhanced filter naming scheme (similar to the one used by ACF)

** Version 1.4.6 **

* Added constants (`_UI_SSL_ENFORCER_OUTPUT_BUFFER` and `_UI_SSL_ENFORCER_FORCE_BUFFER`) for the future stand-alone output buffer option (coming in v2.0)

** Version 1.4.5 **

* Added support for the WP Super Cache output buffer (filter hook)

** Version 1.4.4 (2019-11-18) **

* Added support for Hummingbird Performance cache buffer (filter hook)

** Version 1.4.3 **

* Added support for future own simple cache / output buffer filtering

** Version 1.4.2 **

* Added support for WP Fastest Cache (filter hook)

** Version 1.4.1 (2019-11-13) **

* Added support for Simple Cache plugin (specifically its output buffer filter hook)

** Version 1.4 (2019-10-28) **

* Started initial work on integrating a simple (optional) admin screen
* Added new settings class to improve setting up of (required) SSL constants

** Version 1.3.3 (2018-10-17)**

* Workaround for crappy programming of others: check if simple_html_dom is already loaded.

**Version 1.3 (2018-05-23)**

* Added support for Hyper Cache (hooks into the `cache_buffer` filter hook, which fires right before the buffer is processed)
* Changed the call of the DOM-aware replace filter to be only enabled by constant (`_UI_SSL_ENFORCER_DOM_PARSER`), as it sometimes causes issues with inline script snippets

**Version 1.2 (2018-05-23)**

* Added several more filters, including an HTML DOM-aware URL replace filter with fallback option if something went wrong with including the required simple_html_dom class

**Version 1.1 (2018-05-21)**

* Added widgets filter hooks
* Added filter hook `_ui_ssl_enforcer_get_widget_filters` whichs enables you to add more widget filters to the above function
* Added custom filter hook `_ui_ssl_enforcer_filter_content` to easily filter ANY kind of content (syntax: expects one parameter, which is the content)

**Version 1.0 (2018-05-20)**

* Combined several features of pre-existing plugins into a new one
* Initial release.

== Upgrade Notice ==

**None**
