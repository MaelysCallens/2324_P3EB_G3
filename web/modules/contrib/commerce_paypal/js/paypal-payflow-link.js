/**
 * @file
 * Payflowlink behaviors.
 */

(function ($) {

  /**
   * Escapes from an iframe if the completion page is displayed within an iframe.
   */
  Drupal.behaviors.commercePayflowEscapeIframe = {
    attach: function (context, settings) {
      if (top !== self) {
        if (typeof settings.commercePayflow !== 'undefined' &&
          typeof settings.commercePayflow.page !== 'undefined') {
          if (settings.commercePayflow.page === 'review') {
            window.parent.location.href = window.location.href + '?payflow-page=review';
          }
          else if (settings.commercePayflow.page === 'return' && settings.commercePayflow.returnUrl !== 'undefined') {
            window.parent.location.href = settings.commercePayflow.returnUrl;
          }
          else {
            window.parent.location.href = window.location.href;
          }
        }
        else {
          window.parent.location.href = window.location.href;
        }
      }
    }
  }

})(jQuery);
