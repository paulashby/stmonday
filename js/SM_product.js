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
		},
		open_lightbox: function(settings) {
			settings.to_update = 'lightbox';
			do_action(settings);
		}
	}
	success_callbacks = {
		buy_now_button: function(data, settings) {
			$('#sm_buy_now_button_wrapper').html(data.buy_now_button);
			if(data.sizing_tab_content !== false) {
				$('#tab-sizing_tab').html(data.sizing_tab_content);
			}

			current_colour = settings.colour;
			current_size = settings.size;
		},
		all: function(data, settings) {
			if(data.image_gallery !== false) {
				// data.image_gallery is false if image not found for colour variation
				$('.woocommerce-product-gallery__wrapper').html(data.image_gallery);
				$('.sm_gallery_display_button').click(update_product_shot_view);
			}
			if(data.sizing_tab_content !== false) {
				$('#tab-sizing_tab').html(data.sizing_tab_content);
			}
			$('#sm_buy_now_button_wrapper').html(data.buy_now_button);
			current_colour = settings.colour;
			current_size = settings.size;
		},
		lightbox: function(data) {
			$('html').addClass('no-scroll');
			$('.sm_lightbox').html(data.lightbox_content).addClass('sm_lightbox--active show').click( function(e) {
				
				$(this).removeClass('sm_lightbox--active').unbind();
				
				$(this).one("transitionend", function(event) {
				    // Empty only when transition completes
				    $('html').removeClass('no-scroll');
				    $(this).empty().removeClass('show');
				});
			});
		}
	};

	function update_active(class_name, elmt_number) {
		var collection = $(class_name);
		collection.removeClass('active');
		collection.eq(elmt_number).addClass('active');
	}
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
			quantity: $('.quantity input.qty').first().val(),
			action: 'update_variation_elements',
			product_id: sm_product_config.product_id,
			nonce: sm_product_config.variation_change_nonce
		}, i, curr, ps_keys;

		for (var i = 0; i < sm_product_config.product_variation_attributes.length; i++) {
			curr = sm_product_config.product_variation_attributes[i];
			product_settings[curr.replace('pa_', '')] = $('#' + curr).val();
		}
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
	function update_product_shot_view(){

		var gallery_images;
		var now_active_num = $(this).data('num');

		if($(this).hasClass('active')){
			return;
		}
		update_active('.woocommerce-product-gallery__wrapper picture', now_active_num);
		update_active('.sm_gallery_display_buttons .sm_gallery_display_button', now_active_num);
	}

	function do_action(settings) {

		$.ajax({
			type : 'get',
			dataType : 'json',
			url : sm_product_config.url,
			data : settings,
			success: function(response) {
				// Update nonce
				if(response.data.nonce){
					sm_product_config[response.data.nonce['name']] = response.data.nonce['value'];
				}
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
		
		if(sm_product_config.product_variation_attributes && sm_product_config.product_variation_attributes.indexOf('pa_colour') > -1){
			// Product has colour variations - load image gallery via ajax so we can exclude images of non-selected colour variations. Buy Now button also required
			update_variation_elements('all');
		} else {
			// Load only buy now button - the image gallery is already loaded as we didn't need to filter by variation
			update_variation_elements('buy_now_button');

			// Add listeners to product shot 'carousel' buttons
			$('.sm_gallery_display_button').click(update_product_shot_view);
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
		$('.variation-radios input').change( function(e) {
			var e_target = $(e.target);
			var e_target_name = e_target.attr('name');
			var e_target_value = e_target.attr('value');

			if(e_target_name && e_target_value){
				$('select[name="' + e_target_name + '"]').val(e_target_value).trigger('change');
			}
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

		$('.woocommerce-product-gallery').click( function(e) {

			if(! $(e.target).hasClass('wp-post-image') || $(window).width() <= 650) {
				return;
			}
			var image_id = $(e.target).data('id');
			
			if(image_id){
				var product_settings = {
					action: 'populate_lightbox',
					product_id: sm_product_config.product_id,
					image_id: image_id.toString(),
					nonce: sm_product_config.lightbox_nonce
				}
				update_actions.open_lightbox(product_settings);
			}
		});
	});
}());