'use strict';

(function (Drupal) {
  Drupal.behaviors.collapsibleFieldsFormError = {
    attach: function attach(context) {
      var inputs = context.querySelectorAll('input, select, textarea')
      inputs.forEach(function (input) {
        // Expanding details if field client side validation fails.
        input.addEventListener('invalid', function () {
          input.closest("details").open = true;
        })
      })
    }
  };
})(Drupal);
