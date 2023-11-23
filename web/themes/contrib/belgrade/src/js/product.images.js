
(($, Drupal) => {

  'use strict';

  Drupal.behaviors.productGallery = {
    attach: function (context) {
      var activeImage = document.querySelector(".product-image img");

      $('.image-item img', context).click(function (e) {
        var el = e.target;
        activeImage.src = el.src;

        [...el.parentElement.parentElement.parentElement.children].forEach(sib => sib.classList.remove('active'))
        el.parentElement.parentElement.classList.add('active')
      });

    }
  };

})(jQuery, Drupal);
