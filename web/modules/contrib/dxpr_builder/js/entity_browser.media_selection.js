/* eslint no-plusplus: 0 */

/**
 * @file entity_browser.modal_selection.js
 *
 * Propagates selected entities from modal display.
 */

(function (Drupal, drupalSettings, window) {
  "use strict";

  // @todo return false here if parent document does not contain active DXPR Builder editor
  let instance = false;
  let entities = {};

  /* eslint no-unused-expressions: 0 */
  Object.prototype.hasOwnProperty.call(
    drupalSettings.entity_browser,
    "modal"
  ) && ({ uuid: instance, entities } = drupalSettings.entity_browser.modal);

  /* eslint no-unused-expressions: 0 */
  Object.prototype.hasOwnProperty.call(
    drupalSettings.entity_browser,
    "iframe"
  ) && ({ uuid: instance, entities } = drupalSettings.entity_browser.iframe);

  // Below selector only matches if target element is a dxpr builder image
  // input, this ensures we don't muck up an EB selection for some FAPI widget
  const { parent } = window;
  const $input = parent
    .jQuery(parent.document)
    .find(`input.dxpr-builder-image-input[data-uuid*=${instance}]`);
  if ($input.length > 0) {
    let entityType;
    const entityIDs = [];
    for (let i = entities.length - 1; i >= 0; --i) {
      entityIDs.push(entities[i][0]);
    }
    if (entities.length && entities[0][2]) {
      [[, , entityType]] = entities;
    } else {
      entityType = "file";
    }

    parent.jQuery
      .ajax({
        type: "get",
        url: parent.drupalSettings.dxprBuilder.dxprCsrfUrl,
        dataType: "json",
        cache: false,
        context: this,
      })
      .done((data) => {
        parent.jQuery
          .ajax({
            type: "POST",
            url: data,
            data: {
              action: "dxpr_builder_get_image_urls",
              entityIDs,
              entityType,
              imageStyle: $input
                .siblings(".dxpr-builder-image-styles:first")
                .val(),
            },
            cache: false,
          })
          .done((res) => {
            // We need to access parent window, find correct image field and close media modal
            if ($input.hasClass("dxpr-builder-multi-image-input")) {
              if ($input.val()) {
                $input.val(`${$input.val()},${res}`);
              } else {
                $input.val(res);
              }
            } else {
              $input.val(res);
            }
          })
          .fail((err) => {
            window.alert(
              Drupal.t(
                "Image selection failed, please make sure to select only image files"
              )
            );
          })
          .always(() => {
            $input.trigger("change");
            $input.removeAttr("data-uuid");
            parent.jQuery(parent.document).find("#az-media-modal").remove();
            parent.jQuery(parent.document).find(".modal-backdrop").remove();
          });
      });
  }
})(Drupal, drupalSettings, window);
