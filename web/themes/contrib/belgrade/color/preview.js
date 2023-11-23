(function ($, Drupal, drupalSettings) {
  Drupal.color = {
    logoChanged: false,
    callback: function callback(context, settings, $form) {
      if (!this.logoChanged) {
        $('.color-preview .color-preview-logo img').attr('src', drupalSettings.color.logo);
        this.logoChanged = true;
      }

      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      $colorPreview.css('backgroundColor', $colorPalette.find('input[name="palette[background]"]').val());

      $colorPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[text]"]').val());

      $colorPreview.find('.color-preview-topbar').css('background-color', $colorPalette.find('input[name="palette[dark]"]').val());

      $colorPreview.find('.color-preview-sidebar h2').css('border-color', $colorPalette.find('input[name="palette[light]"]').val());

      $colorPreview.find('.color-preview-main-color').css('background-color', $colorPalette.find('input[name="palette[primary]"]').val());
      $colorPreview.find('.color-preview-header .after').css('background-color', $colorPalette.find('input[name="palette[primary]"]').val());
      $colorPreview.find('.color-preview-content a').css('color', $colorPalette.find('input[name="palette[primary]"]').val());

      $colorPreview.find('.color-preview-sidebar .preview-block').css('background-color', $colorPalette.find('input[name="palette[light]"]').val());

    }
  };
})(jQuery, Drupal, drupalSettings);
