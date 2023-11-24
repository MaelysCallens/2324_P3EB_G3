/**
 * @file
 * A JavaScript file that styles the page with bootstrap classes.
 *
 * @see sass/styles.scss for more info
 */
(function ($, Drupal, once) {
  let dxpr_themeMenuState = "";

  // Create underscore debounce and throttle functions if they doesn't exist already
  if (typeof _ != "function") {
    window._ = {};
    window._.debounce = function (func, wait, immediate) {
      let timeout;
      let result;

      const later = function (context, args) {
        timeout = null;
        if (args) result = func.apply(context, args);
      };

      const debounced = restArgs(function (args) {
        const callNow = immediate && !timeout;
        if (timeout) clearTimeout(timeout);
        if (callNow) {
          timeout = setTimeout(later, wait);
          result = func.apply(this, args);
        } else if (!immediate) {
          timeout = _.delay(later, wait, this, args);
        }

        return result;
      });

      debounced.cancel = function () {
        clearTimeout(timeout);
        timeout = null;
      };

      return debounced;
    };
    var restArgs = function (func, startIndex) {
      startIndex = startIndex == null ? func.length - 1 : +startIndex;
      return function () {
        const length = Math.max(arguments.length - startIndex, 0);
        const rest = Array(length);
        for (var index = 0; index < length; index++) {
          rest[index] = arguments[index + startIndex];
        }
        switch (startIndex) {
          case 0:
            return func.call(this, rest);
          case 1:
            return func.call(this, arguments[0], rest);
          case 2:
            return func.call(this, arguments[0], arguments[1], rest);
        }
        const args = Array(startIndex + 1);
        for (index = 0; index < startIndex; index++) {
          args[index] = arguments[index];
        }
        args[startIndex] = rest;
        return func.apply(this, args);
      };
    };
    _.delay = restArgs((func, wait, args) =>
      setTimeout(() => func.apply(null, args), wait)
    );

    window._.throttle = function (func, wait, options) {
      let context;
      let args;
      let result;
      let timeout = null;
      let previous = 0;
      if (!options) options = {};
      const later = function () {
        previous = options.leading === false ? 0 : _.now();
        timeout = null;
        result = func.apply(context, args);
        if (!timeout) context = args = null;
      };
      return function () {
        const now = _.now();
        if (!previous && options.leading === false) previous = now;
        const remaining = wait - (now - previous);
        context = this;
        args = arguments;
        if (remaining <= 0 || remaining > wait) {
          if (timeout) {
            clearTimeout(timeout);
            timeout = null;
          }
          previous = now;
          result = func.apply(context, args);
          if (!timeout) context = args = null;
        } else if (!timeout && options.trailing !== false) {
          timeout = setTimeout(later, remaining);
        }
        return result;
      };
    };
  }

  $(window).resize(
    _.debounce(() => {
      if ($("#dxpr-theme-main-menu .nav").length > 0) {
        dxpr_themeMenuGovernorBodyClass();
        dxpr_themeMenuGovernor(document);
      }
      dpxr_themeMenuOnResize();
    }, 50)
  );

  dpxr_themeMenuOnResize();

  const isPageScrollable = () =>
    document.documentElement.scrollHeight > window.innerHeight;

  var navBreak =
    "dxpr_themeNavBreakpoint" in window ? window.dxpr_themeNavBreakpoint : 1200;
  if (
    $(".dxpr-theme-header--sticky").length > 0 &&
    !$(".dxpr-theme-header--overlay").length &&
    $(window).width() > navBreak
  ) {
    var { headerHeight } = drupalSettings.dxpr_themeSettings;
    const headerScroll = drupalSettings.dxpr_themeSettings.headerOffset;
    let scroll = 0;

    if (headerHeight && headerScroll) {
      _.throttle(
        $(window).scroll(() => {
          scroll = $(window).scrollTop();
          if (scroll >= headerScroll) {
            document
              .querySelector(".dxpr-theme-header--sticky")
              .classList.add("affix");
            document
              .querySelector(".dxpr-theme-header--sticky")
              .classList.remove("affix-top");
          } else {
            document
              .querySelector(".dxpr-theme-header--sticky")
              .classList.add("affix-top");
            document
              .querySelector(".dxpr-theme-header--sticky")
              .classList.remove("affix");
          }
          if (scroll >= headerScroll && scroll <= headerScroll * 2) {
            const scrollMargin = isPageScrollable()
              ? Number(headerHeight) + Number(headerScroll)
              : Number(headerHeight);

            document.getElementsByClassName(
              "wrap-containers"
            )[0].style.cssText = `margin-top:${scrollMargin}px`;
          } else if (scroll < headerScroll) {
            document.getElementsByClassName(
              "wrap-containers"
            )[0].style.cssText = "margin-top:0";
          }
        }),
        100
      );
    }
  }

  function dxpr_themeMenuGovernor(context) {
    // Bootstrap dropdown multi-column smart menu
    let navBreak = 1200;
    if ("dxpr_themeNavBreakpoint" in window) {
      navBreak = window.dxpr_themeNavBreakpoint;
    }
    if (
      $(".body--dxpr-theme-header-side").length == 0 &&
      $(window).width() > navBreak
    ) {
      if (dxpr_themeMenuState == "top") {
        return false;
      }
      $(".html--dxpr-theme-nav-mobile--open").removeClass(
        "html--dxpr-theme-nav-mobile--open"
      );
      $(".dxpr-theme-header--side")
        .removeClass("dxpr-theme-header--side")
        .addClass("dxpr-theme-header--top");
      $("#dxpr-theme-main-menu .menu__breadcrumbs").remove();
      $(".menu__level")
        .removeClass("menu__level")
        .css("top", "100%")
        .css("margin-top", 0)
        .css("height", "auto");
      $(".menu__item").removeClass("menu__item");
      $("[data-submenu]").removeAttr("data-submenu");
      $("[data-menu]").removeAttr("data-menu");

      const bodyWidth = $("body").innerWidth();
      const margin = 10;
      $("#dxpr-theme-main-menu .menu .dropdown-menu", context).each(
        function () {
          const width = $(this).width();
          if ($(this).find(".dxpr-theme-megamenu__heading").length > 0) {
            var columns = $(this).find(".dxpr-theme-megamenu__heading").length;
          } else {
            var columns = Math.floor($(this).find("li").length / 8) + 1;
          }
          if (columns > 2) {
            $(this)
              .css({
                width: "100%", // Full Width Mega Menu
                "left:": "0",
              })
              .parent()
              .css({
                position: "static",
              })
              .find(".dropdown-menu >li")
              .css({
                width: `${100 / columns}%`,
              });
          } else {
            const $this = $(this);
            if (columns > 1) {
              // Accounts for 1px border.
              $this
                .css("min-width", width * columns + 2)
                .find(">li")
                .css("width", width);
            }
            // Workaround for drop down overlapping.
            // See https://github.com/twbs/bootstrap/issues/13477.
            const $topLevelItem = $this.parent();
            // Set timeout to let the rendering threads catch up.
            setTimeout(() => {
              const delta = Math.round(
                bodyWidth -
                  $topLevelItem.offset().left -
                  $this.outerWidth() -
                  margin
              );
              // Only fix items that went out of screen.
              if (delta < 0) {
                $this.css("left", `${delta}px`);
              }
            }, 0);
          }
        }
      );
      dxpr_themeMenuState = "top";
      // Hit Detection for Header
      if ($(".tabs--primary").length > 0 && $("#navbar").length > 0) {
        const tabsRect = $(".tabs--primary")[0].getBoundingClientRect();
        if (
          $(".dxpr-theme-header--navbar-pull-down").length > 0 &&
          $("#navbar .container-col").length > 0
        ) {
          const pullDownRect = $(
            "#navbar .container-col"
          )[0].getBoundingClientRect();
          if (dxpr_themeHit(pullDownRect, tabsRect)) {
            $(".tabs--primary").css(
              "margin-top",
              pullDownRect.bottom - tabsRect.top + 6
            );
          }
        } else {
          const navbarRect = $("#navbar")[0].getBoundingClientRect();
          if (dxpr_themeHit(navbarRect, tabsRect)) {
            $(".tabs--primary").css(
              "margin-top",
              navbarRect.bottom - tabsRect.top + 6
            );
          }
        }
      }
      if (
        $("#secondary-header").length > 0 &&
        $("#navbar.dxpr-theme-header--overlay").length > 0
      ) {
        const secHeaderRect = $("#secondary-header")[0].getBoundingClientRect();
        if (
          dxpr_themeHit(
            $("#navbar.dxpr-theme-header--overlay")[0].getBoundingClientRect(),
            secHeaderRect
          )
        ) {
          if (drupalSettings.dxpr_themeSettings.secondHeaderSticky) {
            $("#navbar.dxpr-theme-header--overlay").css(
              "cssText",
              `top:${secHeaderRect.bottom}px !important;`
            );
            $("#secondary-header").addClass(
              "dxpr-theme-secondary-header--sticky"
            );
          } else {
            if ($("#toolbar-bar").length > 0) {
              $("#navbar.dxpr-theme-header--overlay").css(
                "top",
                secHeaderRect.bottom
              );
            } else {
              $("#navbar.dxpr-theme-header--overlay").css("top", "");
            }
            $("#secondary-header").removeClass(
              "dxpr-theme-secondary-header--sticky"
            );
          }
        }
      }
    }
    // Mobile Menu with sliding panels and breadcrumb
    // @see dxpr-theme-multilevel-mobile-nav.js
    else {
      if (dxpr_themeMenuState == "side") {
        return false;
      }
      // Temporary hiding while settings up @see #290
      $("#dxpr-theme-main-menu").hide();
      // Set up classes
      $(".dxpr-theme-header--top")
        .removeClass("dxpr-theme-header--top")
        .addClass("dxpr-theme-header--side");
      // Remove split-megamenu columns
      $(
        "#dxpr-theme-main-menu .menu .dropdown-menu, #dxpr-theme-main-menu .menu .dropdown-menu li"
      ).removeAttr("style");
      $("#dxpr-theme-main-menu .menu").addClass("menu__level");
      $("#dxpr-theme-main-menu .menu .dropdown-menu").addClass("menu__level");
      $("#dxpr-theme-main-menu .menu .dxpr-theme-megamenu").addClass(
        "menu__level"
      );
      $("#dxpr-theme-main-menu .menu a").addClass("menu__link");
      $("#dxpr-theme-main-menu .menu li").addClass("menu__item");
      // Set up data attributes
      $("#dxpr-theme-main-menu .menu a.dropdown-toggle").each(function (index) {
        $(this)
          .attr("data-submenu", $(this).text())
          .next()
          .attr("data-menu", $(this).text());
      });
      $("#dxpr-theme-main-menu .menu a.dxpr-theme-megamenu__heading").each(
        function (index) {
          $(this)
            .attr("data-submenu", $(this).text())
            .next()
            .attr("data-menu", $(this).text());
        }
      );

      const bc = $("#dxpr-theme-main-menu .menu .dropdown-menu").length > 0;
      const menuEl = document.getElementById("dxpr-theme-main-menu");
      const mlmenu = new MLMenu(menuEl, {
        breadcrumbsCtrl: bc, // Show breadcrumbs
        initialBreadcrumb: "menu", // Initial breadcrumb text
        backCtrl: false, // Show back button
        itemsDelayInterval: 10, // Delay between each menu item sliding animation
        // onItemClick: loadDummyData // callback: item that doesn´t have a submenu gets clicked - onItemClick([event], [inner HTML of the clicked item])
      });

      // Close/open menu function
      const closeMenu = function () {
        if (drupalSettings.dxpr_themeSettings.hamburgerAnimation === "cross") {
          $("#dxpr-theme-menu-toggle").toggleClass("navbar-toggle--active");
        }
        $(menuEl).toggleClass("menu--open");
        $("html").toggleClass("html--dxpr-theme-nav-mobile--open");
      };

      // Mobile menu toggle
      $(once("dxpr_themeMenuToggle", "#dxpr-theme-menu-toggle")).click(() => {
        closeMenu();
      });
      $("#dxpr-theme-main-menu").css("position", "fixed").show();

      // Close menu with click on anchor link
      $(".menu__link").click(function () {
        if (!$(this).attr("data-submenu")) {
          closeMenu();
        }
      });

      // See if logo  or block content overlaps menu and apply correction
      if ($(".wrap-branding").length > 0) {
        var brandingBottom =
          $(".wrap-branding")[0].getBoundingClientRect().bottom;
      } else {
        var brandingBottom = 0;
      }
      const $lastBlock = $(
        "#dxpr-theme-main-menu .block:not(.block-menu)"
      ).last();

      // Show menu after completing setup
      // See if blocks overlap menu and apply correction
      if (
        $(".body--dxpr-theme-header-side").length > 0 &&
        $(window).width() > navBreak &&
        $lastBlock.length > 0 &&
        brandingBottom > 0
      ) {
        $("#dxpr-theme-main-menu").css("padding-top", brandingBottom + 40);
      }
      if ($lastBlock.length > 0) {
        const lastBlockBottom = $lastBlock[0].getBoundingClientRect().bottom;
        $(".menu__breadcrumbs").css("top", lastBlockBottom + 20);
        $(".menu__level").css("top", lastBlockBottom + 40);
        var offset = 40 + lastBlockBottom;
        $(".dxpr-theme-header--side .menu__level").css(
          "height",
          `calc(100vh - ${offset}px)`
        );
      } else if (
        $(".body--dxpr-theme-header-side").length > 0 &&
        $(".wrap-branding").length > 0 &&
        brandingBottom > 120
      ) {
        $(".menu__breadcrumbs").css("top", brandingBottom + 20);
        $(".menu__level").css("top", brandingBottom + 40);
        var offset = 40 + brandingBottom;
        $(".dxpr-theme-header--side .menu__level").css(
          "height",
          `calc(100vh - ${offset}px)`
        );
      }
      dxpr_themeMenuState = "side";
    }
  }

  // Fixed header on mobile on tablet
  var headerHeight = drupalSettings.dxpr_themeSettings.headerMobileHeight;
  const headerFixed = drupalSettings.dxpr_themeSettings.headerMobileFixed;
  var navBreak =
    "dxpr_themeNavBreakpoint" in window ? window.dxpr_themeNavBreakpoint : 1200;

  if (
    headerFixed &&
    $(".dxpr-theme-header").length > 0 &&
    $(window).width() <= navBreak
  ) {
    if ($("#toolbar-bar").length > 0) {
      $("#navbar").addClass("header-mobile-admin-fixed");
    }
    if ($(window).width() >= 975) {
      $("#navbar").addClass("header-mobile-admin-fixed-active");
    } else {
      $("#navbar").removeClass("header-mobile-admin-fixed-active");
    }
    $(".dxpr-theme-boxed-container").css("overflow", "hidden");
    $("#toolbar-bar").addClass("header-mobile-fixed");
    $("#navbar").addClass("header-mobile-fixed");
    $("#secondary-header").css("margin-top", +headerHeight);
  }

  $(document).ready(() => {
    if ($("#dxpr-theme-main-menu .nav").length > 0) {
      dxpr_themeMenuGovernorBodyClass();
      dxpr_themeMenuGovernor(document);
    }
  });

  function dxpr_themeMenuGovernorBodyClass() {
    let navBreak = 1200;
    if ("dxpr_themeNavBreakpoint" in window) {
      navBreak = window.dxpr_themeNavBreakpoint;
    }
    if ($(window).width() > navBreak) {
      $(".body--dxpr-theme-nav-mobile")
        .removeClass("body--dxpr-theme-nav-mobile")
        .addClass("body--dxpr-theme-nav-desktop");
    } else {
      $(".body--dxpr-theme-nav-desktop")
        .removeClass("body--dxpr-theme-nav-desktop")
        .addClass("body--dxpr-theme-nav-mobile");
    }
  }

  function dpxr_themeMenuOnResize() {
    // Mobile menu open direction.
    if (
      drupalSettings.dxpr_themeSettings.headerSideDirection === "right" &&
      $(window).width() <= window.dxpr_themeNavBreakpoint
    ) {
      $("#dxpr-theme-main-menu").addClass("dxpr-theme-main-menu--to-left");
    } else {
      $("#dxpr-theme-main-menu").removeClass("dxpr-theme-main-menu--to-left");
    }
    // Fix bug with unstyled content on page load.
    if (
      $(window).width() > window.dxpr_themeNavBreakpoint &&
      $(".dxpr-theme-header--side").length === 0
    ) {
      $("#dxpr-theme-main-menu").css("position", "relative");
    }
  }

  // Accepts 2 getBoundingClientRect objects
  function dxpr_themeHit(rect1, rect2) {
    return !(
      rect1.right < rect2.left ||
      rect1.left > rect2.right ||
      rect1.bottom < rect2.top ||
      rect1.top > rect2.bottom
    );
  }
})(jQuery, Drupal, once);
