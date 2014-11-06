<?php
/*
 * Plugin Name: Woocommerce Gift Wrapper
 * Plugin URI: http://cap.little-package.com/web
 * Description: This plugin shows gift wrap options on the WooCommerce cart page, and adds gift wrapping to the order
 * Tags: woocommerce, e-commerce, ecommerce, gift, holidays, present
 * Version: 1.0.1
 * Author: Caroline Paquette
 * Author URI: http://cap.little-package.com/web
 * Text Domain: woocommerce-gift-wrapper
 * Domain path: /lang
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PB2CFX8H4V49L
 * 
 * If this plugin helps you and/or you and others enjoy it, consider
 * donating to my Paypal account (i.e. take me out for a drink!) It's 
 * the proper way to treat someone who does you a favor.
 *
 * Remember this plugin is free. If you have problems with it, please be
 * nice and contact me for help before leaving negative feedback!
 *
 * Copyright: (c) 2014 Caroline Paquette (email cap@little-package.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
if ( ! class_exists( 'WC_Gift_Wrapping' ) ) :

class WC_Gift_Wrapping {
 
	public function __construct() { 

		define( 'GIFT_PLUGIN_VERSION', '1.0' );
		define( 'GIFT_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

		add_action( 'plugins_loaded',       									array( $this, 'wcgiftwrapper_lang' ));
		add_action( 'plugins_loaded',       									array( $this, 'wcgiftwrapper_hooks' ));
		add_action( 'wp_enqueue_scripts',       								array( $this, 'gift_load_css_scripts' ));
		add_filter( 'woocommerce_get_sections_products',       					array( $this, 'wcgiftwrapper_add_section' ));
		add_filter( 'woocommerce_get_settings_products',       					array( $this, 'wcgiftwrapper_settings' ), 10, 2);
		add_action( 'init',       												array( $this, 'add_giftwrap_to_cart' ));
		add_action( 'init',       												array( $this, 'hide_gift_products' ));
		add_action( 'woocommerce_checkout_update_order_meta',   				array( $this, 'update_order_meta' ));
		add_action( 'woocommerce_admin_order_data_after_billing_address', 		array( $this, 'display_admin_order_meta'), 10, 1 );
		add_filter( 'woocommerce_email_order_meta_keys',						array( $this, 'order_meta_keys'));

    }

	/**
	 * l10n
	 **/
	public function wcgiftwrapper_lang() {

		load_plugin_textdomain( 'woocommerce-gift-wrapper', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	}

	/**
	 * hooks
	 **/
	public function wcgiftwrapper_hooks() {

		load_plugin_textdomain( 'wc-gift-wrap', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		$giftwrap_display = get_option('giftwrap_display');
		
		if ( $giftwrap_display == 'after_coupon' ) {
			add_action( 'woocommerce_cart_coupon', 		array( &$this, 'add_gift_wrap_to_order' ));
		} else if ( $giftwrap_display == 'after_cart' ) {
			add_action( 'woocommerce_after_cart',		array( &$this, 'add_gift_wrap_to_order' ));
		}

	}

	/**
	 * Enqueue frontend scripts/css
	 **/
	public function gift_load_css_scripts() {
	
		wp_enqueue_style( 'wcgiftwrap-css', GIFT_PLUGIN_URL .'/style.css' );

	}

 	/**
	 * Add settings section under Woocommerce->Products
	 **/
    public function wcgiftwrapper_add_section( $sections ) {
	
		$sections['wcgiftwrapper'] = __( 'Gift Wrapping', 'wc-gift-wrap' );
		return $sections;

	}

	/**
	* Add settings to the specific section we created before
	*/
	public function wcgiftwrapper_settings( $settings, $current_section ) {

 		if ( $current_section == 'wcgiftwrapper' ) {
				$args_woocommerce_categories = array(
				'orderby' => 'id',
				'order' => 'ASC',
				'taxonomy' => 'product_cat',
				'hide_empty' => '0',
				'hierarchical' => '1'
			);

			$gift_cats = array();
			$gifts_woocommerce_categories = ( $gifts_woocommerce_categories = get_categories( $args_woocommerce_categories ) ) ? $gifts_woocommerce_categories : array();
			foreach( $gifts_woocommerce_categories as $gifts_woocommerce_category )
				$gift_cats[ $gifts_woocommerce_category->term_id ] = $gifts_woocommerce_category->name;

			$settings_slider = array();
 
			$settings_slider[] = array( 
				'id' 				=> 'wcgiftwrapper',
				'name' 				=> __( 'Gift Wrapping Options', 'text-domain' ), 
				'type' 				=> 'title', 
				'desc' 				=> sprintf(__( '<strong>1.</strong> Start by <a href="%s" target="_blank">adding at least one product</a> called "Gift Wrapping" or something similar.<br /><strong>2.</strong> Create a unique product category for this/these gift wrapping product(s), and add them to this category.<br /><strong>3.</strong> Then consider the options below.', 'text-domain' ), wp_nonce_url(admin_url('post-new.php?post_type=product'),'add-product')),
			);
 
			$settings_slider[] = array(
				'id'       			=> 'giftwrap_display',
				'name'     			=> __( 'Where to Show Gift Wrapping', 'wc-gift-wrap' ),
				'desc'     			=> __( '', 'wc-gift-wrap' ),
				'desc_tip' 			=> __( '', 'wc-gift-wrap' ),
				'type'     			=> 'select',
				'options'     		=> array(
					'after_coupon'	=> __( 'Under Coupon Field in Cart', 'wc-gift-wrap' ),
					'after_cart'	=> __( 'Above Totals Field in Cart', 'wc-gift-wrap' ),
				),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[]	= array(
				'id'			=> 'giftwrap_category_id',
				'title'           => __( 'Gift Wrap Category', 'wc-gift-wrap' ),
				'type'            => 'select',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'desc_tip'			=> __( 'Be careful with this setting, as if your Gift Wrap Category is set incorrectly, you can accidentally make the wrong category invisible (or visible) with the next setting.', 'wc-gift-wrap' ),
				'default'         => '',
				'options'         => $gift_cats,
				'custom_attributes'      => array(
					'data-placeholder' => __( 'Define a Category', 'wc-gift-wrap' ),
				)
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_show_products',
				'name'     			=> __( 'Show Gift Wraps in Catalog', 'wc-gift-wrap' ),
				'desc'     			=> __( 'Should the gift wrap products be visible on shop pages? If no, gift wrap options will only show up in the cart. ', 'wc-gift-wrap' ),
				'desc_tip' 			=> __( 'Be careful with this setting, as if your Gift Wrap Category is set incorrectly, you can accidentally make the wrong category invisible (or visible). Of course this toggle can be used to set it back.', 'wc-gift-wrap' ),
				'type'     			=> 'select',
				'default'         => 'yes',
				'options'     		=> array(
					'yes'	=> __( 'Yes - Visible', 'wc-gift-wrap' ),
					'no'	=> __( 'No - Hidden', 'wc-gift-wrap' ),
				),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_show_thumb',
				'name'     			=> __( 'Show Gift Wrap Thumbs in Cart', 'wc-gift-wrap' ),
				'desc'     			=> __( '', 'wc-gift-wrap' ),
				'desc_tip' 			=> __( 'Should gift wrap product thumbnail images be visible in the cart?', 'wc-gift-wrap' ),
				'type'     			=> 'select',
				'default'         => 'yes',
				'options'     		=> array(
					'yes'	=> __( 'Yes', 'wc-gift-wrap' ),
					'no'	=> __( 'No', 'wc-gift-wrap' ),
				),
				'css'      			=> 'min-width:300px;',
			);
 
			$settings_slider[] = array(
				'id'       			=> 'giftwrap_header',
				'name'     			=> __( 'Gift Wrap Cart Header', 'wc-gift-wrap' ),
				'desc'     			=> '',
				'desc_tip' 			=> __( 'The text you would like to use to describe your gift wrap offering.', 'wc-gift-wrap' ),
				'type'     			=> 'textarea',
				'css'      => 'width:50%; height: 75px;',
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_text_label',
				'name'     			=> __( 'Gift Wrap Textarea Label', 'wc-gift-wrap' ),
				'desc'     			=> '',
				'desc_tip' 			=> __( 'The text you would like to use for the textarea label', 'wc-gift-wrap' ),
				'type'     			=> 'text',
 				'default'         	=> 'Add Gift Wrap Message:',
			);
		
			$settings_slider[] = array(
				'id' => 'wcgiftwrapper',
				'type' => 'sectionend',
			);
 
		return $settings_slider;
	
		} else {
			return $settings;
		}
	
	}
		
	/**
	 * Add gift wrapping to cart
	 **/
	public function add_giftwrap_to_cart() {
		global $woocommerce;

		$giftwrap = isset( $_POST['giftwrapproduct'] ) && !empty( $_POST['giftwrapproduct'] ) ? (int)$_POST['giftwrapproduct'] : false;

		if( $giftwrap && isset( $_POST['giftwrap_btn'] ) ) {

			$giftwrap_giftcategory  = get_option('giftwrap_category_id',true); // Get saved giftwrap category ID			
			$giftwrap_found = false; // Add giftwrap item to basket
			
			if( $giftwrap > 0 ) { // Add to session
				$woocommerce->session->ok_gift = $giftwrap;

				if( sizeof( $woocommerce->cart->get_cart() ) > 0) { // Check if giftwrap product already in cart

					foreach( $woocommerce->cart->get_cart() as $cart_item_key=>$values) {
						$_product = $values['data'];
						$terms = get_the_terms($_product->id , 'product_cat' );  // Find all product categories in cart
						if( $terms ) {
							foreach ($terms as $term) {
								if($term->term_id == $giftwrap_giftcategory) {
									$giftwrap_found = true;
								}
							}
						}
				
						if($giftwrap_found) { // Show message to user - You already have a giftwrap in cart 
							wc_add_notice( 'There is already wrapping in your cart. Remove it first if you need to make changes.','notice' );
						}

						if( isset( $_POST['wc_giftwrap_notes'] ) ) { //set gift wrap notes
							$woocommerce->session->giftwrap_notes = $_POST['wc_giftwrap_notes'];
						}
					}
					
					if( !$giftwrap_found ) { // if  giftwrap product not found, add it
						$woocommerce->cart->add_to_cart($giftwrap);
						if( isset( $_POST['wc_giftwrap_notes'] ) ) { //set gift wrap notes
							$woocommerce->session->giftwrap_notes = $_POST['wc_giftwrap_notes'];
						}
					}
				
				} else {

					$woocommerce->cart->add_to_cart($giftwrap); // if no giftwrap products in cart, add it
			
					if( isset( $_POST['wc_giftwrap_notes'] ) ) { //set gift wrap notes
						$woocommerce->session->giftwrap_notes = $_POST['wc_giftwrap_notes'];

					}	
				}
			}
		}
	}

	/**
	 * Update the order meta with field value
	 **/
	public function update_order_meta( $order_id ) {

		global $woocommerce;
	
		if( isset( $woocommerce->session->giftwrap_notes) ) {
			if( $woocommerce->session->giftwrap_notes !='' ) {
				update_post_meta( $order_id, '_giftwrap_notes', sanitize_text_field( $woocommerce->session->giftwrap_notes) );
			}	
		}

	}

	/**
 	* Display field value on the order edit page
 	*/
 
	public function display_admin_order_meta($order){
	
		if ( get_post_meta( $order->id, '_giftwrap_notes', true ) !== '' ) {
    		echo '<p><strong>'.__( 'Gift Wrap Note', 'wc-gift-wrap' ).':</strong> ' . get_post_meta( $order->id, '_giftwrap_notes', true ) . '</p>';
    	}
    	
	}

	/**
	* Add the field to order emails
 	**/ 
	public function order_meta_keys( $keys ) {
		$keys[__( 'Gift Wrap Note', 'wc-gift-wrap' )] = '_giftwrap_notes';
		return $keys;
	}

	/**
	 * Add gift wrap to order
	 **/
	public function add_gift_wrap_to_order() {
	
		global $woocommerce;

		$giftwrap_header =  get_option('giftwrap_header');
		$giftwrap_category_id =  get_option('giftwrap_category_id', true);
		$giftwrap_category_slug = get_term( $giftwrap_category_id , 'product_cat' );

		$args = array(
			'posts_per_page' => '-1',
			'post_count' => -1,
			'post_type' =>'product',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field' => 'slug',
					'terms' =>  $giftwrap_category_slug->slug
				
				)),
		);
	
		$giftwrap_products = get_posts($args);
		
		$giftwrap_show_thumb = get_option('giftwrap_show_thumb');
		$giftwrap_text_label = get_option('giftwrap_text_label');

		if ( count( $giftwrap_products ) > 0 ) {
			$giftwrap_display = get_option('giftwrap_display');
			if ( $giftwrap_display == 'after_cart' ) echo '<hr>'; ?>
			
			<div id="wc-giftwrap">
				<p class="giftwrap_header"><?php echo esc_attr($giftwrap_header); ?></p>
				<form method="post" action="">
					<ul>
		
					<?php if ( count( $giftwrap_products ) > 1 ) {	
						foreach( $giftwrap_products as $giftwrap_product ) {
							$get_giftwrap_product = new WC_Product( $giftwrap_product->ID );
							$giftwrap_product_price  = $get_giftwrap_product->get_price_html();
							$giftwrap_product_URL = $get_giftwrap_product->get_permalink();
							if ( $giftwrap_show_thumb == 'yes' ) {
								$product_image= wp_get_attachment_image(get_post_thumbnail_id($giftwrap_product->ID),'thumbnail');
								$product_image = '<div class="giftwrap_thumb"><a href="' . $giftwrap_product_URL . '">' . $product_image . '</a></div>';
							}
							echo '<li><input type="radio" class="giftwrap_li" name="giftwrapproduct" value="'.$giftwrap_product->ID.'"><span class="giftwrap_title"> ' . $giftwrap_product->post_title . '</span> ' . $giftwrap_product_price . $product_image . '</li>';
						}
					} else {
						foreach( $giftwrap_products as $giftwrap_product ) {
							echo '<input type="hidden" name="giftwrapproduct" value="'.$giftwrap_product->ID.'">';
						}
					} ?>
		
					</ul>
				
					<div class="wc_giftwrap_notes_container">
						<label for="wc_giftwrap_notes"><?php echo esc_attr($giftwrap_text_label);?></label>
						<textarea name="wc_giftwrap_notes" id="wc_giftwrap_notes" cols="30" rows="4" ><?php if( isset( $woocommerce->session->giftwrap_notes) ) { echo stripslashes($woocommerce->session->giftwrap_notes); } ?></textarea>
					</div>
		
					<input type="submit" class="button" name="giftwrap_btn" value="<?php _e('Add Gift Wrap to Order', 'wc-giftwrap');?>"> 
					
				</form>
			</div>
			
		<?php 
		}
	}
	
	/**
	 * Hide Gift Wrapping Products from Catalog
	 **/
	public function hide_gift_products() {
		global $post;
			
		$giftwrap_show_or_not = get_option('giftwrap_show_products'); // no
		$giftwrap_category_id  = get_option('giftwrap_category_id', true);
		
		if ( isset( $giftwrap_category_id ) ) {
			$giftwrap_category_slug = get_term( $giftwrap_category_id , 'product_cat' ); // Get the slug of the selected category holding the gift wrap products
			$get_all_giftproducts_args = array(
				'posts_per_page' => '-1',
				'post_count' => -1,
				'post_type' =>'product',
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'slug',
						'terms' =>  $giftwrap_category_slug->slug
					),
				),
			);

			$get_all_giftproducts_query = new WP_Query( $get_all_giftproducts_args );

			while ( $get_all_giftproducts_query->have_posts() ) { // Loop all products in the category selected and make them shown/hidden
				$get_all_giftproducts_query->the_post();
			
				if ( $giftwrap_show_or_not == 'yes' ) {
					update_post_meta( $post->ID, '_visibility', 'catalog' );
				} else if ( $giftwrap_show_or_not == 'no' ){
					update_post_meta( $post->ID, '_visibility', 'hidden' );
				}
			}
		}
	}

}  // End class WC_Gift_Wrapping

endif;

new WC_Gift_Wrapping();

?>