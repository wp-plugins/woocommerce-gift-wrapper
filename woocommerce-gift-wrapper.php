<?php
/*
 * Plugin Name: Woocommerce Gift Wrapper
 * Plugin URI: http://www.little-package.com/woocommerce-gift-wrapper
 * Description: This plugin shows gift wrap options on the WooCommerce cart page, and adds gift wrapping to the order
 * Tags: woocommerce, e-commerce, ecommerce, gift, holidays, present
 * Version: 1.2
 * Author: Caroline Paquette
 * Text Domain: woocommerce-gift-wrapper
 * Domain path: /lang
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PB2CFX8H4V49L
 * 
 * Woocommerce Gift Wrapper
 * Copyright: (c) 2015 Caroline Paquette (email: cap@little-package.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Remember this plugin is free. If you have problems with it, please be
 * nice and contact me for help before leaving negative feedback!
 *
 * Woocommerce Gift Wrapper is forked from Woocommerce Gift Wrap by Gema75
 * Copyright: (c) 2014 Gema75 - http://codecanyon.net/user/Gema75
 * 
 * Original changes from Woocommerce Gift Wrapper include: OOP to avoid plugin clashes; removal of the option to
 * hide categories (to avoid unintentional, detrimental bulk database changes; use of the Woo API for the
 * settings page; complete restyling of the front-end view including a modal view to unclutter the cart view
 * and CSS tagging to allow easier customization; option for easy front end language adjustments and/or l18n;
 * addition of order notes regarding wrapping to order emails and order pages for admins; support for Woo > 2.2
 * menu sections, security fixes.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
if ( ! class_exists( 'WC_Gift_Wrapping' ) ) :

class WC_Gift_Wrapping {
 
	public function __construct() {

		define( 'GIFT_PLUGIN_VERSION', '1.2' );
		define( 'GIFT_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

		add_action( 'init',       											array( $this, 'lang' ) );
		add_action( 'wp_enqueue_scripts',       							array( $this, 'enqueue_scripts' ) );
		add_action( 'plugins_loaded',       								array( $this, 'hooks' ) );
		add_action( 'wp_loaded',											array( $this, 'add_giftwrap_to_order' ) );
		add_action( 'woocommerce_checkout_update_order_meta',   			array( $this, 'update_order_meta' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', 	array( $this, 'display_admin_order_meta'), 10, 1 );

		add_filter( 'woocommerce_get_sections_products',      	 			array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products',       			array( $this, 'settings' ), 10, 2);
		add_filter( 'woocommerce_cart_item_name',							array( $this, 'add_user_note_into_cart' ), 1, 3 );
		add_filter( 'woocommerce_email_order_meta_keys',					array( $this, 'order_meta_keys') );
		
    }

	/**
	 * l10n
	 **/
	public function lang() {

		load_plugin_textdomain( 'wc-gift-wrapper', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	}

	/**
	 * Hooks
	 **/
	public function hooks() {
		
		$giftwrap_display = get_option('giftwrap_display');

		if ( $giftwrap_display == 'after_coupon' ) {
			add_action( 'woocommerce_cart_coupon', array( &$this, 'add_gift_wrap_to_cart_page' ) );
		} else if ( $giftwrap_display == 'after_cart' ) {	
			add_action( 'woocommerce_after_cart', array( &$this, 'add_gift_wrap_to_cart_page' ) );
		}
		
	}

	/**
	 * Enqueue scripts
	 **/
	public function enqueue_scripts() {

 		$giftwrap_modal = get_option( 'giftwrap_modal' );
		if ( $giftwrap_modal == 'yes' ) {
			wp_enqueue_script( 'wcgiftwrap-js', GIFT_PLUGIN_URL .'/assets/js/wcgiftwrapper.js', 'jquery', null, true );	
			wp_enqueue_style( 'wcgiftwrap-css', GIFT_PLUGIN_URL .'/assets/css/wcgiftwrap_modal.css' );
		} else {
			wp_enqueue_style( 'wcgiftwrap-css', GIFT_PLUGIN_URL .'/assets/css/wcgiftwrap.css' );
		}

	}

 	/**
	 * Add settings SECTION under Woocommerce->Products
	 * @param array $sections
	 * @return array
	 **/
    public function add_section( $sections ) {
	
		$sections['wcgiftwrapper'] = __( 'Gift Wrapping', 'wc-gift-wrapper' );
		return $sections;

	}

	/**
	* Add settings to the section we created with add_section()
	* @param array Settings
	* @param string Current Section
	* @return array
	*/
	public function settings( $settings, $current_section ) {

 		if ( $current_section == 'wcgiftwrapper' ) {
				$category_args = array(
				'orderby' 			=> 'id',
				'order' 			=> 'ASC',
				'taxonomy' 			=> 'product_cat',
				'hide_empty' 		=> '0',
				'hierarchical' 		=> '1'
			);

			$gift_cats = array();
			$gifts_categories = ( $gifts_categories = get_categories( $category_args ) ) ? $gifts_categories : array();
			foreach ( $gifts_categories as $gifts_category )
				$gift_cats[ $gifts_category->term_id ] = $gifts_category->name;

			$settings_slider = array();
 
			$settings_slider[] = array( 
				'id' 				=> 'wcgiftwrapper',
				'name' 				=> __( 'Gift Wrapping Options', 'wc-gift-wrapper' ), 
				'type' 				=> 'title', 
				'desc' 				=> sprintf(__( '<strong>1.</strong> Start by <a href="%s" target="_blank">adding at least one product</a> called "Gift Wrapping" or something similar.<br /><strong>2.</strong> Create a unique product category for this/these gift wrapping product(s), and add them to this category.<br /><strong>3.</strong> Then consider the options below.', 'wc-gift-wrapper' ), wp_nonce_url(admin_url('post-new.php?post_type=product'),'add-product')),
			);
 
			$settings_slider[] = array(
				'id'       			=> 'giftwrap_display',
				'name'     			=> __( 'Where to Show Gift Wrapping', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'Choose where to show gift wrap options to the customer on the cart page.', 'wc-gift-wrapper' ),
				'type'     			=> 'select',
				'default'         	=> 'after_coupon',
				'options'     		=> array(
					'after_coupon'	=> __( 'Under Coupon Field in Cart', 'wc-gift-wrapper' ),
					'after_cart'	=> __( 'Above Totals Field in Cart', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[]	= array(
				'id'				=> 'giftwrap_category_id',
				'title'           	=> __( 'Gift Wrap Category', 'wc-gift-wrapper' ),
				'desc_tip'			=> __( 'Define the category which holds your gift wrap product(s).', 'wc-gift-wrapper' ),
				'type'            	=> 'select',
				'default'         	=> '',
				'options'         	=> $gift_cats,
				'css'             	=> 'width: 450px;',				
				'class'           	=> 'chosen_select',				
				'custom_attributes'	=> array(
					'data-placeholder' => __( 'Define a Category', 'wc-gift-wrapper' ),
				)
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_show_thumb',
				'name'     			=> __( 'Show Gift Wrap Thumbs in Cart', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'Should gift wrap product thumbnail images be visible in the cart?', 'wc-gift-wrapper' ),
				'type'     			=> 'select',
				'default'         	=> 'yes',
				'options'     		=> array(
					'yes'	=> __( 'Yes', 'wc-gift-wrapper' ),
					'no'	=> __( 'No', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;',
			);
 
			$settings_slider[] = array(
				'id'       			=> 'giftwrap_header',
				'name'     			=> __( 'Gift Wrap Cart Header', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text you would like to use to introduce your gift wrap offering.', 'wc-gift-wrapper' ),
				'type'     			=> 'text',
				'default'         	=> __( 'Add gift wrapping?', 'wc-gift-wrapper' ),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_modal',
				'name'     			=> __( 'Should Gift Wrap option open in pop-up?', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'If checked, there will be a link ("header") in the cart, which when clicked will open a window for customers to choose gift wrapping options. It can be styled and might be a nicer option for your site.', 'wc-gift-wrapper' ),
				'type'     			=> 'select',
				'default'         	=> 'yes',
				'options'     		=> array(
					'yes'				=> __( 'Yes', 'wc-gift-wrapper' ),
					'no'				=> __( 'No', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_details',
				'name'     			=> __( 'Gift Wrap Details', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text to give any details or conditions of your gift wrap offering.', 'wc-gift-wrapper' ),
				'type'     			=> 'textarea',
				'default'         	=> '',
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_text_label',
				'name'     			=> __( 'Gift Wrap Textarea Label', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text you would like to use for the textarea label', 'wc-gift-wrapper' ),
				'type'     			=> 'text',
 				'default'         	=> __( 'Add Gift Wrap Message:', 'wc-gift-wrapper' ),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[] = array(
				'id'       			=> 'giftwrap_button',
				'name'     			=> __( 'Gift Wrap Button Text', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text for the add to cart button', 'wc-gift-wrapper' ),
				'type'     			=> 'text',
 				'default'         	=> __( 'Add Gift Wrap to Order', 'wc-gift-wrapper' ),
				'css'      			=> 'min-width:300px;',
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
	 * Forked from Woocommerce Gift Wrap
	 * (c) Copyright 2014, Gema75 - http://codecanyon.net/user/Gema75
	 * @return void
	 **/
	public function add_giftwrap_to_order() {
	
		global $woocommerce;

		// chosen giftwrap product ID
		$giftwrap = isset( $_POST['giftwrapproduct'] ) && !empty( $_POST['giftwrapproduct'] ) ? (int)$_POST['giftwrapproduct'] : false;

		if ( $giftwrap > 0 && isset( $_POST['giftwrap_btn'] ) ) {

			$giftwrap_in_cart = self::is_gift_wrap_in_cart();
	
			if ( $giftwrap_in_cart == FALSE ) {

				$woocommerce->cart->add_to_cart( $giftwrap );
				$woocommerce->session->set( 'gift_wrap_set', $giftwrap );
				if ( isset( $_POST['wc_gift_wrap_notes'] ) ) {
					$woocommerce->session->set( 'gift_wrap_notes', $_POST['wc_gift_wrap_notes'] );
				}

			} else if ( $giftwrap_in_cart == TRUE ) { 

				$old_giftwrap = $woocommerce->session->get( 'gift_wrap_set' );
	 			$old_giftwrap = $woocommerce->cart->generate_cart_id( $old_giftwrap );
                unset( $woocommerce->cart->cart_contents[ $old_giftwrap ] );
				$woocommerce->cart->add_to_cart( $giftwrap );
				$woocommerce->session->set( 'gift_wrap_set', $giftwrap );
				if ( isset( $_POST['wc_gift_wrap_notes'] ) ) {
					$woocommerce->session->set( 'gift_wrap_notes', $_POST['wc_gift_wrap_notes'] );
				}	
			}		
		}

	}

	/**
	* Discover gift wrap products in cart
	* @return bool
	*/
	public static function is_gift_wrap_in_cart() {
	
		global $woocommerce;

		if ( count( $woocommerce->cart->get_cart() ) > 0 ) {
					
			foreach ( $woocommerce->cart->get_cart() as $key => $value ) {
				$product = $value['data'];
				$terms = get_the_terms( $product->id , 'product_cat' );
				
				if ( $terms ) {

					$giftwrap_category = get_option( 'giftwrap_category_id', true );	

					foreach ( $terms as $term ) {
						if ( $term->term_id == $giftwrap_category ) {
							$giftwrap_in_cart = TRUE;
						} else {
							$giftwrap_in_cart = FALSE;
						}
					}
				} 
			}
		} else {
			$giftwrap_in_cart = FALSE;				
		}
		return $giftwrap_in_cart;
		
	}

	/**
	 * Update the order meta with field value 
     * Forked from Woocommerce Gift Wrap
	 * (c) Copyright 2014, Gema75 - http://codecanyon.net/user/Gema75
	 * @param int Order ID
	 * @return void
	 **/
	public function update_order_meta( $order_id ) {

		global $woocommerce;
	
		if ( isset( $woocommerce->session->gift_wrap_notes ) ) {
			if ( $woocommerce->session->gift_wrap_notes !='' ) {
				update_post_meta( $order_id, '_gift_wrap_notes', sanitize_text_field( $woocommerce->session->gift_wrap_notes ) );
			}	
		}

	}

	/**
 	* Display user's note on the cart itemization
 	* @param array Order
	* @return void
 	*/
 	public function add_user_note_into_cart( $product_title, $cart_item, $cart_item_key ) {

		global $woocommerce;

		// giftwrap product ID
		$giftwrap = $woocommerce->session->get( 'gift_wrap_set' );
		// giftwrap note
		$gift_wrap_notes = $woocommerce->session->get( 'gift_wrap_notes' );

 		if ( $gift_wrap_notes !='' && $cart_item['product_id'] == $giftwrap ) {
				$product_title .= '<p class="giftwrap_note"><em>' . $gift_wrap_notes . '</em></p>';
  	 			return $product_title;
	       	} else {
				return $product_title;
   	 		}
	}

	/**
 	* Display field value on the order edit page
 	* @param array Order
	* @return void
 	*/
	public function display_admin_order_meta( $order ) {
	
		if ( get_post_meta( $order->id, '_gift_wrap_notes', true ) !== '' ) {
    		echo '<p><strong>'.__( 'Gift Wrap Note', 'wc-gift-wrapper' ).':</strong> ' . get_post_meta( $order->id, '_gift_wrap_notes', true ) . '</p>';
    	}
    	
	}

	/**
	* Add the field to order emails
 	* @param array Keys
	* @return array
 	**/ 
	public function order_meta_keys( $keys ) {

		$keys[__( 'Gift Wrap Note', 'wc-gift-wrapper' )] = '_gift_wrap_notes';
		return $keys;

	}

	/**
	 * Add gift wrap to order
	 * Forked from Woocommerce Gift Wrap
	 * (c) Copyright 2014, Gema75 - http://codecanyon.net/user/Gema75
	 * @return void
	 **/
	public function add_gift_wrap_to_cart_page() {
	
		global $woocommerce;

		$giftwrap_modal 			= get_option( 'giftwrap_modal' );
		$giftwrap_header 			= get_option( 'giftwrap_header' );
		$giftwrap_details 			= get_option( 'giftwrap_details' );
		$giftwrap_button 			= get_option( 'giftwrap_button' );
		$giftwrap_category_id 		= get_option( 'giftwrap_category_id', true );
		$giftwrap_category_slug 	= get_term( $giftwrap_category_id, 'product_cat' );

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
	
		$giftwrap_products = get_posts( $args );
		$giftwrap_show_thumb = get_option( 'giftwrap_show_thumb' );
		$giftwrap_text_label = get_option( 'giftwrap_text_label' );

		if ( count( $giftwrap_products ) > 0 ) {
			$giftwrap_display = get_option( 'giftwrap_display' );
			if ( $giftwrap_display == 'after_cart' ) echo '<hr>'; ?>
			
			<div id="wc-giftwrap" class="wc-giftwrap">
				<?php // if modal version
				if ( $giftwrap_modal == 'yes' ) { ?>
				<p class="giftwrap_header"><a href="#giftwrap_modal" data-toggle="modal" class="btn"><?php echo esc_attr( $giftwrap_header ); ?></a></p>
				<div id="giftwrap_modal" class="giftwrap_products modal" tabindex="-1">
					 <div class="modal-dialog">
 						<div class="modal-content">
 							<div class="modal-header">
 								<button class="button close giftwrap_cancel" type="button" data-dismiss="modal"><?php _e( 'Cancel', 'wc-gift-wrapper' ); ?></button>
							</div><!-- /.modal-header -->
							<div class="modal-body">
								<?php if ( $giftwrap_details != '' ) { ?><p class="giftwrap_details"><?php echo esc_attr( $giftwrap_details ); ?></p><?php } ?>
								<form method="post" action="" class="giftwrap_products">
									<ul class="giftwrap_ul">
										<?php if ( count( $giftwrap_products ) > 1 ) {	
											foreach ( $giftwrap_products as $giftwrap_product ) {
												$get_giftwrap_product = new WC_Product( $giftwrap_product->ID );
												$giftwrap_product_price = $get_giftwrap_product->get_price_html();
												$giftwrap_product_URL = $get_giftwrap_product->get_permalink();
												if ( $giftwrap_show_thumb == 'yes' ) {
													$product_image = wp_get_attachment_image( get_post_thumbnail_id( $giftwrap_product->ID ),'thumbnail');
													$product_image = '<div class="giftwrap_thumb"><a href="' . $giftwrap_product_URL . '">' . $product_image . '</a></div>';
													$show_thumb = ' show_thumb';
												} else {
													$show_thumb = '';
													$product_image = '';
												}
												$gift_wrap_set = $woocommerce->session->get( 'gift_wrap_set' );
												$radio_checked = isset( $gift_wrap_set ) && ( $giftwrap_product->ID == $gift_wrap_set ) && ( self::is_gift_wrap_in_cart() == TRUE ) ? 'checked' : '';
												echo '<li class="giftwrap_li' . $show_thumb . '"><input type="radio" '.$radio_checked.' name="giftwrapproduct" value="'.$giftwrap_product->ID.'"><label for="giftwrapproduct" class="giftwrap_desc"><span class="giftwrap_title"> ' . $giftwrap_product->post_title . '</span> ' . $giftwrap_product_price . '</label>' . $product_image . '</li>';
											}
										} else {
											foreach ( $giftwrap_products as $giftwrap_product ) {
												echo '<input type="hidden" name="giftwrapproduct" value="'.$giftwrap_product->ID.'">';
											}
										} ?>
									</ul>
									<div class="wc_giftwrap_notes_container">
										<label for="wc_gift_wrap_notes"><?php echo esc_attr($giftwrap_text_label);?></label>
										<textarea name="wc_gift_wrap_notes" id="wc_gift_wrap_notes" cols="30" rows="4" class="wc_giftwrap_notes"><?php if ( isset( $woocommerce->session->gift_wrap_notes) ) { echo stripslashes($woocommerce->session->gift_wrap_notes); } ?></textarea>	
									</div>
								</form>
							</div><!-- /.modal-body -->
							<div class="modal-footer">
								<button type="submit" class="button alt giftwrap_submit" name="giftwrap_btn"><?php echo esc_attr( $giftwrap_button ); ?></button> 
							</div><!-- /.modal-footer -->
	 					</div><!-- /.modal-content -->
 					</div><!-- /.modal-dialog -->
				</div><!-- /.modal -->

				<?php // non-modal version
				} else { ?>

				<h3 class="giftwrap_header"><?php echo esc_attr($giftwrap_header); ?></h3>
				<form method="post" action="" class="giftwrap_products">
				<?php if ( $giftwrap_details != '' ) { ?><p class="giftwrap_details"><?php echo esc_attr( $giftwrap_details ); ?></p><?php } ?>
					<ul class="giftwrap_ul">
					<?php if ( count( $giftwrap_products ) > 1 ) {	
						foreach ( $giftwrap_products as $giftwrap_product ) {
							$get_giftwrap_product = new WC_Product( $giftwrap_product->ID );
							$giftwrap_product_price = $get_giftwrap_product->get_price_html();
							$giftwrap_product_URL = $get_giftwrap_product->get_permalink();
							if ( $giftwrap_show_thumb == 'yes' ) {
								$product_image = wp_get_attachment_image( get_post_thumbnail_id( $giftwrap_product->ID ), 'thumbnail' );
								$product_image = '<div class="giftwrap_thumb"><a href="' . $giftwrap_product_URL . '">' . $product_image . '</a></div>';
								$show_thumb = ' show_thumb';

							} else {
								$show_thumb = '';
								$product_image = '';
							}
							$gift_wrap_set = $woocommerce->session->get( 'gift_wrap_set' );
							$radio_checked = isset( $gift_wrap_set ) && ( $giftwrap_product->ID == $gift_wrap_set ) && ( self::is_gift_wrap_in_cart() == TRUE ) ? 'checked' : '';
							echo '<li class="giftwrap_li' . $show_thumb . '"><input type="radio" '.$radio_checked.' name="giftwrapproduct" value="'.$giftwrap_product->ID.'"><span class="giftwrap_title"> ' . $giftwrap_product->post_title . '</span> ' . $giftwrap_product_price . $product_image . '</li>';
						}
					} else {
						foreach ( $giftwrap_products as $giftwrap_product ) {
							echo '<input type="hidden" name="giftwrapproduct" value="'.$giftwrap_product->ID.'">';
						}
					} ?>
					</ul>
					<div class="wc_giftwrap_notes_container">
						<label for="wc_gift_wrap_notes"><?php echo esc_attr( $giftwrap_text_label );?></label>
						<textarea name="wc_gift_wrap_notes" id="wc_gift_wrap_notes" cols="30" rows="4" class="wc_giftwrap_notes"><?php if ( isset( $woocommerce->session->gift_wrap_notes ) ) { echo stripslashes( $woocommerce->session->gift_wrap_notes ); } ?></textarea>
					</div>
					<input type="submit" class="button giftwrap_submit" name="giftwrap_btn" value="<?php echo esc_attr( $giftwrap_button ); ?>"> 
				</form>
				<?php } 

				$giftwrap_in_cart = self::is_gift_wrap_in_cart();
				if ( $giftwrap_in_cart == TRUE ) { ?>
					<script type="text/javascript">
					/* <![CDATA[ */
						jQuery('.giftwrap_submit').click( function() {
							if ( window.confirm( "<?php _e( 'Are you sure you want to replace the gift wrap in your cart?', 'wc-gift-wrapper' ); ?>" ) ) {
								return true;	
							}
							return false;
						});
					/* ]]> */
					</script>		
					<noscript></noscript>
				<?php } ?>

			</div>
			
		<?php 
		}
		
	} // End add_gift_wrap_to_cart_page()
	
}  // End class WC_Gift_Wrapping

endif; // End if ( class_exists() )

new WC_Gift_Wrapping();

?>