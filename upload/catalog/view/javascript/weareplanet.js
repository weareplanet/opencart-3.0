(function($) {
	window.WeArePlanet = {
		handler : null,
		methodConfigurationId : null,
		running : false,
		initCalls : 0,
		initMaxCalls : 10,
		confirmationButtonSources: ['#button-confirm', '#journal-checkout-confirm-button'],

		initialized : function() {
			$('#weareplanet-iframe-spinner').hide();
			$('#weareplanet-iframe-container').show();
			WeArePlanet.enableConfirmButton();
			$('#button-confirm').click(function(event) {
				WeArePlanet.handler.validate();
				WeArePlanet.disableConfirmButton();
			});
		},

		fallback : function(methodConfigurationId) {
			WeArePlanet.methodConfigurationId = methodConfigurationId;
			$('#button-confirm').click(WeArePlanet.submit);
			$('#weareplanet-iframe-spinner').toggle();
			WeArePlanet.enableConfirmButton();
		},
		
		reenable: function() {
			WeArePlanet.enableConfirmButton();
			if($('html').hasClass('quick-checkout-page')) { // modifications do not work for js
				triggerLoadingOff();
			}
		},

		submit : function() {
			if (!WeArePlanet.running) {
				WeArePlanet.running = true;
				$.getJSON('index.php?route=extension/payment/weareplanet_'
						+ WeArePlanet.methodConfigurationId
						+ '/confirm', '', function(data, status, jqXHR) {
					if (data.status) {
						if(WeArePlanet.handler) {
							WeArePlanet.handler.submit();
						}
						else {
							window.location.assign(data.redirect);
						}
					}
					else {
						alert(data.message);
						WeArePlanet.reenable();
					}
					WeArePlanet.running = false;
				});
			}
		},

		validated : function(result) {
			if (result.success) {
				WeArePlanet.submit();
			} else {
				WeArePlanet.reenable();
				if(result.errors) {
					alert(result.errors.join(" "));
				}
			}
		},

		init : function(methodConfigurationId) {
			WeArePlanet.initCalls++;
			WeArePlanet.disableConfirmButton();
			if (typeof window.IframeCheckoutHandler === 'undefined') {
				if (WeArePlanet.initCalls < WeArePlanet.initMaxCalls) {
					setTimeout(function() {
						WeArePlanet.init(methodConfigurationId);
					}, 500);
				} else {
					WeArePlanet.fallback(methodConfigurationId);
				}
			} else {
				WeArePlanet.methodConfigurationId = methodConfigurationId;
				WeArePlanet.handler = window
						.IframeCheckoutHandler(methodConfigurationId);
				WeArePlanet.handler
						.setInitializeCallback(this.initialized);
				WeArePlanet.handler
					.setValidationCallback(this.validated);
				WeArePlanet.handler
					.setEnableSubmitCallback(this.enableConfirmButton);
				WeArePlanet.handler
					.setDisableSubmitCallback(this.disableConfirmButton);
				WeArePlanet.handler
						.create('weareplanet-iframe-container');
			}
		},
		
		enableConfirmButton : function() {
			for(var i = 0; i < WeArePlanet.confirmationButtonSources.length; i++) {
				var button = $(WeArePlanet.confirmationButtonSources[i]);
				if(button.length) {
					button.removeAttr('disabled');
				}
			}
		},
		
		disableConfirmButton : function() {
			for(var i = 0; i < WeArePlanet.confirmationButtonSources.length; i++) {
				var button = $(WeArePlanet.confirmationButtonSources[i]);
				if(button.length) {
					button.attr('disabled', 'disabled');
				}
			}
		}
	}
})(jQuery);