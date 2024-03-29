<?php
add_action( 'wp_loaded', function() {
	global $pagenow;
	$request_uri = $_SERVER['REQUEST_URI'];
	$complianz_request = strpos($request_uri, "/wp-json/complianz/v1") !== false;
	$cookie_policy_request = strpos($request_uri, "/cookie-policy") !== false;
	$privacy_statement_request = strpos($request_uri, "/privacy-statement") !== false;
	
	if(
		$pagenow !== 'wp-login.php' 
		&& ! is_admin() 
		&& ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) 
		&& ! is_user_logged_in()
	) {

		if (
			defined( 'IN_MAINTENANCE' )
			&& IN_MAINTENANCE
		) {
			http_response_code(503);
			if ( file_exists( WP_CONTENT_DIR . '/stm-maintenance/stm-maintenance.php' ) ) {

				// Show maintenance page
				require_once( WP_CONTENT_DIR . '/stm-maintenance/stm-maintenance.php' );
			}
			die();
		}
		else if ( 
			mode_is_active('lookbook') 
			&& $request_uri !== '/lookbook/'
			&& !$complianz_request 
			&& !$cookie_policy_request
			&& !$privacy_statement_request
		) {
			if ( file_exists( WP_CONTENT_DIR . '/stm-maintenance/stm-maintenance-lookbook.php' ) ) {

				// Show soft launch holding page with link to lookbook
				require_once( WP_CONTENT_DIR . '/stm-maintenance/stm-maintenance-lookbook.php' );
			}
			die();
		}
	}
});

// enqueue parent styles and custom scripts and provide config data to page
add_action( 'wp_enqueue_scripts', 'sm_enqueue_resources' );

function sm_enqueue_resources() {

	// enqueue parent styles
	wp_enqueue_style( 'parent-theme', get_template_directory_uri() . '/style.css' );

    // parent theme enqueues child theme styles
    // https://wordpress.stackexchange.com/questions/329875/how-to-avoid-loading-style-css-twice-in-child-theme

    // enqueue child theme scripts - have to use get_stylesheet_directory_uri() for child path - clear as mud then!
    // https://wordpress.stackexchange.com/questions/230085/get-template-directory-uri-pointing-to-parent-theme-not-child-theme
	wp_enqueue_script( 'stmonday-script', get_stylesheet_directory_uri() . '/js/SM_stmonday.js', array(), '1.0.0', true );

	if(is_page('lookbook')) {
		wp_enqueue_style( 'lookbook-font', get_stylesheet_directory_uri() . '/style_lookbook.css' );
	}

	$email_signup_form = -1;
	
	if(mode_is_active('emailsubtop')){
			
		// Get email sign up form from Email Subscribers plug in
		$email_signup_form = do_shortcode("[email-subscribers-form id='2']");

		// Customise email sign up form
		$email_signup_form = str_replace(array("Sign Up*<br />", "Subscribe"), array("", "Sign up"), $email_signup_form);

		// Remove tabs (they mangle the config object)
		$email_signup_form = json_encode(trim(preg_replace('/\t+/', '', $email_signup_form)));
	}

	$lookbooklinktop = -1;

	if(mode_is_active('lookbooklinktop') && !mode_is_active('topmenu')){

		// This standalone Lookbook link is added only if topmenu is off
		// When topmenu is on, the link is incorporated into the menu
		$lookbookurl = get_permalink( get_page_by_path( 'lookbook' ) );

		$lookbooklinktop = json_encode("<div class='lookbooklink'>
			<a href='$lookbookurl'></a>
		</div>");

	}

	$js_data = "var sm_config = {
		email_signup_form: $email_signup_form,
		lookbooklinktop: $lookbooklinktop
	};";

	wp_add_inline_script('stmonday-script', $js_data);

	if ( is_product() ){

		wp_enqueue_script( 'stmonday-product-script', get_stylesheet_directory_uri() . '/js/SM_product.js', array(), '1.0.0', true );
		$admin_url = admin_url('admin-ajax.php');
		
		$variation_change_nonce = wp_create_nonce('variation_change_nonce');
		$lightbox_nonce = wp_create_nonce('lightbox_nonce');

		$product_id = get_queried_object_id();
		$product = wc_get_product($product_id);
		$attributes = $product->is_type( 'variable' ) ? json_encode(array_keys($product->get_variation_attributes())) : json_encode(array());
		
		$js_data = "var sm_product_config = {
			url: '$admin_url',
			variation_change_nonce: '$variation_change_nonce',
			lightbox_nonce: '$lightbox_nonce',
			product_id: '$product_id',
			product_variation_attributes: $attributes
		};";
		wp_add_inline_script('stmonday-product-script', $js_data);
	}
}

add_filter('pre_kses', 'sm_show_regional_privacy_and_cookie_policy_links');

function sm_show_regional_privacy_and_cookie_policy_links ($string) {

	if (strpos($string, '?cmplz_region_redirect=true') === false) {

		if (strpos($string, '/privacy-statement-uk/') !== false) {
		    
		    return str_replace('/privacy-statement-uk/', '/privacy-statement-uk/?cmplz_region_redirect=true', $string);
		}
		
		if (strpos($string, '/cookie-policy-uk/') !== false) {
		   
		    return str_replace('/cookie-policy-uk/', '/cookie-policy-uk/?cmplz_region_redirect=true', $string);
		}
	}

	return $string;
}

/* Enable svg - NOTE: This is safe as long as we're not allowing users to upload files */
// add_filter('upload_mimes', 'cc_mime_types');

// function cc_mime_types($mimes) {
// 	$mimes['svg'] = 'image/svg+xml';
//  return $mimes;
// }

// Remove theme image sizes that aren't used for product shots - these will need to be commented out when adding Neto or Max Slider images
add_action('init', 'remove_extra_image_sizes');

function remove_extra_image_sizes( $name ) {

	global $_wp_additional_image_sizes;
    $image_keys = array(
    	'maxslider_slide',
    	'neto_item',
    	'neto_fullwidth',
    	'neto_hero',
    	'neto_about'
    );

    foreach ($image_keys as $key) {
    	if ( isset( $_wp_additional_image_sizes[ $key ] ) ) {
           unset( $_wp_additional_image_sizes[ $key ] );
       }
    }
}

//////////////////////////////////////////////////////////////
// Custom Site Settings page /////////////////////////////////

// Disable Gutenberg on Site Settings page
add_filter('use_block_editor_for_post', 'disable_gutenberg_on_settings_page', 5, 2);

function disable_gutenberg_on_settings_page($can, $post){
    if($post){
        if($post->post_name === "site-settings"){
            return false;
        }
    }
    return $can;
}

// Remove page from pages listing
add_action('pre_get_posts', 'hide_settings_page');

function hide_settings_page($query) {

	$sm_query = new WP_Query();
	
    if ( !is_admin() && ! $sm_query->is_main_query() ) {
        return;
    }    
    global $typenow;
    if ($typenow === "page") {
        $settings_page = get_page_by_path("site-settings",NULL,"page")->ID;
        $query->set( 'post__not_in', array($settings_page) );    
    }
    return;

}

// Add the page to admin menu
add_action( 'admin_menu', 'add_site_settings_to_menu' );

function add_site_settings_to_menu(){
    add_menu_page( 'Site Settings', 'Site Setttings', 'manage_options', 'post.php?post='.get_page_by_path("site-settings",NULL,"page")->ID.'&action=edit', '', 'dashicons-admin-tools', 20);
}

// Change the active menu item
add_filter('parent_file', 'higlight_custom_settings_page');

function higlight_custom_settings_page($file) {
    global $parent_file;
    global $pagenow;
    global $typenow, $self;

    $settings_page = get_page_by_path("site-settings",NULL,"page")->ID;
    $post = false;

    foreach ($_GET as $key => $entry) {
    	if($key == "post") {
    		$post = (int) htmlspecialchars($entry);
    	}
    }
    if ($pagenow === "post.php" && $post === $settings_page) {
    	$file = "post.php?post=$settings_page&action=edit";
    }
    return $file;
}

// Add custom title to settings page
add_action( 'admin_title', 'edit_site_settings_title' );

function edit_site_settings_title() {
    global $post, $title, $action, $current_screen;
    if( isset( $current_screen->post_type ) && $current_screen->post_type === 'page' && $action == 'edit' && $post->post_name === "site-settings") {
        $title = $post->post_title.' - '.get_bloginfo('name');           
    }
    return $title;  
}

// Hide advanced custom fields menu item from Shop manager
add_filter('acf/settings/show_admin', 'sm_acf_show_admin');

function sm_acf_show_admin( $show ) {
    // shop_manager does not have the  activate_plugins capability
    return current_user_can('activate_plugins');   
}
/*
// Add manage_options capability to Shop manager
add_action( 'admin_head', 'sm_add_manage_options_capability' );

function sm_add_manage_options_capability() {
	$shop_manager_role = get_role( 'shop_manager' );
	$shop_manager_role->add_cap( 'manage_options' );
}
*/
// Hide admin menu items from Shop manager
add_action( 'admin_head', 'sm_hide_menu' );

function sm_hide_menu() {

	// Hiding menu items only from the shop_manager
	$curr_user = wp_get_current_user();
	$roles = (array) $curr_user->roles;

	if( in_array('shop_manager', $roles) ) {
		remove_menu_page( 'options-general.php' ); //Settings
	  	remove_menu_page( 'edit.php?post_type=elementor_library' ); // Elementor Templates
		remove_menu_page( 'elementor' ); // Elementor
		remove_menu_page( 'smush' );
		remove_menu_page( 'duplicator' );
		remove_menu_page( 'complianz' );
   	}
}

// -- Apply Site Settings --------------------------------- //

// Add mode classes to body tag
add_filter('body_class', 'add_body_classes');

function add_body_classes($classes) {

	if ( $post = get_page_by_path( 'site-settings', OBJECT, 'page' ) ){

		$queried_ob = get_queried_object();

		if ( is_object($queried_ob) ) {
			
			if( isset($queried_ob->post_name) ) {
				$slug = $queried_ob->post_name;
				$classes[] = "slug__$slug";
			}
		}

		$id = $post->ID;
		$modes = get_field('modes', $id);

		if( $modes ){
			foreach ($modes as $mode) {
				$classes[] = $mode;
			} 	
		}
	}
    return $classes;
}

// END Custom Site Settings page /////////////////////////////
//////////////////////////////////////////////////////////////


// When Site Settings > Lookbook is active, footer is removed by neto-child/footer.php

// Add elements before footer
add_action( 'get_footer', 'sm_pre_footer' );

function sm_pre_footer() {

	$pre = "";

	if(mode_is_active('emailsubtop')){

		// Add container for email signup feedback messages
		$pre.= '<div class="signup-feedback"><a class="signup-message__button-wrap"><div class="signup-message__button"></div></a><div id="signup-message" class="signup-message signup-message--inactive">
		</div></div>';
	}
	
	// Add div before footer to push it to bottom of screen when content is short
	$pre .= '<div class="sm-footer-pusher"></div>';
	echo $pre;
}

// Change breadcrumb separator to pipe character
add_filter( 'woocommerce_breadcrumb_defaults', 'sm_change_breadcrumb_delimiter', 999 );

function sm_change_breadcrumb_delimiter( $defaults ) {
	$defaults['delimiter'] = "<span class='breadcrumb-delimiter'> | </span>";
	return $defaults;
}

// Remove 'Select options' and 'Add to basket' on listings
add_filter('woocommerce_loop_add_to_cart_link', 'sm_remove_listings_buttons');

function sm_remove_listings_buttons( $product ){
	return;
}

// Use radio buttons for variations - this is called separately for each product attribute
// https://stackoverflow.com/questions/36219833/woocommerce-variations-as-radio-buttons
add_filter('woocommerce_dropdown_variation_attribute_options_html', 'variation_radio_buttons', 20, 2);

function variation_radio_buttons($html, $args) {

	$MAX_PER_ROW = 3;

	$args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
		'options'          => false,
		'attribute'        => false,
		'product'          => false,
		'selected'         => false,
		'name'             => '',
		'id'               => '',
		'class'            => '',
		'show_option_none' => __('Choose an option', 'woocommerce'),
	));

	if(false === $args['selected'] && $args['attribute'] && $args['product'] instanceof WC_Product) {
		$selected_key     = 'attribute_'.sanitize_title($args['attribute']);
		$args['selected'] = isset($_REQUEST[$selected_key]) ? wc_clean(wp_unslash($_REQUEST[$selected_key])) : $args['product']->get_variation_default_attribute($args['attribute']);
	}

	$options               = $args['options'];
	$product               = $args['product'];
	$attribute             = $args['attribute'];
	$name                  = $args['name'] ? $args['name'] : 'attribute_'.sanitize_title($attribute);
	$id                    = $args['id'] ? $args['id'] : sanitize_title($attribute);
	$class                 = $args['class'];
	$show_option_none      = (bool)$args['show_option_none'];
	$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __('Choose an option', 'woocommerce');

	if(empty($options) && !empty($product) && !empty($attribute)) {
		$attributes = $product->get_variation_attributes();
		$options    = $attributes[$attribute];
	}

	$radios = '<div class="variation-radios">';

	// Store radio entries in array so we can structure html according to number of items
	$radio_entries = array();

	if(!empty($options)) {
		if($product && taxonomy_exists($attribute)) {
			$terms = wc_get_product_terms($product->get_id(), $attribute, array(
				'fields' => 'all',
			));

			foreach($terms as $term) {
				if(in_array($term->slug, $options, true)) {
					$id = $name.'-'.$term->slug;
					$radio_entries[] = '<input type="radio" id="'.esc_attr($id).'" name="'.esc_attr($name).'" value="'.esc_attr($term->slug).'" '.checked(sanitize_title($args['selected']), $term->slug, false).'><label for="'.esc_attr($id).'">'.esc_html(apply_filters('woocommerce_variation_option_name', $term->name)).'</label>';
				}
			}
		} else {
			foreach($options as $option) {
				$id = $name.'-'.$option;
				$checked = sanitize_title($args['selected']) === $args['selected'] ? checked($args['selected'], sanitize_title($option), false) : checked($args['selected'], $option, false);
				$radio_entries[] = '<input type="radio" id="'.esc_attr($id).'" name="'.esc_attr($name).'" value="'.esc_attr($option).'" id="'.sanitize_title($option).'" '.$checked.'><label for="'.esc_attr($id).'">'.esc_html(apply_filters('woocommerce_variation_option_name', $option)).'</label>';
			}
		}
	}
	if($attribute == "pa_size" || $radio_entries <= $MAX_PER_ROW) {

		// All radio buttons will fit on single row
		$radios .= "<div class='variation-radio-row'>" . implode("", $radio_entries) . "</div";
	} else {

		// Multiple rows of buttons required
		$count = 0;

		foreach ($radio_entries as $radio) {
			
			switch ($count % $MAX_PER_ROW) {

				case 0:
				$radios .= "<div class='variation-radio-row'>$radio";
				break;

				case $MAX_PER_ROW - 1:
				$radios .= "$radio</div>";
				break;

				default:
				$radios .= $radio;
				break;
			} 
			$count++;
		}
	}
	$radios .= '</div>';

	return $html.$radios;
}

// Exclude out-of-stock variations if backorders are not allowed. Added when replacing select menu with radio buttons.
// add_filter('woocommerce_variation_is_active', 'variation_check', 10, 2);

// function variation_check($active, $variation) {

// 	if(!$variation->is_in_stock() && !$variation->backorders_allowed()) {
// 		return false;
// 	}
// 	return $active;
// }

// Position tabs on right of product page
add_action( 'wp', 'sm_move_tab_position' );

function sm_move_tab_position() {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_output_product_data_tabs', 60 );
}

// Add lightbox container
add_action( 'woocommerce_after_single_product_summary', 'sm_render_lightbox', 10 );

function sm_render_lightbox() {
	echo "<div class='sm_lightbox fade-in'></div>";
}

// position of social media buttons above sku on product page
add_action( 'wp', 'sm_move_sharing_position' );

function sm_move_sharing_position() {
	remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 35 );
}

// Add message directing customers to sizing info tab
add_action( 'woocommerce_before_single_variation', 'sm_size_info' );

function sm_size_info() {

	global $product;

	$default_attributes = $product->get_default_attributes();
	$message = array_key_exists('pa_size', $default_attributes) ? "See Additional Information about size below" : "This product comes in a single size";
	echo "<p class='sm-product-size-instruction'>$message</p>";	
}

// Remove thumbnails from product page
remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );

// If product has variations defer to AJAX load. Else, render all products shots instead of single image
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'sm_render_all_product_shots', 10 );

function sm_render_all_product_shots ( $html ) {

	$button_count = 0;
	$responsive_image_sizes = get_product_gallery_image_sizes();

	// Single product pages (woocommerce)
	if ( is_product() ) {

		global $product;
		$product_has_colour_variations = strlen($product->get_attribute('pa_colour'));

		if( $product_has_colour_variations ) {
			// Product page will load images via ajax call
			return "";
		}

		$image_id = $product->get_image_id();

		if( ! strlen($image_id)) { 
			// No main image - let woo use placeholder
			return $html; 
		}

		ob_start(); // Start buffering

		echo sm_get_image_markup($image_id, $responsive_image_sizes, true);
		$button_count++;

	    // Loop through gallery Image Ids
		foreach( $product->get_gallery_image_ids() as $image_id ) {

			echo sm_get_image_markup($image_id, $responsive_image_sizes);
			$button_count++;

		}
		echo sm_get_display_button_markup($button_count);
	    // Return buffered content
		return ob_get_clean();
	}
	return $html;
}

// Remove short description from product page
add_action('add_meta_boxes', 'remove_short_description', 999);

// Remove the Short Description field from the WooCommerce admin panel
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

function remove_short_description() {
	remove_meta_box( 'postexcerpt', 'product', 'normal');
}

// Add wrapper for Buy Now button on the product page 
add_action( 'woocommerce_after_add_to_cart_form', 'sm_after_add_to_cart_form' );

function sm_after_add_to_cart_form(){

	global $product;

	$product_id = $product->get_id();
	$settings = array(
		'product_id' => $product->get_id(),
		'quantity' => 1,
		'placeholder' => true
	);
	$buy_now_button = get_buy_now_button($settings);
	echo "<div id='sm_buy_now_button_wrapper'>$buy_now_button</div>";

}

// Variation images are sizing guides, so we want to load our thumbnails from the image gallery
add_filter( 'woocommerce_cart_item_thumbnail', 'getCartItemThumbnail', 111, 2 );

function getCartItemThumbnail( $img, $cart_item ) {

    if ( isset( $cart_item['product_id'] ) ) {

        $product = wc_get_product($cart_item['product_id']);

        if ( $product && $product->is_type( 'variable' ) ) {

        	$item_data = $cart_item['data'];
			$attributes = $item_data->get_attributes();

			if(array_key_exists('pa_colour', $attributes)) {

				// Product has colour variations, so need to get correct pic from image gallery
				$cart_item_colour = $attributes['pa_colour'];

				$responsive_image_sizes = array(
					'media_library_sizes' => array(100, 150, 300),
					'fallback_size' => 200,
					'sizes_attribute' =>'70px' // Match width up to 1000vw. 1000px for wider vw
				);

				foreach( $product->get_gallery_image_ids() as $image_id ) {

					$gallery_image_colour  = get_field( "colour", $image_id );

					if($gallery_image_colour == $cart_item_colour) {

						// Image's 'colour' field matches $cart_item colour - this is the correct image
						return sm_get_image_markup($image_id, $responsive_image_sizes);
					}
				}
			}
        	// No match found or product doesn't have colours variations - just return the main image
        	return $product->get_image();
        }
    }
    // Product doesn't have variations, so no need to worry about sizing graphic being used as cart thumbnail
    return $img;
}

// Remove reset variation button from variable products
add_filter('woocommerce_reset_variations_link', '__return_empty_string');

// Remove 'Choose an option' from product variation select menu
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'sm_filter_dropdown_option_html', 12, 2 );

function sm_filter_dropdown_option_html( $html, $args ) {
	$show_option_none_text = $args['show_option_none'] ? $args['show_option_none'] : __( 'Choose an option', 'woocommerce' );
	$show_option_none_html = '<option value="">' . esc_html( $show_option_none_text ) . '</option>';

	$html = str_replace($show_option_none_html, '', $html);

	return $html;
}

// Add subtitles to product pages
add_action( 'woocommerce_single_product_summary', 'woocommerce_single_item_subtitle', 5 );

function woocommerce_single_item_subtitle() {
	global $post, $product;
	$product_id = $product->get_id();
	$subtitle = get_field('product_subtitle', $product_id);
	echo "<h2 class='sm-product-subtitle'>$subtitle</h2>";
}

// Add subtitles to thumb listings (home page thumbs, related products etc)
add_action( 'woocommerce_shop_loop_item_title', 'sm_woocommerce_shop_loop_item_subtitle', 20 );

function sm_woocommerce_shop_loop_item_subtitle() {
	global $post, $product;
	$product_id = $product->get_id();
	$subtitle = get_field('product_subtitle', $product_id);
	echo "<p class='sm-thumb-subtitle'>$subtitle</p>";
}

// Handle lightbox ajax calls
add_action( 'wp_ajax_populate_lightbox', 'sm_populate_lightbox' );
add_action( 'wp_ajax_nopriv_populate_lightbox', 'sm_populate_lightbox' );

function sm_populate_lightbox() {

	$responsive_image_sizes = array(
		'media_library_sizes' => array(768, 1024, 1536, 2048),
		'fallback_size' => 1024,
		'sizes_attribute' =>'(min-width: 1000px) 1024px, 100vw' // Match width up to 1000vw. 1000px for wider vw
	);

	// Check for nonce security      
	if ( ! wp_verify_nonce( $_GET['nonce'], 'lightbox_nonce' ) ) {
		wp_send_json_error ('The request could not be processed') ;
		die ( 'Insecure request' );
	}
	if ( ! isset ($_REQUEST['image_id'])) { wp_send_json_error( "Missing required argument: 'image_id'" ); }

	$return_data = array(
		'lightbox_nonce' => wp_create_nonce('lightbox_nonce')	
	);

	$image_id = intval(sanitize_text_field(filter_input(INPUT_GET, 'image_id')));

	$return_data = array(
		'lightbox_content' => sm_get_image_markup($image_id, $responsive_image_sizes),
		'nonce' => array(
			'name'=>'lightbox_nonce',
			'value'=>wp_create_nonce('lightbox_nonce')
		)
	);
	exit(wp_send_json_success($return_data));
}

// Process product variation change ajax calls
add_action( 'wp_ajax_update_variation_elements', 'sm_on_product_variation_change' );
add_action( 'wp_ajax_nopriv_update_variation_elements', 'sm_on_product_variation_change' );

function sm_on_product_variation_change() {
	
	if ( ! wp_verify_nonce( $_GET['nonce'], 'variation_change_nonce' ) ) {
		wp_send_json_error ('The request could not be processed') ;
		die ( 'Insecure request' );
	}
	if ( ! isset ($_REQUEST['to_update'])) { (wp_send_json_error( "Missing required argument: 'to_update'" )); }
	if ( ! isset ($_REQUEST['quantity'])) { wp_send_json_error( "Missing required argument: 'quantity'" ); }
	if ( ! isset ($_REQUEST['product_id'])) { wp_send_json_error( "Missing required argument: 'product_id'" ); }

	$to_update = sanitize_text_field(filter_input(INPUT_GET, 'to_update'));

	$quantity = intval(sanitize_text_field(filter_input(INPUT_GET, 'quantity')));
	$product_id = intval(sanitize_text_field(filter_input(INPUT_GET, 'product_id')));
	$product = wc_get_product($product_id);

	$product_details = array(
		'size' => false,
		'colour' => false,
		'quantity' => $quantity,
		'product_id' => $product_id,
		'product' => $product
	);
	$return_data = array(
		'sizing_tab_content' => false
	);

	if ( isset ($_REQUEST['colour'])) { 
		$product_details['colour'] = sanitize_text_field(filter_input(INPUT_GET, 'colour'));
	}
	if ( isset ($_REQUEST['size'])) { 
		// Provide markup to update the sizing tab as a new size has been selected
		$product_details['size'] = sanitize_text_field(filter_input(INPUT_GET, 'size')); 
		$return_data['sizing_tab_content'] = get_sizing_tab_content($product_details);
	}
	$return_data['buy_now_button'] = get_buy_now_button($product_details);

	if($to_update === 'all') {
		$return_data['image_gallery'] = get_image_gallery($product_id, $product_details['colour']);
	}

	$return_data['nonce'] = array(
		'name'=>'variation_change_nonce',
		'value'=>wp_create_nonce('variation_change_nonce')
	);

	exit(wp_send_json_success($return_data));
}

// Populate ACF colour field with variation colours
add_filter('acf/load_field/name=colour', 'acf_load_color_field_choices');

function acf_load_color_field_choices( $field ) {

	// reset choices
	$field['choices'] = array();

	$choices = get_terms('pa_colour');

	$field['choices'][ 'none' ] = 'No Colour Variations';
	if( is_array($choices) ) {
		foreach( $choices as $choice ) {

			$field['choices'][ $choice->slug ] = $choice->name;
		}
	}

	return $field;

}

// Customise product page tabs
add_filter( 'woocommerce_product_tabs', 'sm_customise_tabs', 98, 1 );

function sm_customise_tabs( $tabs ) {

	global $product;
	$default_attributes = $product->get_default_attributes();

	// Remove Sizing/Additional Information tab
	unset( $tabs['additional_information'] );

	if(array_key_exists('pa_size', $default_attributes)) {
		
		$tabs['sizing_tab'] = array(
			'title' 	=> __( 'Sizing', 'woocommerce' ),
			'priority' 	=> 50,
			'callback' 	=> 'sm_sizing_product_tab_content'
		);
	}
	
	// Remove Reviews tab
	unset( $tabs['reviews'] );

	// Add new tabs
	$tabs['shipping_tab'] = array(
		'title' 	=> __( 'Shipping', 'woocommerce' ),
		'priority' 	=> 50,
		'callback' 	=> 'sm_shipping_product_tab_content'
	);
	$tabs['returns_tab'] = array(
		'title' 	=> __( 'Returns', 'woocommerce' ),
		'priority' 	=> 50,
		'callback' 	=> 'sm_returns_product_tab_content'
	);

	return $tabs;
}

// Utility functions ///////////////////////////////////////////////////////

// Populate Sizing tab on product page
function sm_sizing_product_tab_content() {

	global $product;

	$settings = array(
		'product' => $product,
		'product_id' => $product->get_id()
	);

	echo "<h2>Sizing</h2>";
	echo get_sizing_tab_content($settings);
}
// Populate Shipping tab on product page
function sm_shipping_product_tab_content() {

	if ( $post = get_page_by_path( 'site-settings', OBJECT, 'page' ) ){
		
		$id = $post->ID; 
		$shipping_info = get_field('shipping_tab_content', $id);
	}
	echo "<h2>Shipping</h2>$shipping_info";
}
// Populate Returns tab on product page
function sm_returns_product_tab_content() {
	
	if ( $post = get_page_by_path( 'site-settings', OBJECT, 'page' ) ){
		
		$id = $post->ID; 
		$returns_info = get_field('returns_tab_content', $id);
	}
	echo "<h2>Returns</h2>$returns_info";
}
// This function is called indirectly by sm_customise_tabs( $tabs ) when page loads
// And by sm_on_product_variation_change() to ensure correct size details are displayed
function get_sizing_tab_content($settings) {

	$default_attributes = $settings['product']->get_default_attributes();
	$match_attributes =  array();

	// 'size' will be provided when different variation selected
	if(array_key_exists('size', $settings)){
		// Get sizing image for provided size
		$match_attributes['attribute_pa_size'] = $settings['size'];	
	} else {
		// Get sizing image for default size
		$match_attributes['attribute_pa_size'] = $default_attributes['pa_size'];
	}

	if(array_key_exists('pa_colour', $default_attributes)) {
		// We get the sizing images from the default colour variations (they're stored as variation images)
		$match_attributes['attribute_pa_colour'] = $default_attributes['pa_colour'];	
	}
	
	$data_store = WC_Data_Store::load( 'product' );
	$variation_id = $data_store->find_matching_product_variation(
		new \WC_Product($settings['product_id']), $match_attributes
	);

	$variation = new WC_Product_Variation($variation_id);
	
	$image_id = $variation->get_image_id('edit');
	if ($image_id){
		// Image exists on the variation itself - this is our sizing image
		$fading_image = sprintf("<div class='fade-in'>%s</div>", $variation->get_image());
		return $fading_image;
	} else {
		// Variation doesn't have image - use textual alternative
		$width = $variation->get_width() . get_option( 'woocommerce_dimension_unit' );
		$length = $variation->get_length() . get_option( 'woocommerce_dimension_unit' );
		return "<p>Size: $width x $length</p>";
	}
}

function get_image_gallery($product_id, $colour){

	$product = new WC_Product_Variable( $product_id );
	$image_gallery = array();
	$default_image_id = $product->get_image_id();

	if(! is_string($default_image_id) || strlen($default_image_id) == 0) {
		error_log("Error: Default image unavailable for " . $product->get_title());
		return wc_placeholder_img();
	}

	$default_colour = get_field( "colour", $default_image_id);
	$default_image_ids = array();
	$image_colour = $default_colour;

	$responsive_image_sizes = get_product_gallery_image_sizes();

	// $colour is false if product has no colour variations
	if($colour === false || $image_colour === $colour) {
		// If product has colour variations, the main image should only be included if its colour field matches the value of $colour
		$image_gallery[] = sm_get_image_markup($default_image_id, $responsive_image_sizes, true);
	} else {
		$default_image_ids[] = $default_image_id;
	}

	$image_data = get_gallery_images($product->get_gallery_image_ids(), $default_colour, $colour, $image_gallery);
	$default_image_ids = array_merge($default_image_ids, $image_data['default_image_ids']);
	
	$num_images = count($image_data['gallery_images']);
	if($num_images > 0){
		$image_data['gallery_images'][] = sm_get_display_button_markup($num_images);
		return $image_data['gallery_images'];
	}
	// No images found for colour variation - use default images as fallback
	$image_data = get_gallery_images($default_image_ids, $default_colour, $colour, $image_gallery, true);
	$image_gallery = $image_data['gallery_images'];

	$num_images = count($image_gallery);
	if($num_images > 0){
		$image_gallery[] = sm_get_display_button_markup($num_images);
		return $image_gallery;
	}

	return false;
}
function get_product_gallery_image_sizes() {
	return array(
		'media_library_sizes' => array(300, 1024, 150, 768, 100, 1500),
		'fallback_size' => 540,
		'sizes_attribute' =>'(min-width: 1200px) 540px, (min-width: 1000px) 440px, (min-width: 780px) 330px, (min-width: 620px) 540px, 90vw' 
	);	
}
function get_gallery_images($image_ids, $default_colour, $colour, $image_gallery, $fallback = false) {

	$responsive_image_sizes = get_product_gallery_image_sizes();
	$default_image_ids = array();

	// $image_ids is the result of a call to $product->get_gallery_image_ids()
	foreach( $image_ids as $image_id ) {
		$image_colour  = get_field( "colour", $image_id );

		// $image_gallery array is only populated if $colour == default_colour
		$active_image = count($image_gallery) === 0;
		$not_colour_variation_image = strlen($image_colour) === 0 || $image_colour === "none";

		if($fallback || $colour === false || $image_colour === $colour) {
			$image_gallery[] = sm_get_image_markup($image_id, $responsive_image_sizes, $active_image);
		} else if($not_colour_variation_image) {
			// Image is not a colour variation (is not tagged with a colour), so we want it regardless of which colour variation is selected
		 	$image_gallery[] = sm_get_image_markup($image_id, $responsive_image_sizes);
		 } else if($image_colour === $default_colour) {
			// Store default image id
			$default_image_ids[] = $image_id;
		}
	}

	return array( "gallery_images" => $image_gallery, "default_image_ids" => $default_image_ids);
}

function sm_get_display_button_markup($button_count){

	// Product images are displayed one at a time at narrow widths. These buttons are positioned under the image and control which product image is shown.
	if($button_count < 2){ return; }

	$buttons = "<div class='sm_gallery_display_buttons'>";
	$active_class = "active";

	for ($i=0; $i < $button_count; $i++) { 
		$buttons .= "<div class='sm_gallery_display_button $active_class' data-num='$i'></div>";
		$active_class = "";
	}

	return "$buttons</div>";
}

function sm_get_image_markup($image_id, $custom_image_sizes, $active_image = false) {

	$attachment = get_post($image_id);
	$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);
	$image_title = $attachment->post_title;
	$picture_class = $active_image ? 'fade-in active' : 'fade-in';
	$image_sizes = $custom_image_sizes['media_library_sizes'];
	$sizes = $custom_image_sizes['sizes_attribute'];
	$fallback_size = $custom_image_sizes['fallback_size'];
	$fallback = wp_get_attachment_image_url($image_id, array($fallback_size, $fallback_size));
	$srcset = make_srcset_entry($fallback, "$fallback_size");
	$webp_srcset = make_srcset_entry(get_webp_url($fallback), "$fallback_size");

	// Add all sizes to srcsets
	foreach ($image_sizes as $image_size) {
		$image_url = wp_get_attachment_image_url($image_id, array($image_size, $image_size));
		
		if($image_url){
			$srcset .= make_srcset_entry($image_url, $image_size);
			$webp_srcset .= make_srcset_entry(get_webp_url($image_url), $image_size);
		}
	}

	$srcset = rtrim($srcset, ", ");
	$webp_srcset = rtrim($webp_srcset, ", ");

	return "<picture class='$picture_class'>
	<source type='image/webp'
	srcset='$webp_srcset'
	sizes='$sizes'
	/>
	<source  type='image/jpeg'
	srcset='$srcset'
	sizes='$sizes'
	/>
	<img src='($fallback'
	class='wp-post-image' alt='$image_alt' title='$image_title' data-id='$image_id'/>
	</picture>";
}

function make_srcset_entry($image_url, $image_size) {
	return $image_url . " " . $image_size . "w, ";
}

function get_webp_url($image_url) {
	return replace_first('wp-content/', 'wp-content/webp-express/webp-images/', $image_url) . '.webp';
}

function replace_first($search, $replace, $subject) {
	$pos = strpos($subject, $search);
	if ($pos !== false) {
		return substr_replace($subject, $replace, $pos, strlen($search));
	}
	return $subject;
}

function get_buy_now_button($settings) {

	$button_class = 'single_add_to_cart_button button alt';
	$disable = false; 

	// We're using a placeholder button on page load just so a Buy Now button is in place from the get go
	// An immediate ajax call replaces this with a version with the correct variation details
	if( array_key_exists('placeholder', $settings) || ! $settings['product']->is_type( 'variable' )){
		$variation_id = $settings['product_id'];
	} else {
		$match_attributes =  array();

		if($settings['colour'] !== false) {
			$match_attributes['attribute_pa_colour'] = $settings['colour'];
		}
		if($settings['size'] !== false) {
			$match_attributes['attribute_pa_size'] = $settings['size'];
		}

		$data_store = WC_Data_Store::load( 'product' );
		$variation_id = $data_store->find_matching_product_variation(
			new \WC_Product($settings['product_id']), $match_attributes
		);
		
		// Disable button if variation is out of stock
		$variation = new WC_Product_variation($variation_id);
		if(!$variation->is_in_stock() && !$variation->backorders_allowed()) {
			error_log('Variation is out of stock');
			$button_class .= ' disabled wc-variation-is-unavailable';
			$disable = true;
		}
	}

	$checkout_url = wc_get_checkout_url();
	$button_href = $disable ? "" : "href='" . wc_get_checkout_url() . "?add-to-cart=$variation_id&quantity=" . $settings['quantity'] . "'";

	return "<div class='sm_buy_now__pre'><p class='sm_buy_now__pre-txt'>Or</p></div><a id='buy_now_button' class='$button_class' $button_href>Buy Now</a>";
}

function mode_is_active($mode) {

	if ( $post = get_page_by_path( 'site-settings', OBJECT, 'page' ) ){
		
		$id = $post->ID; 
		$modes = get_field('modes', $id);

		if($modes && array_search($mode, $modes) !== false){
			 return true;
		}
	}
	return false;
}
