=== Woo Zap Mirror ===
Contributors: idofri
Tags: WooCommerce, zap, index, mirror, xml
Requires at least: 3.0
Tested up to: 4.6
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates Zap Mirror site for WooCommerce.

== Description ==

= About the plugin =

The plugin creates a mirror site for [Zap](http://www.zap.co.il/) which allows it to better index you WooCommerce store.

== Installation ==

= Installation =
1. In your WordPress Dashboard go to "Plugins" -> "Add Plugin".
2. Search for "Woo Zap Mirror".
3. Install the plugin by pressing the "Install" button.
4. Activate the plugin by pressing the "Activate" button.

= Minimum Requirements =
* WordPress version 3.0 or greater.
* PHP version 5.2.4 or greater.
* MySQL version 5.0 or greater.

= Recommended Requirements =
* Latest WordPress version.
* PHP version 5.6 or greater.
* MySQL version 5.6 or greater.

== Screenshots ==

1. Product configuration.

2. Customize mirror site URL address.

3. Hide specific categories.

== Frequently Asked Questions ==

= What does the plugin do? =

The plugin will create a mirror site of your woocommerce shop for Zap.

= How do I access the mirror site? =

By default the mirror site will be avaiable @ http://www.YOUR-DOMAIN.com/zap/

= Can I change the mirror site address? =

Yes. The mirror site URL address can be changed: WooCommerce => Zap Settings => General Settings.

= How do I set product properties for the mirror site? =

Each product will have a fully customizable settings box titled "Zap Settings".

= Why does some fields contain values as placeholders? =

Some fields are already being managed @ the product level, so to save you the effort I've placed their values as placeholders. 
You can then convert these placeholders to values by pressing on any non-alphnumeric key.

= Can I exclude specific products from the mirror site? =

Yes. simply check the "Hide Product" checkbox @ the top of the settings box.

= Can I exclude specific categories from the mirror site? =

Yes. simply check the categories you wish to hide on the plugin's settings page: WooCommerce => Zap Settings => Hide Categories.

= What is the cost for the gateway plugin? =

This plugin is a FREE download.

= Does this this plugin work well with caching plugins? =

It is advisable to exclude and prevent the mirror site from being cached in any way. 

== Changelog ==

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