/**
 * @file
 * belgrade theme main JS file.
 *
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  // Initiate all Toasts on page.
  Drupal.behaviors.belgradeToast = {
    attach: function (context, settings) {
      $(once('initToast', '.toast', context)).each(function () {
        $(this).toast("show");
      });
    }
  };

  // Accordion buttons containing Edit links.
  Drupal.behaviors.accordionButtonLinks = {
    attach: function (context, settings) {
      $(once('.fieldset-legend.accordion-button', '.fieldset-legend.accordion-button a', context)).on('click', function (event) {
        event.preventDefault();
      });
    }
  };

  // Collapse and accordion if a field is required.
  Drupal.behaviors.focusRequired = {
    attach: function (context, settings) {
      var inputs = document.querySelectorAll('form .accordion input');
      [].forEach.call(inputs, function(input) {
        input.addEventListener('invalid',function(e){
          var accordion = input.closest(".collapse");
          $(accordion).collapse('show');
        });
      });
    }
  };

  // Collapse certain accordions on mobile
  Drupal.behaviors.collapseAccordionMob = {
    attach: function () {
      const breakPoint = drupalSettings.responsive.breakpoints["belgrade.sm-max"]
      var x = window.matchMedia(breakPoint)

      if (x.matches) { // If media query matches collapse the bef
        var befAccordions = document.querySelectorAll('.bef-exposed-form .collapse');
        if (befAccordions.length) {
          [].forEach.call(befAccordions, function(bef) {
            $(bef).collapse('hide')
          });
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
