/**
 * @file
 * Provides CSS3 Native Grid treated as Masonry based on Grid Layout.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout
 * The two-dimensional Native Grid does not use JS until treated as a Masonry.
 * If you need GridStack kind, avoid inputting numeric value for Grid.
 * Below is the cheap version of GridStack.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.blazy = Drupal.blazy || {};

  var ID = 'b-nativegrid';
  var ID_ONCE = 'b-masonry';
  var C_IS_MASONRY = 'is-' + ID_ONCE;
  var C_MOUNTED = C_IS_MASONRY + '-mounted';
  var S_ELEMENT = '.' + ID + '.' + C_IS_MASONRY;
  var C_IS_CAPTIONED = 'is-b-captioned';
  var S_GRID = '.grid';
  var V_MAX = 0;

  /**
   * Applies grid row end to each grid item.
   *
   * @param {HTMLElement} elm
   *   The container HTML element.
   */
  function process(elm) {
    var heights = {};
    var items = $.findAll(elm, S_GRID);
    var caption = $.find(elm, '.views-field') && $.find(elm, '.views-field p');

    // @todo move it to PHP, unreliable here.
    // It is here for Views rows not aware of captions, not formatters.
    if (caption) {
      $.addClass(elm, C_IS_CAPTIONED);
    }

    var style = $.computeStyle(elm);
    var gap = style.getPropertyValue('row-gap');
    var rows = style.getPropertyValue('grid-auto-rows');
    var box = $.find(elm, S_GRID);
    var parentWidth = $.rect(elm).width;
    var boxWidth = $.rect(box).width;
    var boxStyle = $.computeStyle(box);
    var margin = parseFloat(boxStyle.marginLeft) + parseFloat(boxStyle.marginRight);
    var itemWidth = boxWidth + margin;
    var columnCount = Math.round((1 / (itemWidth / parentWidth)));
    var rowHeight = $.toInt(rows, 1);

    function processItem(el, id) {
      var target = el.target;
      var grid = target ? $.closest(target, S_GRID) : el;

      id = id || items.indexOf(grid);
      gap = $.toInt(gap, 0);

      if (gap === 0) {
        gap = 0.0001;
      }

      // Once setup, we rely on CSS to make it responsive.
      var layout = function () {
        var cn = $.find(grid, S_GRID + '__content');
        var ch = $.outerHeight(cn, true);
        var span = Math.ceil((ch + gap) / (rowHeight + gap));
        var curColumn;
        var style;

        // Sets the grid row span based on content and gap height.
        grid.style.gridRowEnd = 'span ' + span;

        curColumn = (id % columnCount) || 0;

        style = $.computeStyle(grid);

        if ($.isUnd(heights[curColumn])) {
          heights[curColumn] = 0;
        }

        heights[curColumn] += ch + parseFloat(style.marginBottom);
      };

      setTimeout(layout, 301);
    }

    // Process on page load.
    $.each(items, processItem);

    var checkHeight = function () {
      var values = Object.values(heights);
      var max = Math.max.apply(null, values);

      if (max < 0) {
        max = V_MAX;
      }

      // Min-height causes unwanted white-space. Height is too risky with
      // dynamic contents without aspect ratio, but normally fit best.
      max = parseInt(max, 10);
      if (max > 0) {
        elm.style.height = max + 'px';
      }

      V_MAX = max;

      setTimeout(function () {
        elm.style.height = '';
      }, 1200);
    };

    checkHeight();

    // Process on resize.
    Drupal.blazy.checkResize(items, processItem, elm, processItem);

    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Blazy behavior to HTML element identified by .b-nativegrid.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyNativeGrid = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }

  };

}(dBlazy, Drupal));
