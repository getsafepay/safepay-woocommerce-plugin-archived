var index = 0;

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
			            jQuery('form[name=checkout] textarea').on('change', function (e) {
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
				}, '#woocommerce-payment-option-safepay');

			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});		
    } else {
    	 jQuery('#woocommerce-payment-option-safepay').html('');
         jQuery('#place_order').removeAttr('disabled');
    }
} 

function isValid() {
	var validation_s = false;
    var validations = [];
    var fields = [];
    jQuery('.validate-required').each(function(index, el) {
		var input = jQuery(el).find('input');
		var select = jQuery(el).find('select');
		var textarea = jQuery(el).find('textarea');

		if (select.length > 0) {
			fields.push(select);
		}
		if (textarea.length > 0) {
			fields.push(textarea);
		}
		if (input.length > 0) {
			fields.push(input);
		}
	});
	jQuery.each( fields, function( key, element ) {
		var $this             = jQuery( element );
		var $parent           = $this.closest( '.form-row' );
		var validation_s      = true;
		var	validate_required = $parent.is( '.validate-required' );
		var validate_email    = $parent.is( '.validate-email' );
		var event_type        = 'validate';
		if ( 'validate' === event_type ) {
			if ( validate_required ) {
				if ( 'checkbox' === $this.attr( 'type' ) && ! $this.is( ':checked' ) ) {
					$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
					validation_s = false;
					validations.push(validation_s);
				} else if ( $this.val() === '' ) {
					$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
					validation_s = false;
					validations.push(validation_s);
				}
			}
			if ( validate_email ) {
				if ( $this.val() ) {
					var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
					if ( ! pattern.test( $this.val()  ) ) {
						$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-email' );
						validation_s = false;
						validations.push(validation_s);
					}
				}
			}
			if ( validation_s ) {
				validations.push(true);
				$parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email' ).addClass( 'woocommerce-validated' );
			}
		}
	});
	if(jQuery.inArray(false, validations) != -1) {
	    return false;
	} else {
		return true;
	}
}

function onClickPlaceOrder(handler) {
	document.querySelector('#place_order').addEventListener('click', handler);
}

function toggleButton(actions) {
	return isValid() ? actions.enable() : actions.disable();
}
