var SM_product = (function () {

	var $ = jQuery,
		current_colour = '';
		current_size = '',
		update_actions = {
			buy_now_button: function(settings) {
				settings.to_update = 'buy_now_button';
				do_action(settings);
			},
			all: function(settings) {
				settings.to_update = 'all';
				do_action(settings);
			}
		}
		success_callbacks = {
			buy_now_button: function(data, settings) {
				$('#sm_buy_now_button_wrapper').html(data.buy_now_button);
				current_colour = settings.colour;
				current_size = settings.size;
			},
			all: function(data, settings) {
				$('.woocommerce-product-gallery__wrapper').html(data.image_gallery);
				$('#sm_buy_now_button_wrapper').html(data.buy_now_button);
				current_colour = settings.colour;
				current_size = settings.size;
			}
		};

	function update_variation_elements(toUpdate) {
		// Call appropriate function
		var settings = get_product_settings();
		if(typeof settings === 'string'){
			return console.warn(settings);
		}
		update_actions[toUpdate](settings);
	}
	function get_product_settings() {

		var product_settings = {
				size: $('#pa_size').val(),
				colour: $('#pa_colour').val(),
				quantity: $('.quantity input.qty').first().val()
			},
			ps_keys = Object.keys(product_settings),
			currentSetting = '';

		// Check product_settings object has captured product variation values
		for(i = 0; i < ps_keys.length; i++) {

			currentSetting = product_settings[ps_keys[i]];

			if( currentSetting === undefined || currentSetting.length === 0) { 
				return ('Unable to get data for product ' + ps_keys[i]); 
			}
		}
		// add additional settings
		product_settings.action = 'update_variation_elements';
		product_settings.product_id = config.product_id;
		product_settings.nonce = config.nonce;

		return product_settings;
	}
	function update_buy_now_button(clicked_class){

		var buy_now_button = $('#buy_now_button'),
			quantity_adjustment = 0,
			quantity = undefined,
			checkout_url = undefined;

		if(buy_now_button.length){
			checkout_url = buy_now_button.attr('href');
			quantity = parseInt($('input[name="quantity"]').val(), 10);

			if(clicked_class) {
				// Quantity adjustment via the plus/minus qty-handle buttons
				quantity_adjustment = clicked_class.indexOf('qty-plus') < 0 ? -1 : 1;
			} else {
				// Enforce minimum quantity of 1
				$('input[name="quantity"]').first().val(Math.max($('input[name="quantity"]').first().val() , 1));
			}

			// Enforce minimum quantity of 1
			quantity = Math.max(quantity + quantity_adjustment, 1);
			checkout_url = checkout_url.substring(0, checkout_url.indexOf('&quantity=')) + '&quantity=' + quantity;
			buy_now_button.attr('href', checkout_url );
		}
	}

	function do_action(settings) {

		$.ajax({
			type : 'get',
			dataType : 'json',
         url : config.url,
         data : settings,
         success: function(response) {
         	if(response.success) {
         		// settings.ajax_callback(response.data);
         		success_callbacks[settings.to_update](response.data, settings);
            }
            else {
               console.log(response.data);
            }
         },
         error: function(xhr, ajaxOptions, thrownError) {
         	alert(xhr.status);
         	alert(thrownError);
         }
      });

	}

	$( document ).ready(function() {

		// Load images on page load
		update_variation_elements('all');

		// On product colour change, get new Buy Now button and image gallery
		$('#pa_colour').change( function() {
			update_variation_elements('all');
		 });

		// On product size change, get new Buy Now button
		$('#pa_size').change( function() {
			update_variation_elements('buy_now_button');
		 });

		// On product quantity change, update Buy Now button
		$('input[name="quantity"]').first().blur( function() {
			update_buy_now_button();
		 });
		$('.qty-handle').click( function() {
			update_buy_now_button($(this).attr('class'));
		 });
	});
}());