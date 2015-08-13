=== Woocommerce Gift Wrapper===
Contributors: littlepackage
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PB2CFX8H4V49L
Tags: ecommerce, e-commerce, woocommerce, woothemes, woo, gift, present
Requires at least: 3.8
Tested up to: 4.3
Stable tag: 1.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Holidays are always coming! Offer your customers gift wrapping, per order, in the WooCommerce cart.

== Description ==

**Features:**

* Create a simple gift wrap option form on the cart page, or go all out with robust gift wrapping offerings
* Use your own copy/language on the cart page
* Set individual prices, descriptions, and images for wrapping types
* Show or hide wrap images in cart
* Static or modal view of giftwrap options on cart page
* Get notice of the customer's intended gift wrap message by email order notification and on the order page
* Fully CSS-tagged for your customizing pleasure.
* If you have suggestions for other features, please get in touch.

== Installation ==

= To install plugin =

1. Upload the entire "woocommerce_gift_wrap" folder to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Visit WooCommerce->Settings->Products tab to set your plugin preferences. Look for the "Gift Wrapping" sub tab link.
4. Follow the instructions there and review the settings.

= To remove plugin: =

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= It doesn't work =
Things to check:

1. Is the plugin activated?
2. Are you using WooCommerce version 2.2.2 or newer? Time to upgrade!
3. Is WooCommerce activated and configured, and are all the theme files current (check WooCommerce->System Status if unsure)
4. Does the your-theme-or-child-theme/woocommerce/cart/cart.php file include the code

`<?php do_action('woocommerce_cart_coupon'); ?>` or
`<?php do_action('woocommerce_after_cart'); ?>`

If not, your theme is missing a crucial hook(s) to the functioning of this plugin. Try using the other location for the "Where to Show Gift Wrapping" in the plugin settings.

*Other problem?* Let me know!

= Why isn't gift wrapping added when I click the button in the cart? =
Have you added a gift wrapping as a product? This plugin works by creating a product that virtually represents gift wrapping. It is up to you whether that product is visible in the catalog or not, and how fleshed-out you make the product description. But there needs to be a product, and it needs to be in a category whether or not you make more than one wrapping types.

= Why make more than one type of wrapping? =

Maybe you want to offer "Winter Holiday" wrapping and "Birthday" wrapping separately, or maybe you have other types of wrapping paper or boxes you use that may incur different prices or shipping rules. It's up to you whether or not you make more than one wrapping product. You don't have to.

= How can I style the appearance? =
I've added CSS tags to every aspect of the cart forms so you can style away. If you want to streamline your site and speed page-loading, move the CSS to your style.css file and comment out the line in *woocommerce-gift-wrapper.php* that reads: 

`add_action( 'wp_enqueue_scripts', array( &$this, 'gift_load_css_scripts' ));`

= I don't want more than one wrapping added to the cart! =

Yeah, that could be a problem, but rather than hard-code against that possibility I leave the settings to you, and for good reason. If you don't want more than one wrapping possible, make sure to set your wrapping product to "sold individually" under Product Data->Inventory in your Product editor. If you do this make sure your customer has a way to remove the gift wrapping from the cart on small screens, as sometimes responsive CSS designs remove the "Remove from Cart" button from the cart table for small screens.

= I don't want to show gift wrapping in my catalog =

Visit your gift wrap product and set Catalog Visibility to "hidden" in the upper right corner near the blue update button. If you have more than one gift wrap product, do this for each one.

= Can I make the plugin's CSS/JavaScript load on the cart page only? =
Yes. It's a good idea to load scripts conditionally to keep page load times down. You only need the plugin scripts on the WooCommece cart page, so just add the following to your functions.php:

`function wcgiftwrapper_manage_scripts() {
	if ( !is_page( 'cart' ) ) {
		wp_dequeue_script( 'wcgiftwrap-js' );
		wp_dequeue_script( 'wcgiftwrap-css' );
	}
}
add_action( 'wp_enqueue_scripts', 'wcgiftwrapper_manage_scripts', 99 );`

== Screenshots ==

1. Screenshot of the settings page.

== Upgrade Notice ==
= 1.0 =
* Initial release
= 1.0.1 =
* Clarifications on settings page to help prevent users making the wrong category invisible
= 1.0.2 =
* Removed setting to hide gift wrap from catalog as it was potentially disruptive if category was set wrong
= 1.0.3 =
* Now compatible with versions of WC < 2.2.2
* Minor CSS fix
= 1.1.0 =
* Finished l10n install
* Added in copyright/fork notice for Gema75
* Modal view in cart
= 1.2.0 =
* Wordpress 4.3 ready
* Woocommerce version < 2.2.2 support removed
* Spanish and French translations

== Changelog ==
= 1.0 October 29 2014 =
* Initial release

= 1.0.1 November 6 2014 =
* Clarifications on settings page to help prevent users making the wrong category invisible; multi-select may need to be removed.

= 1.0.2 November 6 2014 =
* Removed setting to hide gift wrap from catalog as it was potentially disruptive if category was set wrong

= 1.0.3 December 2 2014 =
* Now compatible with versions of WC < 2.2.2
* Minor CSS fix

= 1.1.0 January 13 2014 =
* Finished l10n install
* Added in copyright/fork notice for Gema75
* Modal view in cart

= 1.2.0 August 12 2015 =
* Wordpress 4.3 ready
* Fixed JS and modal issues (modal was clipped when page was scrolled, JS now loaded in footer)
* JS dialog option when replacing wrapping already in cart
* User notes added below Product name in cart for customer reassurance
* Woocommerce version < 2.2.2 support removed
* Spanish and French translations