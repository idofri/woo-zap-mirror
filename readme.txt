=== Woo Zap Mirror ===
Contributors: idofri
Tags: WooCommerce, zap, index, mirror site, XML
Requires at least: 4.7
Requires PHP: 5.6
Tested up to: 5.2
Stable tag: 1.4.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Creates Zap Mirror site for WooCommerce.

== Description ==

= About the plugin =

The plugin creates a mirror site for [Zap](http://www.zap.co.il/) which allows it to better index your WooCommerce store.

== Installation ==

= Installation =
1. In your WordPress Dashboard go to "Plugins" -> "Add Plugin".
2. Search for "Woo Zap Mirror".
3. Install the plugin by pressing the "Install" button.
4. Activate the plugin by pressing the "Activate" button.

= Minimum Requirements =
* WordPress version 3.0 or greater.
* PHP version 5.3.0 or greater.
* MySQL version 5.0 or greater.

= Recommended Requirements =
* Latest WordPress version.
* PHP version 5.6 or greater.
* MySQL version 5.6 or greater.

== Screenshots ==

1. Plugin settings.

2. Product settings.

== Frequently Asked Questions ==

= What does the plugin do? =

The plugin will create a mirror site of your WooCommerce shop for Zap.

= How do I set product properties for the mirror site? =

Each product will have a fully customizable settings tab titled "Zap Settings".

= Can I exclude specific products from the mirror site? =

Yes. simply check the "Hide Product" checkbox @ the top of the settings tab.

= Can I exclude specific categories from the mirror site? =

Yes. simply check the categories you wish to hide on the plugin's settings page: WooCommerce => Settings => Zap Mirror Site => Hide Categories.

= What is the cost for the gateway plugin? =

This plugin is a FREE download.

= Does this this plugin work well with caching plugins? =

It is advisable to exclude and prevent the mirror site from being cached in any way. 

== Changelog ==

= 1.4.4 =
* Modified product urls in XML.

= 1.4.3 =
* Fixed potential bug: changed filter-hook priority for template overwrite.

= 1.4.2 =
* Fixed bug: removed PHP short-tags

= 1.4.1 =
* Added global default-attributes to products.

= 1.4 =
* More restructuring.

= 1.3.7 =
* Added new XML node-attribute.
* Minor restructuring.

= 1.3.6 =
* Raised chars limit for product name.

= 1.3.5 =
* Prevent WP Super Cache & W3 Total Cache from cache mirror-site requests.

= 1.3.4 =
* Fixed XML structure.

= 1.3.3 =
* Prevent other plugins from interfering with mirror-site template redirection logic.

= 1.3.2 =
* Fixed attribute escaping bug.
* Added new action hooks.
* Added Yoast SEO action hook.

= 1.3.1 =
* Added missing localization function.

= 1.3.0 =
* Complete restructuring of the plugin.
* Added action and filter hooks.

= 1.2.2 =
* Changed template redirection logic (again).

= 1.2.1 =
* Fixed minor permalinks bug.

= 1.2.0 =
* Changed template redirection logic.

= 1.1.9 =
* Fixed permalinks bug.

= 1.1.8 =
* Added WPML & Polylang compatibility.

= 1.1.7 =
* Apply XML fields requirements.

= 1.1.6 =
* Added WordPress 4.5 & WooCommerce 2.5.5 compatibility.
* Fixed permalink structure bug.

= 1.1.5 =
* Updated admin js.

= 1.1.4 =
* Added permalink compatibility.

= 1.1.3 =
* Fixed activation hook bug.

= 1.1.2 =
* Fixed activation hook bug.

= 1.1.1 =
* Fixed OOP bugs.
* Fixed javascript bugs.

= 1.1.0 =
* Added advanced product settings.
* New Customizable mirror site URL.
* Choose which categories to show/hide.

= 1.0.4 =
* Cleared html special chars for XML.

= 1.0.3 =
* Replaced SimpleXMLElement->addchild() @ products iterations.

= 1.0.2 =
* Fixed OOP syntax.
* Reveresed to template_redirect();

= 1.0.1 =
* Replaced template_redirect() with template_include().

= 1.0.0 =
* First Release.

== Upgrade Notice ==

= 1.1.6 =
* Added WordPress 4.5 & WooCommerce 2.5.5 compatibility.
* Fixed permalink structure bug.

= 1.1.4 =
* Added permalink compatibility.

= 1.1.0 =
* Added advanced product settings.
* New Customizable mirror site URL.
* Choose which categories to show/hide.

= 1.0.4 =
* Cleared html special chars for XML.

= 1.0.3 =
* Replaced SimpleXMLElement->addchild() @ products iterations.

= 1.0.2 =
* Fixed OOP syntax.
* Reveresed to template_redirect();

== Upgrade Notice ==

= 1.3 =
The upgrade requires some new configuration. The configuration from version 1.2.2 will not be fully preserved.