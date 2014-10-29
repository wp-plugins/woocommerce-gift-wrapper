=== Gift Wrap for Woocommerce ===
Contributors: littlepackage
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PB2CFX8H4V49L
Tags: ecommerce, e-commerce, woocommerce, gift, present, holidays
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Holidays are always coming! Offer your customers gift wrapping, per order, in the WooCommerce cart.

== Description ==

**Features:**

* Create a simple gift wrap option form on the cart page, or go all out with robust gift wrapping offerings
* Use your own copy/language on the cart page
* Set individual prices, descriptions, and images for wrapping types
* Show or hide wrap images in cart
* Get notice of the gift wrap message by email order notification and on the order page
* If you have suggestions for other features, please get in touch.

== Installation ==

= To install plugin =
1. Upload the entire "woocommerce_gift_wrap" folder to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Visit WooCommerce->Settings->Products tab to set your plugin preferences. Look for the Gift Wrap sub tab.
4. Follow the instructions there and review the settings.

= To remove plugin: =

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= It doesn't work =
Things to check:
1. Is the plugin activated?
2. Is WooCommerce activated and configured, and are all the theme files current (check WooCommerce->system status if unsure)
3. Does the your-theme-or-child-theme/woocommerce/cart/cart.php file include the code

`<?php do_action('woocommerce_cart_coupon'); ?>` or
`<?php do_action('woocommerce_after_cart'); ?>`

If not, your theme is missing a crucial hook(s) to the functioning of this plugin. Try using the other location for the "Where to Show Gift Wrapping" in the plugin settings.
4. Other problem? Let me know!

= Why isn't gift wrapping added when I click the button in the cart? =
Have you added a gift wrapping as a product? This plugin works by creating a product that virtually represents gift wrapping. It is up to you whether that product is visible in the catalog or not, and how fleshed-out you make the product description. But there needs to be a product, and it needs to be in a category whether or not you make more than one wrapping types.

= Why make more than one type of wrapping? =

Maybe you want to offer "Winter Holiday" wrapping and "Birthday" wrapping separately, or maybe you have other types of wrapping paper or boxes you use that may incur different prices or shipping rules. It's up to you whether or not you make more than one wrapping product.

= How can I style the appearance? =
I've added CSS tags to every aspect of this form so you can style away. If you want to streamline your site and speed page-loading, move the CSS to your style.css file and comment out the line in *woocommerce-gift-wrap.php* that reads: 

`add_action( 'wp_enqueue_scripts', array( &$this, 'gift_load_css_scripts' ));`

= I don't want more than one wrapping added to the cart! =

Yeah, that could be a problem, but rather than hard-code against that possibility I leave the settings to you, and for good reason. If you don't want more than one wrapping possible, make sure to set your wrapping product to "sold individually" under Product Data->Inventory in your Product editor. If you do this make sure your customer has a way to remove the gift wrapping from the cart on small screens, as sometimes responsive CSS designs remove the "Remove from Cart" button from the cart table for small screens.

== Screenshots ==

1. Screenshot of the settings page.

== Changelog ==
= 1.0 =
* Initial release

== Upgrade Notice ==
= 1.0 =
* Initial release