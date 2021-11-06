var SM_stmonday = (function () {

	var $ = jQuery,
		message = '';

	$( document ).ready(function() {
		if(sm_config.email_signup_form !== -1) {
			$('.mast-head .row-table div').eq(1).after(sm_config.email_signup_form);
			$('.es_txt_email').attr('value', '');
			$('.es_txt_email').attr('placeholder', 'EMAIL');

			message = $('.es_subscription_message');

			if(message.html().length > 1) { // message defaults to new line character
				$('#signup-message').html('<h3 class="signup-message__title">StMonday mailing list</h3>\n<div class="signup-message__content">\n' + message.html() + "\n</div>").parent().addClass('signup-feedback--active');
				$('.signup-message__button-wrap').click( function() {
					$(this).parent().removeClass('signup-feedback--active');
				});
			}
		}
	});
}());