/**
 * @file
 * Provides CSS3 flex based on Flexbox layout.
 *
 * Credit: https://fjolt.com/article/css-grthis loader id-masonry
 *
 * @requires aspect ratio fluid in the least to layout correctly.
 * @todo deprecated this is worse than NativeGrid Masonry. We can't compete
 * against the fully tested Outlayer or GridStack library.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var ID = 'b-flex';
  var ID_ONCE = ID;
  var C_MOUNTED = 'is-' + ID_ONCE;
  var C_DONE = C_MOUNTED + '-done';
  var C_RESIZED = C_MOUNTED + '-resized';
  var C_IS_CAPTIONED = 'is-b-captioned';
  var S_ELEMENT = '.' + ID; // + ':not(.' + C_MOUNTED + ')';
  var S_GRID = '.grid';
  var V_BIO = 'bio';
  var E_DONE = V_BIO + ':done';
  var E_RESIZED = V_BIO + ':resizing';
  var V_MAX = 0;

  function columnCount(elm) {
    var box = $.find(elm, S_GRID);
    var parentWidth = $.rect(elm).width;
    var boxWidth = $.rect(box).width;
    var boxStyle = $.computeStyle(box);
    var margin = parseFloat(boxStyle.marginLeft) + parseFloat(boxStyle.marginRight);
    var itemWidth = boxWidth + margin;
    return Math.round((1 / (itemWidth / parentWidth)));
  }

  /**
   * Applies height adjustments to each item.
   *
   * @param {HTMLElement} elm
   *   The container HTML element.
   */
  function process(elm) {
    var heights = {};
    var items = $.findAll(elm, S_GRID);
    var html = $.find(elm, '.b-html');
    var caption = $.find(elm, '.views-field') && $.find(elm, '.views-field p');
    var columns = columnCount(elm);

    function reset(grids) {
      heights = {};

      elm.style.height = '';
      $.each(grids, function (el) {
        el.style.transform = '';
      });
    }

    function toGrid(grids) {
      var layout = function (grid, id) {
        var target = grid.target;
        grid = target ? $.closest(target, S_GRID) : grid;
        id = $.isUnd(id) ? grids.indexOf(grid) : id;

        var cn = $.find(grid, S_GRID + '__content');
        if (!$.isElm(cn)) {
          return;
        }

        var cr = $.rect(cn);
        var ch = cr.height;

        if (ch < 60) {
          cr = $.rect(grid);
          ch = cr.height;
        }

        if (ch < 60) {
          return;
        }

        var curColumn = (id % columns) || 0;
        var style = $.computeStyle(grid);

        if ($.isUnd(heights[curColumn])) {
          heights[curColumn] = 0;
        }

        // grid.style.minHeight = ch + 'px';
        heights[curColumn] += ch + parseFloat(style.marginBottom);

        // If the item has an item above it, then move it to fill the gap.
        if (id - columns >= 0) {
          var nh = id - columns + 1;
          var itemAbove = $.find(elm, S_GRID + ':nth-of-type(' + nh + ')');
          if ($.isElm(itemAbove)) {
            var prevBottom = $.rect(itemAbove).bottom;
            var currentTop = cr.top - parseFloat(style.marginBottom);

            // grid.style.top = '-' + (currentTop - prevBottom) + 'px';
            grid.style.transform = 'translateY(-' + parseInt(currentTop - prevBottom, 0) + 'px)';
          }
        }
      };

      var processItem = function (item, id) {
        layout(item, id);
      };

      // Process on page load.
      $.each(grids, processItem);

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
      };

      checkHeight();

      var loader = $.find(_doc.body, '> .ajaxin-wrapper');
      $.remove(loader);
    }

    function start(grids, cw) {
      if (cw > 1) {
        toGrid(grids);
      }

      $.removeClass(elm, C_RESIZED);

      setTimeout(function () {
        $.addClass(elm, C_DONE);
      }, 101);
    }

    function onMutation(entries) {
      $.each(entries, function (entry) {
        if ($.is(entry.target, elm) && entry.addedNodes.length) {
          setTimeout(function () {
            items = $.findAll(elm, S_GRID);
            reset(items);

            start(items, columns);
          }, 301);
        }
      });
    }

    function initNow(e) {
      var isDone = false;
      var isResized = false;

      if (e) {
        isDone = e.type === E_DONE;
        isResized = e.type === E_RESIZED;

        reset(items);

        if (isDone) {
          $.off(E_DONE + '.' + ID, initNow);
        }
        else if (isResized) {
          $.removeClass(elm, C_DONE);
          $.addClass(elm, C_RESIZED);

          columns = columnCount(elm);
        }
      }

      items = $.findAll(elm, S_GRID);
      start(items, columns);
    }

    if ($.isElm(html)) {
      $.on(E_DONE + '.' + ID, initNow);
    }
    else {
      setTimeout(initNow, 301);
    }

    $.on(E_RESIZED + '.' + ID, $.debounce(initNow, 601));

    var observer = new MutationObserver(onMutation);
    observer.observe(elm, {
      childList: true
    });

    if (caption) {
      $.addClass(elm, C_IS_CAPTIONED);
    }

    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Blazy behavior to HTML element identified by .b-flex.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyFlex = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

}(dBlazy, Drupal, this.document));
