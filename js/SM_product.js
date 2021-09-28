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
			quantity: $('.quantity input.qty').first().val()
		}, i, curr, ps_keys;

		for (var i = 0; i < config.product_variation_attributes.length; i++) {
			curr = config.product_variation_attributes[i];
			product_settings[curr.replace('pa_', '')] = $('#' + curr).val();
		}

		ps_keys = Object.keys(product_settings);

		// Check product_settings object has captured product variation values
		for(i = 0; i < ps_keys.length; i++) {

			curr = product_settings[ps_keys[i]];

			if( curr === undefined || curr.length === 0) { 
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
		quantity,
		checkout_url;

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
					success_callbacks[settings.to_update](response.data, settings);
				}
				else {
					console.log(response.data);
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				console.log(xhr.status);
				console.log(thrownError);
			}
		});

	}

	$( document ).ready(function() {

		// Get images on page load
		if(config.product_variation_attributes.length){
			// Load image gallery and Buy Now button
			update_variation_elements('all');
			// capitalise_select_options();
		} else {
			// Load only buy now button - the image gallery is already loaded as we didn't need to filter by variation
			update_variation_elements('buy_now_button');
		}

		// On product colour change, get new Buy Now button and image gallery
		$('#pa_colour').change( function() {
			update_variation_elements('all');
		});

		// On product size change, get new Buy Now button - requires ajax call as we need the variation code
		$('#pa_size').change( function() {
			update_variation_elements('buy_now_button');
		});

		// Make radio button clicks trigger change event on corresponding select menu entry
		$('.variation-radios input').change( function() {
			$('.variation-radios input:checked').each(function(index, element) {
				var $el = $(element);
				var thisName = $el.attr('name');
				var thisVal  = $el.attr('value');
				$('select[name="'+thisName+'"]').val(thisVal).trigger('change');
			});
		});

		// Enable appropriate radio buttons after variations have been selected
		$(document).on('woocommerce_update_variation_values', function() {
			$('.variation-radios input').each(function(index, element) {
				var $el = $(element);
				var thisName = $el.attr('name');
				var thisVal  = $el.attr('value');
				$el.removeAttr('disabled');
				if($('select[name="'+thisName+'"] option[value="'+thisVal+'"]').is(':disabled')) {
					$el.prop('disabled', true);
				}
			});
		});

		// On product quantity change, update Buy Now button - no ajax call needed as we're just updating quantity
		$('input[name="quantity"]').first().blur( function() {
			update_buy_now_button();
		});
		$('.qty-handle').click( function() {
			update_buy_now_button($(this).attr('class'));
		});
	});
}());