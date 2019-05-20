var indexx = 0;
jQuery(function(){
    jQuery( 'body' ).on( 'updated_checkout', function() {
        usingGateway();
        jQuery('input[name=\"payment_method\"]').change(function(){
            usingGateway();
        });
    });
});

function usingGateway(){
    if(jQuery('form[name="checkout"] input[name="payment_method"]:checked').val() == 'safepay'){
    	jQuery('#place_order').attr('disabled', 'disabled');
		var data = {
			'action': 'get_cart_total',
		};
		jQuery.ajax({
			url: required_values.ajax_url,
			data: data,
			type: 'post',
			success: function(response) {
				var totalPrice = response;
				var enviroment = required_values.enviroment;
				var sandboxKey = required_values.sandboxKey;
				var productionKey = required_values.productionKey;
				var currencySafePay = required_values.currencySafePay;
				safepay.Button.render({
					env: enviroment,
					amount: parseFloat(totalPrice),  
					currency: currencySafePay,
					client: {
						'sandbox': sandboxKey,
						'production': productionKey
					},
					validate: function(actions) {
						toggleButton(actions);
						onClickPlaceOrder(function() {
							toggleButton(actions);
						});
						jQuery('form[name=checkout] input').on('input', function (e) {
							toggleButton(actions);
						});
						jQuery('form[name=checkout] select').on('change', function (e) {
							toggleButton(actions);
						});
						jQuery('form[name=checkout] textarea').on('input', function (e) {
							toggleButton(actions);
						});
					},
					onClick: function() {
						if(isValid() == false) {
							jQuery('#place_order').removeAttr('disabled');
							jQuery('#place_order').trigger('click');
							jQuery('#place_order').attr('disabled', 'disabled');
						}
					},
					payment: function (data, actions) {
						return actions.payment.create({
							transaction: {
							amount: parseFloat(totalPrice),
							currency: currencySafePay
							}
						});
					},
					onCheckout: function(data, actions) {
						jQuery('#reference').attr('value', data.reference);
						jQuery('#token').attr('value', data.token);
						jQuery('#tracker').attr('value', data.tracker);
						jQuery('#place_order').removeAttr('disabled');
						jQuery('#place_order').trigger('click');
					}
				}, '.payment_box.payment_method_safepay');
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});		
    } else {
         jQuery('#place_order').removeAttr('disabled');
    }
} 

function isValid() {
	var validation_s = true;
	jQuery('.validate-required').each(function(index, el) {
		var ell = jQuery(el);
		var input = jQuery(el).find('input');
		var select = jQuery(el).find('select');
		var textarea = jQuery(el).find('textarea');
		if (input.length > 0 && input.val() == '') {
			validation_s = false;
		}
		if (select.length > 0 && select.val() == '') {
			validation_s = false;
		}
		if (textarea.length > 0 && textarea.val() == '') {
			validation_s = false;
		}
	});
	return validation_s;
}

function onClickPlaceOrder(handler) {
	document.querySelector('#place_order').addEventListener('click', handler);
}

function toggleButton(actions) {
	return isValid() ? actions.enable() : actions.disable();
}
