/**
 * @file
 * A JavaScript file that styles the page with bootstrap classes.
 *
 * @see sass/styles.scss for more info
 */
(function ($, Drupal, once) {
  Drupal.behaviors.dxpr_theme_breadcrumbs = {
    attach(context, settings) {
      // Breadcrumbs
      if (settings.dxpr_themeSettings.breadcrumbsSeparator) {
        const { breadcrumbsSeparator } = settings.dxpr_themeSettings;
        $(
          once(
            "dxpr_theme",
            ".page-title-full-width-container .breadcrumb a",
            context
          )
        ).after(
          ` <span class="dxpr-theme-breadcrumb-spacer">${breadcrumbsSeparator}</span> `
        );
      } else {
        $(
          once(
            "dxpr_theme",
            ".page-title-full-width-container .breadcrumb a",
            context
          )
        ).after(' <span class="dxpr-theme-breadcrumb-spacer">/</span> ');
      }
    },
  };
})(jQuery, Drupal, once);
