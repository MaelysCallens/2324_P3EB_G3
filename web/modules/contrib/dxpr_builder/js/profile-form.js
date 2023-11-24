(function ($, Drupal) {
  Drupal.behaviors.useAllBlocksViews = {
    attach: function attach() {
      $("#edit-all-blocks").click(function () {
        if (this.checked) {
          // Iterate each checkbox
          $("#edit-blocks")
            .find(":checkbox")
            .each(function () {
              this.checked = true;
            });
        } else {
          $("#edit-blocks")
            .find(":checkbox")
            .each(function () {
              this.checked = false;
            });
        }
      });

      $("#edit-all-views").click(function () {
        if (this.checked) {
          // Iterate each checkbox
          $("#edit-views")
            .find(":checkbox")
            .each(function () {
              this.checked = true;
            });
        } else {
          $("#edit-views")
            .find(":checkbox")
            .each(function () {
              this.checked = false;
            });
        }
      });
    },
  };
})(jQuery, Drupal);
