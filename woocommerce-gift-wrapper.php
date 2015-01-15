<?php
/*
 * Plugin Name: Woocommerce Gift Wrapper
 * Plugin URI: http://cap.little-package.com/web
 * Description: This plugin shows gift wrap options on the WooCommerce cart page, and adds gift wrapping to the order
 * Tags: woocommerce, e-commerce, ecommerce, gift, holidays, present
 * Version: 1.1.0
 * Author: Caroline Paquette
 * Text Domain: woocommerce-gift-wrapper
 * Domain path: /lang
 * Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PB2CFX8H4V49L
 * 
 * Woocommerce Gift Wrapper
 * Copyright: (c) 2014 Caroline Paquette (email: littlepackage@gmail.com)
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
 * Changes include: OOP to avoid plugin clashes; removal of the option to hide categories (to avoid unintentional,
 * detrimental bulk database changes; use of the Woo API for the settings page; complete restyling of the front-end
 * view including a modal view to simplify the cart view and CSS tagging to allow easier customization; option for
 * easy front end language adjustments and/or l18n; addition of order notes regarding wrapping to order emails
 * and order pages for admins; security fixes.
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
if ( ! class_exists( 'WC_Gift_Wrapping' ) ) :

class WC_Gift_Wrapping {
 
	public function __construct() { 

		define( 'GIFT_PLUGIN_VERSION', '1.1.0' );
		define( 'GIFT_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

		add_action( 'init',       												array( $this, 'wcgiftwrapper_lang' ) );
		add_action( 'plugins_loaded',       									array( $this, 'wcgiftwrapper_hooks' ) );
		add_action( 'wp_enqueue_scripts',       								array( $this, 'wcgiftwrapper_scripts' ) );

		if ( version_compare( self::get_woo_version_number(), '2.2.2' ) >= 0  ) {

			// greater than or equal to v2.2;
			add_filter( 'woocommerce_get_sections_products',       			array( $this, 'wcgiftwrapper_add_section' ) );
			add_filter( 'woocommerce_get_settings_products',       			array( $this, 'wcgiftwrapper_settings' ), 10, 2);

		} else {

			// less than v2.2.2
			$this->tab_name = 'wc-gift-wrapper';
			add_filter( 'woocommerce_settings_tabs_array', 					array( $this, 'add_settings_tab' ), 101 );
			add_action( 'woocommerce_settings_tabs_' . $this->tab_name, 		array( $this, 'create_settings_page' ) ); 
			add_action( 'woocommerce_update_options_' . $this->tab_name, 		array( $this, 'save_settings_page' ) );

		}

		add_action( 'init',       												array( $this, 'add_giftwrap_to_cart' ) );
		add_action( 'woocommerce_checkout_update_order_meta',   				array( $this, 'update_order_meta' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', 	array( $this, 'display_admin_order_meta'), 10, 1 );
		add_filter( 'woocommerce_email_order_meta_keys',						array( $this, 'order_meta_keys') );

    }

	/**
	 * l10n
	 **/
	public function wcgiftwrapper_lang() {

		load_plugin_textdomain( 'wc-gift-wrapper', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	}

	/**
	 *
	 * WC version
	 * Thank you Mike Donaghy - http://wpbackoffice.com/get-current-woocommerce-version-number/
	 * 
	 **/
	public function get_woo_version_number() {

        // If get_plugins() isn't available, require it
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
        	// Create the plugins folder and file variables
			$plugin_folder = get_plugins( '/' . 'woocommerce' );
			$plugin_file = 'woocommerce.php';
	
			// If the plugin version number is set, return it 
			if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
				return $plugin_folder[$plugin_file]['Version'];

		} else {
			// Otherwise return null
			return NULL;
		}

	}

	/**
	 * Hooks
	 **/
	public function wcgiftwrapper_hooks() {

		$giftwrap_display = get_option('giftwrap_display');
		
		if ( $giftwrap_display == 'after_coupon' ) {
			add_action( 'woocommerce_cart_coupon', 							array( &$this, 'add_gift_wrap_to_order' ) );
		} else if ( $giftwrap_display == 'after_cart' ) {
			add_action( 'woocommerce_after_cart',								array( &$this, 'add_gift_wrap_to_order' ) );
		}

	}

	/**
	 * Enqueue scripts
	 **/
	public function wcgiftwrapper_scripts() {

 		$giftwrap_modal = get_option('giftwrap_modal');
		if ( $giftwrap_modal == 'yes' ) {
			wp_enqueue_script( 'wcgiftwrap-js', GIFT_PLUGIN_URL .'/assets/js/wcgiftwrapper.js' );	
			wp_enqueue_style( 'wcgiftwrap-css', GIFT_PLUGIN_URL .'/assets/css/wcgiftwrap_modal.css' );
		} else {
			wp_enqueue_style( 'wcgiftwrap-css', GIFT_PLUGIN_URL .'/assets/css/wcgiftwrap.css' );
		}

	}

	/**
	 *  For Woo < 2.2.2 - Add a tab to the settings page	
	 */
	public function add_settings_tab($tabs) {

		$tabs[$this->tab_name] = __( 'Gift Wrapper', 'wc-gift-wrapper' );
		return $tabs;

	}

	/**
	 * For Woo < 2.2.2 - Include and display the settings page.
	 */
	public function create_settings_page() {

		$wcgiftwrapper_settings = $this->settings_array();
		woocommerce_admin_fields( $wcgiftwrapper_settings );		

	}

	/**
	 * For Woo < 2.2.2 - Save the settings page.
	 */
	public function save_settings_page() {

		$wcgiftwrapper_settings = $this->settings_array();

		if ( is_admin() ) {
			if ( isset( $wcgiftwrapper_settings ) ) {
				foreach ( $wcgiftwrapper_settings as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						woocommerce_update_options( $wcgiftwrapper_settings );
					}
				}
			}
		} else {
			// NULL
		}

	}


	/**
	 * For Woo < 2.2.2 - Settings array
	 */
	public function settings_array() {

		$category_args = array(
		'orderby' => 'id',
		'order' => 'ASC',
		'taxonomy' => 'product_cat',
		'hide_empty' => '0',
		'hierarchical' => '1'
			);

		$gift_cats = array();
		$gifts_categories = ( $gifts_categories = get_categories( $category_args ) ) ? $gifts_categories : array();
		foreach( $gifts_categories as $gifts_category )
			$gift_cats[ $gifts_category->term_id ] = $gifts_category->name;
	
		return array(

			array( 
				'id' 				=> 'wcgiftwrapper',
				'title' 			=> __( 'Gift Wrapping Options', 'wc-gift-wrapper' ), 
				'type' 				=> 'title', 
				'desc' 				=> sprintf(__( '<strong>1.</strong> Start by <a href="%s" target="_blank">adding at least one product</a> called "Gift Wrapping" or something similar.<br /><strong>2.</strong> Create a unique product category for this/these gift wrapping product(s), and add them to this category.<br /><strong>3.</strong> Then consider the options below.', 'text-domain' ), wp_nonce_url(admin_url('post-new.php?post_type=product'),'add-product'))
			),

			array(
				'id'       			=> 'giftwrap_display',
				'title'     		=> __( 'Where to Show Gift Wrapping', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'Choose where to show gift wrap options to the customer on the cart page.', 'wc-gift-wrapper' ),
				'type'     			=> 'select',
				'options'     		=> array(
					'after_coupon'	=> __( 'Under Coupon Field in Cart', 'wc-gift-wrapper' ),
					'after_cart'	=> __( 'Above Totals Field in Cart', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;'
			),

			array(
				'id'				=> 'giftwrap_category_id',
				'title'           	=> __( 'Gift Wrap Category', 'wc-gift-wrapper' ),
				'type'            	=> 'select',
				'class'           	=> 'chosen_select',
				'css'             	=> 'width: 450px;',
				'desc_tip'			=> __( 'Define the category which holds your gift wrap product(s).', 'wc-gift-wrapper' ),
				'default'         	=> '',
				'options'         	=> $gift_cats,
				'custom_attributes'	=> array(
					'data-placeholder' 		=> __( 'Define a Category', 'wc-gift-wrapper' ),
				)
			),

			array(
				'id'       			=> 'giftwrap_show_thumb',
				'title'     		=> __( 'Show Gift Wrap Thumbs in Cart', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'Should gift wrap product thumbnail images be visible in the cart?', 'wc-gift-wrapper' ),
				'type'     			=> 'select',
				'default'         	=> 'yes',
				'options'     		=> array(
					'yes'				=> __( 'Yes', 'wc-gift-wrapper' ),
					'no'				=> __( 'No', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;',
			),
 
			array(
				'id'       			=> 'giftwrap_header',
				'title'     		=> __( 'Gift Wrap Cart Link', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The link text you would like to use to tantalize customers with your gift wrap offering.', 'wc-gift-wrapper' ),
				'type'     			=> 'text',
				'default'         	=> __( 'Add gift wrapping?', 'wc-gift-wrapper' ),
				'css'      			=> 'min-width:300px;',
			),

			array(
				'id'       			=> 'giftwrap_modal',
				'title'     		=> __( 'Should Gift Wrap option open in pop-up?', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'If checked, there will be a link in the cart, which when clicked will open a window for customers to choose gift wrapping options. It can be styled and might be a nicer option for your site.', 'wc-gift-wrapper' ),
				'type'     			=> 'select',
				'default'         	=> 'yes',
				'options'     		=> array(
					'yes'				=> __( 'Yes', 'wc-gift-wrapper' ),
					'no'				=> __( 'No', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;',
			),

			array(
				'id'       			=> 'giftwrap_details',
				'title'     		=> __( 'Gift Wrap Details', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text to give any details or conditions of your gift wrap offering.', 'wc-gift-wrapper' ),
				'type'     			=> 'textarea',
				'default'			=> '',
				'css'      			=> 'min-width:300px;',
			),

			array(
				'id'       			=> 'giftwrap_text_label',
				'title'     		=> __( 'Gift Wrap Textarea Label', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text you would like to use for the textarea label', 'wc-gift-wrapper' ),
				'type'     			=> 'text',
 				'default'         	=> __( 'Add Gift Wrap Message:', 'wc-gift-wrapper' ),
				'css'      			=> 'min-width:300px;',
			),

			array(
				'id'       			=> 'giftwrap_button',
				'title'     		=> __( 'Gift Wrap Button Text', 'wc-gift-wrapper' ),
				'desc_tip' 			=> __( 'The text for the add to cart button', 'wc-gift-wrapper' ),
				'type'     			=> 'text',
 				'default'         	=> __( 'Add Gift Wrap to Order', 'wc-gift-wrapper' ),
				'css'      			=> 'min-width:300px;',
			),
		);

	}


 	/**
	 * Add settings SECTION under Woocommerce->Products
	 **/
    public function wcgiftwrapper_add_section( $sections ) {
	
		$sections['wcgiftwrapper'] = __( 'Gift Wrapping', 'wc-gift-wrapper' );
		return $sections;

	}


	/**
	* Add settings to the specific section we created before
	*/
	public function wcgiftwrapper_settings( $settings, $current_section ) {

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
			foreach( $gifts_categories as $gifts_category )
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
				'options'     		=> array(
					'after_coupon'	=> __( 'Under Coupon Field in Cart', 'wc-gift-wrapper' ),
					'after_cart'	=> __( 'Above Totals Field in Cart', 'wc-gift-wrapper' ),
				),
				'css'      			=> 'min-width:300px;',
			);

			$settings_slider[]	= array(
				'id'				=> 'giftwrap_category_id',
				'title'           	=> __( 'Gift Wrap Category', 'wc-gift-wrapper' ),
				'type'            	=> 'select',
				'class'           	=> 'chosen_select',
				'css'             	=> 'width: 450px;',
				'desc_tip'			=> __( 'Define the category which holds your gift wrap product(s).', 'wc-gift-wrapper' ),
				'default'         	=> '',
				'options'         	=> $gift_cats,
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
	 *
	 * Add gift wrapping to cart
	 * Forked from Woocommerce Gift Wrap
	 * (c) Copyright 2014, Gema75 - http://codecanyon.net/user/Gema75
	 *
	 **/
	public function add_giftwrap_to_cart() {
		global $woocommerce;

		$giftwrap = isset( $_POST['giftwrapproduct'] ) && !empty( $_POST['giftwrapproduct'] ) ? (int)$_POST['giftwrapproduct'] : false;

		if( $giftwrap && isset( $_POST['giftwrap_btn'] ) ) {

			$giftwrap_giftcategory  = get_option('giftwrap_category_id',true);		
			$giftwrap_found = false;
			
			if( $giftwrap > 0 ) {

				$woocommerce->session->ok_gift = $giftwrap;

				if( sizeof( $woocommerce->cart->get_cart() ) > 0 ) {

					foreach( $woocommerce->cart->get_cart() as $cart_item_key=>$values ) {
						$_product = $values['data'];
						$terms = get_the_terms($_product->id , 'product_cat' );
						if( $terms ) {
							foreach ( $terms as $term ) {
								if( $term->term_id == $giftwrap_giftcategory ) {
									$giftwrap_found = true;
								}
							}
						}
				
						if( $giftwrap_found ) {
							wc_add_notice( 'There is already wrapping in your cart. Remove it to make changes.', 'notice' );
						}

					}
					
					if( !$giftwrap_found ) {
						$woocommerce->cart->add_to_cart($giftwrap);
					}
				
				} else {
				
					$woocommerce->cart->add_to_cart($giftwrap);

				}
				
				if( isset( $_POST['wc_giftwrap_notes'] ) ) {
					$woocommerce->session->giftwrap_notes = $_POST['wc_giftwrap_notes'];
				}			
					
			}

		}

	}

	/**
	 *
	 * Update the order meta with field value 
     * Forked from Woocommerce Gift Wrap
	 * (c) Copyright 2014, Gema75 - http://codecanyon.net/user/Gema75
	 *
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
    		echo '<p><strong>'.__( 'Gift Wrap Note', 'wc-gift-wrapper' ).':</strong> ' . get_post_meta( $order->id, '_giftwrap_notes', true ) . '</p>';
    	}
    	
	}

	/**
	* Add the field to order emails
 	**/ 
	public function order_meta_keys( $keys ) {

		$keys[__( 'Gift Wrap Note', 'wc-gift-wrapper' )] = '_giftwrap_notes';
		return $keys;

	}

	/**
	 *
	 * Add gift wrap to order
	 * Forked from Woocommerce Gift Wrap
	 * (c) Copyright 2014, Gema75 - http://codecanyon.net/user/Gema75
	 *
	 **/
	public function add_gift_wrap_to_order() {
	
		global $woocommerce;

		$giftwrap_modal =  get_option('giftwrap_modal');
		$giftwrap_header =  get_option('giftwrap_header');
		$giftwrap_details = get_option('giftwrap_details');
		$giftwrap_button = get_option('giftwrap_button');
		$giftwrap_category_id = get_option('giftwrap_category_id', true);
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

				<?php if ( $giftwrap_modal == 'yes' ) { ?>
				<p class="giftwrap_header"><a href="#giftwrap_modal" data-toggle="modal" class="btn"><?php echo esc_attr($giftwrap_header); ?></a></p>

				<div id="giftwrap_modal" class="giftwrap_products modal" tabindex="-1">
					 <div class="modal-dialog">
 						<div class="modal-content">
 							<div class="modal-header">
 								<button class="button close giftwrap_cancel" type="button" data-dismiss="modal"><?php _e( 'Cancel', 'wc-gift-wrapper' ); ?></button>
							</div>

							<div class="modal-body">
								<?php if ($giftwrap_details != '') { ?><p class="giftwrap_details"><?php echo esc_attr($giftwrap_details); ?></p><?php } ?>
								<form method="post" action="">
									<ul>
										<?php if ( count( $giftwrap_products ) > 1 ) {	
											foreach( $giftwrap_products as $giftwrap_product ) {
												$get_giftwrap_product = new WC_Product( $giftwrap_product->ID );
												$giftwrap_product_price  = $get_giftwrap_product->get_price_html();
												$giftwrap_product_URL = $get_giftwrap_product->get_permalink();
												if ( $giftwrap_show_thumb == 'yes' ) {
													$product_image = wp_get_attachment_image(get_post_thumbnail_id($giftwrap_product->ID),'thumbnail');
													$product_image = '<div class="giftwrap_thumb"><a href="' . $giftwrap_product_URL . '">' . $product_image . '</a></div>';
													$show_thumb = ' show_thumb';
												} else {
													$show_thumb = '';
													$product_image = '';
												}
												echo '<li class="giftwrap_li' . $show_thumb . '"><label class="giftwrap_desc"><input type="radio" name="giftwrapproduct" value="'.$giftwrap_product->ID.'"><span class="giftwrap_title"> ' . $giftwrap_product->post_title . '</span> ' . $giftwrap_product_price . '</label>' . $product_image . '</li>';
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
								</form>
							</div>
							<div class="modal-footer">
								<button type="submit" class="button alt giftwrap_submit" name="giftwrap_btn"><?php echo esc_attr( $giftwrap_button ); ?></button> 
							</div>
	 					</div><!-- /.modal-content -->
 					</div><!-- /.modal-dialog -->
				</div><!-- /.modal -->


				<?php // non-modal version
				} else { ?>

				<h3 class="giftwrap_header"><?php echo esc_attr($giftwrap_header); ?></h3>
				<form method="post" action="" class="giftwrap_products">
				<?php if ($giftwrap_details != '') { ?><p class="giftwrap_details"><?php echo esc_attr($giftwrap_details); ?></p><?php } ?>

					<ul>
		
					<?php if ( count( $giftwrap_products ) > 1 ) {	
						foreach( $giftwrap_products as $giftwrap_product ) {
							$get_giftwrap_product = new WC_Product( $giftwrap_product->ID );
							$giftwrap_product_price  = $get_giftwrap_product->get_price_html();
							$giftwrap_product_URL = $get_giftwrap_product->get_permalink();
							if ( $giftwrap_show_thumb == 'yes' ) {
								$product_image = wp_get_attachment_image(get_post_thumbnail_id($giftwrap_product->ID),'thumbnail');
								$product_image = '<div class="giftwrap_thumb"><a href="' . $giftwrap_product_URL . '">' . $product_image . '</a></div>';
							}
							echo '<li class="giftwrap_li"><input type="radio" name="giftwrapproduct" value="'.$giftwrap_product->ID.'"><span class="giftwrap_title"> ' . $giftwrap_product->post_title . '</span> ' . $giftwrap_product_price . $product_image . '</li>';
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
		
					<input type="submit" class="button giftwrap_submit" name="giftwrap_btn" value="<?php _e('Add Gift Wrap to Order', 'wc-gift-wrapper');?>"> 
					
				</form>
				<?php } ?>
			</div>
			
		<?php 
		}
	}
	
}  // End class WC_Gift_Wrapping

endif;

new WC_Gift_Wrapping();

?>