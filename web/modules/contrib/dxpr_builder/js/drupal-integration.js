/* jslint white:true, multivar, this, browser:true getModalDismissValue */
/* global getModalDismissValue, FILE_UPLOAD_MAX_SIZE, dxpr_builder_alert */

/**
 * @file This file is the glue between Drupal and DXPR Builder
 */

(function ($, Drupal, drupalSettings, window) {
  "use strict";

  window.onload = function () {
    let bootstrapVersion = false;
    // Safely check nested objects using optional chaining
    const bs3bs4 = window.jQuery?.fn?.popover?.Constructor?.VERSION;
    const bs5 = window.bootstrap?.Popover?.VERSION;
    if (bs3bs4) {
      bootstrapVersion = bs3bs4.charAt(0);
    } else if (bs5) {
      bootstrapVersion = bs5.charAt(0);
    }
    if (!bootstrapVersion) {
      const messages = new Drupal.Message();
      const message = Drupal.t(
        "The DXPR Builder depends on Bootstrap framework to work. " +
          "Please enable Bootstrap in " +
          "the <a href='@dxpr_builder_settings'>DXPR Builder settings form</a>.",
        {
          "@dxpr_builder_settings": Drupal.url(
            "admin/dxpr_studio/dxpr_builder/settings"
          ),
        }
      );
      messages.add(message, { type: "error" });
    }
  };

  window.dxprBuilder = {};
  // Set elements that DXPR Builder will globally recognize
  window.dxprBuilder.dxpr_editable = [
    "h1",
    "h2",
    "h3",
    "h4",
    "h5",
    "h6",
    "img:not(.not-editable)",
    "a:not(.not-editable)",
    "i:not(.not-editable)",
  ];
  window.dxprBuilder.dxpr_styleable = [];
  window.dxprBuilder.dxpr_textareas = [];
  window.dxprBuilder.dxpr_formats = [];

  /**
   * Hide the resize image controls
   *
   * @param {jQuery} input The input for which the resize image controls should be hidden
   */
  function hideImageStyleControls(input) {
    input.siblings("label:first, .chosen-container:first").hide();
  }

  /**
   * Create an array of image URLs from the image input
   *
   * @var {jQuery} imageInput The image input from which the URLs should be extracted
   * @var {string} delimiter The delimiter used between filenames stored in the input
   *
   * @return {array} An array of image names extracted from the image input
   */
  function getUrlsFromInput(input, delimiter) {
    if (delimiter) {
      return input
        .val()
        .split(delimiter)
        .filter((el) => Boolean(el.length));
    }

    return [input.val().trim()];
  }

  /**
   * Show the resize image controls
   *
   * @param {jQuery} input The input for which the resize image controls should be hidden
   */
  function showImageStyleControls(input) {
    input.siblings("label:first, .chosen-container:first").show();
  }

  /**
   * Create the file upload button users will click to upload an image
   *
   * @var {jQuery} input The input used as a reference for inserting the button into the DOM
   */
  function createFileUploadButton(input, type) {
    switch (type) {
      case "image":
        // Insert the button into the DOM, and set the button to programmatically click
        // the file upload element when the button is created, thereby initiating the
        // browser's file selection dialog.
        input
          .parent()
          .parent()
          .prepend(
            $("<button/>", { class: "ac-select-image btn btn-default" })
              .text(Drupal.t("Select Image"))
              .click(function (e) {
                e.preventDefault();

                // Trigger file upload
                $(this)
                  .siblings(".ac-select-image__content-container")
                  .find(".image_upload:first")
                  .click();
              })
          );
        break;

      case "video":
        input.parent().prepend(
          $("<button/>", { class: "ac-select-video btn btn-default" })
            .text(Drupal.t("Select Video"))
            .click(function (e) {
              e.preventDefault();

              // Trigger file upload
              $(this).siblings(".video_upload:first").click();
            })
        );
        break;
      default:
    }
  }

  /**
   * Create the file upload button users will click to upload an image
   *
   * @var {jQuery} input The input used as a reference for inserting the button into the DOM
   */
  function createEntityBrowserButton(input) {
    // Insert the button into the DOM, and set the button to programmatically click
    // the file upload element when the button is created, thereby initiating the
    // browser's file selection dialog.

    const ACSelectImage = input[0].closest(".ac-select-image");
    const ACSelectImageButton = document.createElement("button");

    ACSelectImageButton.classList.add(
      ...["ac-select-image", "btn", "btn-default"]
    );
    ACSelectImageButton.innerText = Drupal.t("Select Image");
    ACSelectImage.insertAdjacentElement("afterbegin", ACSelectImageButton);

    ACSelectImageButton.addEventListener("click", (e) => {
      e.preventDefault();

      // Trigger Entity Browser Selection
      const { mediaBrowser } = drupalSettings.dxprBuilder;

      let eb = "dxprBuilderSingle";
      if (input.hasClass("dxpr-builder-multi-image-input")) {
        eb = "dxprBuilderMulti";
      }

      input[0].setAttribute("data-uuid", eb);

      // Remove old modal
      let mediaBrowserHTML = document.getElementById("az-media-modal");
      if (mediaBrowserHTML) mediaBrowserHTML.remove();

      // Create new modal
      mediaBrowserHTML = `
      <div id="az-media-modal" class="modal dxpr-builder-ui" style="display:none">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header">
                <span class="close" ${getModalDismissValue()} aria-hidden="true">&times;</span>
                <h4 class="modal-title">${Drupal.t("Media Browser")}</h4>
              </div>
              <div class="modal-body">
              <iframe 
                data-uuid="${eb}"
                src="${
                  drupalSettings.path.baseUrl
                }entity-browser/modal/${mediaBrowser}?uuid=${eb}"
                frameborder="0">
              </iframe>
              </div>
            </div>
        </div>
      </div>
      `;

      // Display the modal
      $(mediaBrowserHTML).modal("show");
    });
  }

  /**
   *
   * @param {string} url The URL from which the file name should be extracted
   * @return {string} The name of the file
   */
  function getFileNameFromUrl(url) {
    const parts = url.split("/");

    return parts[parts.length - 1];
  }

  /**
   *
   * @param {string} url The URL to be altered
   * @param {string} imageStyle The image style that should be applied to that URL. If this is equal to
   *   'original', The original image URL will be returned, instead of a URL
   *   with an image style path
   *
   * @returns {string} The image style URL for the original image
   */
  function getImageStyleUrl(url, imageStyle) {
    const filesUrl = drupalSettings.dxprBuilder.publicFilesFolder;

    // First check if we're dealing with a local image in public files storage
    if (url.indexOf(filesUrl) !== -1 && url.indexOf("svg") === -1) {
      // Check if we're dealing with a non-image style URL
      const isPrivate = url.indexOf("/system/files/") !== -1;
      if (isPrivate) {
        if (url.indexOf("/private/") === -1) {
          // Insert this private image style into the URL
          return url.replace(
            filesUrl,
            `${filesUrl}styles/${imageStyle}/private/`
          );
        }
        // If the image style is 'original', then return non-image style URL
        if (imageStyle === "original") {
          return url.replace(/ styles\/[^/]+\/private\/ /, "");
        }
        // Otherwise swap out the current image style with the new one.
        return url.replace(/ \/styles\/[^/]+ /, `/styles/${imageStyle}`);
      }
      // Public file case.
      if (url.indexOf("/public/") === -1) {
        // Insert private image style into the URL
        return url.replace(filesUrl, `${filesUrl}styles/${imageStyle}/public/`);
      }
      // If the image style is 'original', then return non-image style URL
      if (imageStyle === "original") {
        return url.replace(/ styles\/[^/]+\/public\/ /, "");
      }
      // Otherwise swap out the current image style with the new one.
      return url.replace(/ \/styles\/[^/]+ /, `/styles/${imageStyle}`);
    }
    return url;
  }

  /**
   *
   * @param {jQuery} imageList
   * @param {string} delimiter
   */
  function sortFilenames(imageList, delimiter) {
    const imageInput = imageList.siblings(".form-control:first");
    const urls = getUrlsFromInput(imageInput, delimiter);

    const fileNames = [];
    imageList.children("li").each(function () {
      const filename = $(this).children(":first").attr("data-filename");
      if (filename && filename.length) {
        fileNames.push(filename);
      }
    });

    const sorted = [];
    $.each(fileNames, (index) => {
      $.each(urls, (index2) => {
        if (urls[index2].endsWith(fileNames[index])) {
          sorted.push(urls[index2]);
          return false;
        }
      });
    });

    imageInput.val(sorted.join(delimiter));
  }

  /**
   * Click handler for the remove button on thumbnails
   */
  function thumbnailCloseButtonClickHandler(e) {
    e.preventDefault();

    const thumbnailContainer = $(this).parent().parent();

    const imageList = thumbnailContainer.parent();
    const selectElement = thumbnailContainer.parent().siblings("select:first");

    // Unset the currently selected image style
    selectElement.find("option[selected='selected']").removeAttr("selected");

    // Set the new image style
    selectElement
      .find("option[value='original']")
      .attr("selected", "selected")
      .trigger("chosen:updated");

    // Remove the thumbnail
    thumbnailContainer.remove();

    if (!imageList.children("li:first").length) {
      hideImageStyleControls(imageList.siblings(".form-control:first"));
    }

    sortFilenames(imageList, ",");

    liveEditingManager.update();
  }

  /**
   *
   * @param {string} fileUrl
   * @param {jQuery} input
   * @param {string} delimiter
   * @param {string} fileLocation
   */
  function insertImageThumbnail(fileUrl, input, delimiter, fileLocation) {
    // Create a container for the thumbnail
    const imageContainer = $("<div/>", {
      class: "image-preview",
      "data-filename": getFileNameFromUrl(fileUrl),
    });

    // Create the image element
    const image = fileLocation
      ? $("<img/>", { src: fileLocation })
      : $("<img/>", { src: getImageStyleUrl(fileUrl, "thumbnail") });

    // Create the remove button
    const closeButton = $("<a/>", {
      class: "glyphicon glyphicon-remove",
      href: "#",
    }).click(thumbnailCloseButtonClickHandler);

    // Add the image and close button to the container
    imageContainer.append(image).append(closeButton);

    // Retrieve list of images
    let imageList = input.siblings(".preview:first");

    // If the list doesn't exist, it needs to be created
    if (!imageList.length) {
      imageList = $("<ul/>", { class: "preview ui-sortable" })
        .insertBefore(input.siblings(".chosen-container-single"))
        .sortable({
          stop() {
            sortFilenames($(this), delimiter);
            liveEditingManager.update();
          },
        });
    }

    // If multiple images are not allowed, any existing thumbnails are first removed.
    if (!delimiter) {
      imageList.empty();
    }

    // Insert the container into the list
    $("<li/>", { class: "added" }).append(imageContainer).appendTo(imageList);

    showImageStyleControls(input);
  }

  /**
   * Get image style url with itok.
   *
   * @param {string} imageStyle
   * @param {string} fileId
   * @param {callback} callback
   */
  function dxpr_builder_get_image_style_url(imageStyle, fileId, callback) {
    $.ajax({
      type: "get",
      url: drupalSettings.dxprBuilder.dxprCsrfUrl,
      dataType: "json",
      cache: false,
      context: this,
    }).done((data) => {
      $.ajax({
        type: "POST",
        url: data,
        data: {
          action: "dxpr_builder_get_image_style_url",
          imageStyle,
          fileId,
        },
        cache: false,
      })
        .done((res) => {
          if (typeof callback === "function") {
            callback(res);
          }
          liveEditingManager.update();
        })
        .fail(() => {
          callback("");
        });
    });
  }

  /**
   *
   * @param {string} imageStyle
   * @param {string} fileUrl file location url
   * @param {string} fileId image file id
   * @param {any} input The input used as a reference for inserting the select element into the DOM.
   * @param {string} delimiter The delimiter used between URLs in the input.
   * @param {string} newImages The array with images for elements with multiple images.
   * @param {string} fileLocation The image new location url.
   */
  function dxpr_builder_insert_image(
    updateThumb,
    imageStyle,
    fileUrl,
    fileId,
    input,
    delimiter,
    newImages,
    fileLocation
  ) {
    if (!fileLocation) {
      fileLocation = fileUrl;
    }

    if (updateThumb) {
      insertImageThumbnail(fileUrl, input, delimiter, fileLocation);
    } else if (delimiter) {
      newImages.push(fileLocation);
      // Insert the new image URLs into the image field
      input.val(newImages.join(delimiter));
    } else {
      input.val(fileLocation);
    }
  }

  /**
   * Helper function to load images.
   *
   * @param {string} imageStyle
   * @param {string} fileUrl
   * @param {string} input
   * @param {string} delimiter
   * @param {array} newImages
   */
  function dxpr_builder_get_images(
    updateThumb,
    imageStyle,
    fileUrl,
    input,
    delimiter,
    newImages
  ) {
    const isProtectedFiles = fileUrl.indexOf("/system/files/") !== -1;
    const isPublicFiles = fileUrl.indexOf("/sites/default/files/") !== -1;
    // Check if it's an image stored in files.
    if (isPublicFiles || isProtectedFiles) {
      let fileId = "";
      const idPosition = fileUrl.indexOf("fid=");
      if (idPosition > -1) {
        fileId = fileUrl.substr(idPosition + 4);
      }

      if (fileId.length > 0) {
        dxpr_builder_get_image_style_url(imageStyle, fileId, (fileLocation) => {
          dxpr_builder_insert_image(
            updateThumb,
            imageStyle,
            fileUrl,
            fileId,
            input,
            delimiter,
            newImages,
            fileLocation
          );
        });
      } else {
        const fileLocation = getImageStyleUrl(fileUrl, imageStyle);
        dxpr_builder_insert_image(
          updateThumb,
          imageStyle,
          fileUrl,
          fileId,
          input,
          delimiter,
          newImages,
          fileLocation
        );
      }
    } else {
      dxpr_builder_insert_image(
        updateThumb,
        imageStyle,
        fileUrl,
        null,
        input,
        delimiter,
        newImages,
        null
      );
    }
  }

  /**
   * Create the file upload element used to upload an image. When an image
   * has been uploaded, the URL of the file is inserted into the given input.
   * If multiple files have been uploaded, the URLs are separated by the given
   * delimiter
   *
   * @param {jQuery} input The input used as a reference for inserting the element into the DOM
   * @param {string} delimiter The delimiter used between filenames stored in the input
   */
  function createFileUploadElement(input, delimiter, type) {
    switch (type) {
      case "image":
        // Set up the input that is used to handle the image uploads. This is hidden
        // to the user, but used for transferring the image in the background. When clicked
        // it will handle the upload using AJAX.
        $("<input/>", {
          type: "file",
          class: "image_upload",
          accept: ".gif,.jpg,.jpeg,.png,.svg",
          "data-url": drupalSettings.dxprBuilder.fileUploadUrl,
        })
          .insertBefore(input)
          .fileupload({
            dataType: "json",
            acceptFileTypes: /(\.|\/)(gif|jpe?g|png|svg)$/i,
            formData: { type: "image" },
            done(e, data) {
              const imageStyle = input.siblings("select:first").val();
              // Loop through the returned files and insert them into the field that the front end will
              // use to insert them into the page
              $.each(data.result.files, (index) => {
                let url;
                // Set the URL to be added, based on the image style selected
                if (imageStyle === "original") {
                  url = `${data.result.files[index].fileUrl}?fid=${data.result.files[index].fileId}`;
                } else {
                  url = getImageStyleUrl(
                    data.result.files[index].fileUrl,
                    imageStyle
                  );
                }

                // Insert filename into input
                if (delimiter) {
                  const currentImages = getUrlsFromInput(input, delimiter);

                  currentImages.push(url);
                  input.val(currentImages.join(delimiter));
                } else {
                  input.val(url);
                }

                // Create a thumbnail for the uploaded image
                dxpr_builder_get_images(
                  true,
                  "thumbnail",
                  url,
                  input,
                  delimiter
                );
              });
            },
          });
        break;

      case "video":
        // Set up the input that is used to handle the image uploads. This is hidden
        // to the user, but used for transferring the image in the background. When clicked
        // it will handle the upload using AJAX.
        $("<input/>", {
          type: "file",
          class: "video_upload",
          accept: ".webm,.ogv,.ogg,.mp4",
          "data-url": drupalSettings.dxprBuilder.fileUploadUrl,
        })
          .insertBefore(input)
          .fileupload({
            dataType: "json",
            acceptFileTypes: /(\.|\/)(webm|ogv|ogg|mp4)$/i,
            formData: { type: "video" },
            done(e, data) {
              input.next(".alert-danger").remove();

              // Loop through the returned files and insert them into the field that the front end will
              // use to insert them into the page
              $.each(data.result.files, (index) => {
                const url = `${data.result.files[index].fileUrl}?fid=${data.result.files[index].fileId}`;

                // Insert filename into input
                if (delimiter) {
                  const currentVideos = getUrlsFromInput(input, delimiter);

                  currentVideos.push(url);
                  input.val(currentVideos.join(delimiter));
                } else {
                  input.val(url);
                }
              });

              if (data.result.error) {
                input.after(
                  `<div class="alert alert-danger" role="alert">${data.result.error}</div>`
                );
              }

              liveEditingManager.update();
            },
            fail(e, data) {
              if (data.jqXHR.status === 413) {
                dxpr_builder_alert(
                  `The uploaded video is too large. Max size is ${FILE_UPLOAD_MAX_SIZE}MB`,
                  {
                    type: "danger",
                  }
                );
              } else {
                dxpr_builder_alert(data.jqXHR.statusText, { type: "danger" });
              }
            },
          });
        break;
      default:
    }
  }

  /**
   * Change handler for the image style select element
   *
   * @param {jQuery} selectElement The select element for image styles
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function imageStyleChangeHandler(selectElement, delimiter) {
    // Find the selected option and act on it
    const imageStyle = selectElement.val();

    // Get the image input containing the URL of the image
    const imageInput = selectElement.siblings(".form-control:first");
    // If a delimiter has been provided, it means multiple images are allowed,
    // so each image needs the image style applied
    if (delimiter) {
      // Create an array of the currently entered images
      const currentImages = getUrlsFromInput(imageInput, delimiter);

      // Create an array to hold the images with the new image style URLs
      const newImages = [];
      // Loop through each of the current images, creating an array with the new image URLs
      $.each(currentImages, (index) => {
        const fileUrl = currentImages[index];
        dxpr_builder_get_images(
          false,
          imageStyle,
          fileUrl,
          imageInput,
          delimiter,
          newImages
        );
      });
    } else {
      const fileUrl = imageInput.val();
      dxpr_builder_get_images(false, imageStyle, fileUrl, imageInput);
    }
  }

  /**
   * Create the select element users will use to select an image style
   *
   * @param {jQuery} input The input used as a reference for inserting the select element into the DOM
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function createImageStyleInput(input, delimiter) {
    // TODO: is this variable ever used?
    let label;

    // Create the select element used for selecting an image style
    const imageStyleSelect = $(
      '<select class="dxpr-builder-image-styles"/>'
    ).change(function () {
      imageStyleChangeHandler($(this), delimiter);
    });

    // Add an <option> tag for each image style to the image style select element
    $.each(drupalSettings.dxprBuilder.imageStyles, (key) => {
      imageStyleSelect.append(
        $("<option/>", { value: key }).text(
          drupalSettings.dxprBuilder.imageStyles[key]
        )
      );
    });

    // When editing an existing image, the image input will contain a URL. This URL
    // is parsed to see if it has an image style applied to it.
    const matches = input.val().match(/styles\/([^/]+)\/(public|private)/);
    // If the URL has an image style applied to it, that image style is set as the current selection
    if (matches && matches[1]) {
      imageStyleSelect
        .find(`option[value='${matches[1]}']`)
        .attr("selected", "selected");
    }

    // Append the newly created elements to the page
    input.before(imageStyleSelect).prepend(label);

    // Use jQuery.chosen() to make a cleaner select element for the image styles.
    imageStyleSelect.chosen({
      search_contains: true,
      allow_single_deselect: true,
    });

    hideImageStyleControls(input);
  }

  /**
   * When an image is being edited, a URL will exist in the input. This
   * function creates a thumbnail from that URL.
   *
   * @param {jQuery} input The input from which the URL will be retrieved
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  function createThumbailFromDefault(input, delimiter) {
    let currentImages;
    // If a value exists, thumbnails need to be created
    if (input.val().length) {
      // Get the list of images that exist in the input
      currentImages = getUrlsFromInput(input, delimiter);

      // Loop through the images creating thumbnails for each image
      $.each(currentImages, (index) => {
        const fileUrl = currentImages[index];
        dxpr_builder_get_images(true, "thumbnail", fileUrl, input, delimiter);
      });

      // Show the image controls, since there has been an image inserted
      showImageStyleControls(input);
    }
  }

  /**
   * This function is used to launch the code in this script, and is
   * called by external scripts.
   *
   * @param {HTMLElement} input The input into which URLs should be inserted. The URLs will then
   *   become images in the DOM when the dialog is saved
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  window.dxprBuilder.backend_images_select = function (input, delimiter) {
    const $input = $(input);
    $input
      .css("display", "block")
      .wrap($("<div/>", { class: "ac-select-image" }))
      .wrap($("<div/>", { class: "ac-select-image__content-container" }));

    if (drupalSettings.dxprBuilder.mediaBrowser.length > 0) {
      createEntityBrowserButton($input);
    } else {
      createFileUploadElement($input, delimiter, "image");
      createFileUploadButton($input, "image");
    }
    createImageStyleInput($input, delimiter);
    createThumbailFromDefault($input, delimiter);

    $input.change({ input: $input, delimiter }, (event) => {
      $input.siblings(".preview:first").empty();
      createThumbailFromDefault(input, delimiter);
    });
  };

  /**
   * This function is used to launch the code in this script, and is
   * called by external scripts.
   *
   * @param {HTMLElement} input The input into which URLs should be inserted. The URLs will then
   *   become images in the DOM when the dialog is saved
   * @param {string} delimiter The delimiter used between URLs in the input
   */
  window.dxprBuilder.backend_videos_select = function (input, delimiter) {
    const $input = $(input);
    $input
      .css("display", "block")
      .wrap($("<div/>", { class: "ac-select-video" }));

    createFileUploadElement($input, delimiter, "video");
    createFileUploadButton($input, "video");
  };
})(jQuery, Drupal, drupalSettings, window);
