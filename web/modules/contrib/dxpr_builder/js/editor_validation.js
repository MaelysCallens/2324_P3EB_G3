/* jslint white:true, multivar, this, browser:true */

/**
 * @file This file is to validate dxpr editor with cloud CDN.
 */

(function (Drupal, drupalSettings) {
  "use strict";

  if (drupalSettings.dxprBuilder.dxprJwtValue !== null) {
    fetch(
      `${drupalSettings.dxprBuilder.dxprAssetsUrl}dxpr_builder.min.js?${drupalSettings.dxprBuilder.dxprAssetsParams}`
    ).then((response) => {
      if (response.status === 401) {
        const messages = new Drupal.Message();
        messages.add(
          "Your product key is not valid, please contact DXPR at sales@dxpr.com to resolve this problem.",
          { type: "error" }
        );
      }
    });
  }
})(Drupal, drupalSettings);
