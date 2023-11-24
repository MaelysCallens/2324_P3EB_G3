/**
 * @file
 * Defines the behavior of the media entity browser view.
 */

(function ($, _, Backbone, Drupal) {

  "use strict";

  var Selection = Backbone.View.extend({

    events: {
      'click .views-row': 'onClick',
      'dblclick .views-row': 'onClick'
    },

    // Display selected items counter.
    renderCounter: function () {
      // Remove existing status messages, so we can add new status messages.
      $('.media-browser-file-counter').remove();
      if (this.count) {
        var $counter = $('<div class="media-browser-file-counter"></div>');
        var $viewContent = $('.view-content');
        var text = Drupal.formatPlural(this.count, '1 item selected.', '@count items selected.');
        $counter.text(text).addClass('messages messages--status');
        $viewContent.prepend($counter);
      }
    },

    initialize: function () {
      // This view must be created on an element which has this attribute.
      // Otherwise, things will blow up and rightfully so.
      this.uuid = this.el.getAttribute('data-entity-browser-uuid');

      // If we're in an iFrame, reach into the parent window context to get the
      // settings for this entity browser.
      var settings = (frameElement ? parent : window).drupalSettings.entity_browser[this.uuid];

      // Assume a single-cardinality field with no existing selection.
      this.count = settings.count || 0;
      this.cardinality = settings.cardinality || 1;
    },

    deselect: function (item) {
      this.$(item)
        .removeClass('checked')
        .find('input[name ^= "entity_browser_select"]')
        .prop('checked', false);
    },

    /**
     * Deselects all items in the entity browser.
     */
    deselectAll: function () {
      // Create a version of deselect() that can be called within each() with
      // this as its context.
      var _deselect = jQuery.proxy(this.deselect, this);

      this.$('.views-row').each(function (undefined, item) {
        _deselect(item);
      });
    },

    select: function (item) {
      this.$(item)
        .addClass('checked')
        .find('input[name ^= "entity_browser_select"]')
        .prop('checked', true);
    },

    /**
     * Marks unselected items in the entity browser as disabled.
     */
    lock: function () {
      this.$('.views-row:not(.checked)').addClass('disabled');
    },

    /**
     * Marks all items in the entity browser as enabled.
     */
    unlock: function () {
      this.$('.views-row').removeClass('disabled');
    },

    /**
     * Warn the user that they've reached the limit.
     */
    warn: function () {
      var text = Drupal.t('Youʼve reached the maximum selection limit. Please deselect an item in order to choose another.');
      var $message = $('<div class="media-browser-limit"></div>').text(' ' + text);
      if (!this.$('.media-browser-file-counter .media-browser-limit').length) {
        this.$('.media-browser-file-counter').addClass('messages--warning').append($message);
      }
    },

    /**
     * Remove limit warning message.
     */
    unwarn: function () {
      this.$('.media-browser-limit').each(function () {
        $(this).remove();
      });
      this.$('.media-browser-file-counter').removeClass('messages--warning').addClass('messages--status');
    },

    /**
     * Handles click events for any item in the entity browser.
     *
     * @param {jQuery.Event} event
     */
    onClick: function (event) {

      var chosen_one = this.$(event.currentTarget);

      if (chosen_one.hasClass('disabled')) {
        this.warn();
        return false;
      }
      else if (this.cardinality === 1) {
        this.deselectAll();
        this.select(chosen_one);
      }
      else if (chosen_one.hasClass('checked')) {
        this.deselect(chosen_one);
        this.count--;
        this.unlock();
        this.unwarn();
        this.renderCounter();
      }
      else {
        this.select(chosen_one);

        // If cardinality is unlimited, this will never be fulfilled. Good.
        if (++this.count === this.cardinality) {
          this.lock();
        }

        this.renderCounter();
      }
    }

  });

  /**
   * Attaches the behavior of the media entity browser view.
   */
  Drupal.behaviors.mediaEntityBrowserView = {

    getElement: function (context) {
      // If we're in a document context, search for the first available entity
      // browser form. Otherwise, ensure that the context is itself an entity
      // browser form.
      return $(context)[context === document ? 'find' : 'filter']('form[data-entity-browser-uuid]')
        .not('.dxpr-builder-media-processed')
        .addClass('dxpr-builder-media-processed')
        .get(0);
    },

    attach: function (context) {
      var element = this.getElement(context);
      if (element) {
        $(element).data('view', new Selection({ el: element }));
      }
    },

    detach: function (context) {
      var element = this.getElement(context);

      if (element) {
        var view = $(element).data('view');

        if (view instanceof Selection) {
          view.undelegateEvents();
        }
      }
    }

  };

})(jQuery, _, Backbone, Drupal);
