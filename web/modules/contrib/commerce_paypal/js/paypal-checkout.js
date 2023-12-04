(function ($, Drupal) {
  'use strict';

  Drupal.paypalCheckout = {
    initialized: false,
    makeCall(url, settings) {
      settings = settings || {};
      const ajaxSettings = {
        dataType: 'json',
        url: url
      };
      $.extend(ajaxSettings, settings);
      return $.ajax(ajaxSettings);
    },
    renderButtons(settings) {
      paypal.Buttons({
        createOrder: () => {
          return Drupal.paypalCheckout.makeCall(settings.onCreateUrl, {
            type: 'POST',
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({
              flow: settings.flow
            })
          }).then((data) => {
            return data.id;
          }, (data) => {
            const messages = new Drupal.Message();
            const options = {
              type: 'error'
            };
            messages.clear();
            messages.add(data.responseJSON.message, options);
            if (data.status === 403) {
              location.reload();
            }
          });
        },
        onApprove: (data) => {
          Drupal.paypalCheckout.addLoader();
          return Drupal.paypalCheckout.makeCall(settings.onApproveUrl).then((data) => {
            if (data.hasOwnProperty('redirectUrl')) {
              window.location.assign(data.redirectUrl);
            }
          }, (data) => {
            const messages = new Drupal.Message();
            const options = {
              type: 'error'
            };
            messages.clear();
            messages.add(data.responseJSON.message, options);
            if (data.status === 403) {
              location.reload();
            }
          });
        },
        onClick: (data) => {
          // Set the fundingSource in the cookie for retrieval post-checkout.
          if (data.hasOwnProperty('fundingSource')) {
            document.cookie = 'lastFundingSource = ' + data.fundingSource + ';path=/';
          }
        },
        style: settings['style']
      }).render('#' + settings['elementId']);
    },
    initialize(settings) {
      if (!this.initialized) {
        // Ensure we initialize the script only once.
        this.initialized = true;
        let script = document.createElement('script');
        script.src = settings.src;
        script.type = 'text/javascript';
        script.setAttribute('data-partner-attribution-id', 'CommerceGuys_Cart_SPB');
        document.getElementsByTagName('head')[0].appendChild(script);
      }
      const waitForSdk = (settings) => {
        if (typeof paypal !== 'undefined') {
          Drupal.paypalCheckout.renderButtons(settings);
        }
        else {
          setTimeout(() => {
            waitForSdk(settings)
          }, 100);
        }
      };
      waitForSdk(settings);
    },
    addLoader() {
      const $background = $('<div class="paypal-background-overlay"></div>');
      const $loader = $('<div class="paypal-background-overlay-loader"></div>');
      $background.append($loader);
      $('body').append($background);
    }
  };

  Drupal.behaviors.commercePaypalCheckout = {
    attach(context, settings) {
      for (let index in settings.paypalCheckout) {
        once('paypal-checkout-init', '#' + settings.paypalCheckout[index]['elementId']).forEach(() => {
          Drupal.paypalCheckout.initialize(settings.paypalCheckout[index]);
        });
      }
    }
  };

})(jQuery, Drupal);
