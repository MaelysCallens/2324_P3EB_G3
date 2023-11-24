/**
 * Main.js
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright 2015, Codrops
 * http://www.codrops.com
 */
(function (window) {
  "use strict";

  const support = { animations: Modernizr.cssanimations };
  const animEndEventNames = {
    WebkitAnimation: "webkitAnimationEnd",
    OAnimation: "oAnimationEnd",
    msAnimation: "MSAnimationEnd",
    animation: "animationend",
  };
  const animEndEventName = animEndEventNames[Modernizr.prefixed("animation")];
  const onEndAnimation = function (el, callback) {
    var onEndCallbackFn = function (ev) {
      if (support.animations) {
        if (ev.target != this) return;
        this.removeEventListener(animEndEventName, onEndCallbackFn);
      }
      if (callback && typeof callback === "function") {
        callback.call();
      }
    };
    if (support.animations) {
      el.addEventListener(animEndEventName, onEndCallbackFn);
    } else {
      onEndCallbackFn();
    }
  };

  function extend(a, b) {
    for (const key in b) {
      if (b.hasOwnProperty(key)) {
        a[key] = b[key];
      }
    }
    return a;
  }

  function MLMenu(el, options) {
    this.el = el;
    this.options = extend({}, this.options);
    extend(this.options, options);

    // The menus (<ul>´s)
    this.menus = [].slice.call(this.el.querySelectorAll(".menu__level"));
    // Index of current menu
    this.current = 0;

    this._init();
  }

  MLMenu.prototype.options = {
    // Show breadcrumbs
    breadcrumbsCtrl: true,
    // Initial breadcrumb text
    initialBreadcrumb: "all",
    // Show back button
    backCtrl: true,
    // Delay between each menu item sliding animation
    itemsDelayInterval: 60,
    // Direction
    direction: "r2l",
    // Callback: item that doesn´t have a submenu gets clicked
    // onItemClick([event], [inner HTML of the clicked item])
    onItemClick(ev, itemName) {
      return false;
    },
  };

  MLMenu.prototype._init = function () {
    // Iterate the existing menus and create an array of menus, more specifically an array of objects where each one holds the info of each menu element and its menu items
    this.menusArr = [];
    const self = this;
    this.menus.forEach((menuEl, pos) => {
      const menu = { menuEl, menuItems: [].slice.call(menuEl.children) };
      self.menusArr.push(menu);

      // Set current menu class
      if (pos === self.current) {
        classie.add(menuEl, "menu__level--current");
      }
    });

    // Create back button
    if (this.options.backCtrl) {
      this.backCtrl = document.createElement("button");
      this.backCtrl.className = "menu__back menu__back--hidden";
      this.backCtrl.setAttribute("aria-label", "Go back");
      this.backCtrl.innerHTML = '<span class="icon icon--arrow-left"></span>';
      this.el.insertBefore(this.backCtrl, this.el.firstChild);
    }

    // Create breadcrumbs
    if (self.options.breadcrumbsCtrl) {
      this.breadcrumbsCtrl = document.createElement("nav");
      this.breadcrumbsCtrl.className = "menu__breadcrumbs";
      this.el.insertBefore(this.breadcrumbsCtrl, this.el.firstChild);
      // Add initial breadcrumb
      this._addBreadcrumb(0);
    }

    // Event binding
    this._initEvents();
  };

  MLMenu.prototype._initEvents = function () {
    const self = this;

    for (let i = 0, len = this.menusArr.length; i < len; ++i) {
      this.menusArr[i].menuItems.forEach((item, pos) => {
        if (item.querySelector("a")) {
          item.querySelector("a").addEventListener("click", (ev) => {
            const submenu = ev.target.getAttribute("data-submenu");
            const itemName = ev.target.innerHTML;
            const subMenuEl = self.el.querySelector(
              `ul[data-menu="${submenu}"]`
            );

            // Check if there's a sub menu for this item
            if (submenu && subMenuEl) {
              ev.preventDefault();
              // Open it
              self._openSubMenu(subMenuEl, pos, itemName);
            } else {
              // Add class current
              const currentlink = self.el.querySelector(".menu__link--current");
              if (currentlink) {
                classie.remove(
                  self.el.querySelector(".menu__link--current"),
                  "menu__link--current"
                );
              }
              classie.add(ev.target, "menu__link--current");

              // Callback
              self.options.onItemClick(ev, itemName);
            }
          });
        }
      });
    }

    // Back navigation
    if (this.options.backCtrl) {
      this.backCtrl.addEventListener("click", () => {
        self._back();
      });
    }
  };

  MLMenu.prototype._openSubMenu = function (
    subMenuEl,
    clickPosition,
    subMenuName
  ) {
    if (this.isAnimating) {
      return false;
    }
    this.isAnimating = true;

    // Save "parent" menu index for back navigation
    this.menusArr[this.menus.indexOf(subMenuEl)].backIdx = this.current;
    // Save "parent" menu´s name
    this.menusArr[this.menus.indexOf(subMenuEl)].name = subMenuName;
    // Current menu slides out
    this._menuOut(clickPosition);
    // Next menu (submenu) slides in
    this._menuIn(subMenuEl, clickPosition);
  };

  MLMenu.prototype._back = function () {
    if (this.isAnimating) {
      return false;
    }
    this.isAnimating = true;

    // Current menu slides out
    this._menuOut();
    // Next menu (previous menu) slides in
    const backMenu = this.menusArr[this.menusArr[this.current].backIdx].menuEl;
    this._menuIn(backMenu);

    // Remove last breadcrumb
    if (this.options.breadcrumbsCtrl) {
      this.breadcrumbsCtrl.removeChild(this.breadcrumbsCtrl.lastElementChild);
    }
  };

  MLMenu.prototype._menuOut = function (clickPosition) {
    // The current menu
    const self = this;
    const currentMenu = this.menusArr[this.current].menuEl;
    const isBackNavigation = typeof clickPosition == "undefined";

    // Slide out current menu items - first, set the delays for the items
    this.menusArr[this.current].menuItems.forEach((item, pos) => {
      item.style.WebkitAnimationDelay = item.style.animationDelay =
        isBackNavigation
          ? `${parseInt(pos * self.options.itemsDelayInterval)}ms`
          : `${parseInt(
              Math.abs(clickPosition - pos) * self.options.itemsDelayInterval
            )}ms`;
    });
    // Animation class
    if (this.options.direction === "r2l") {
      classie.add(
        currentMenu,
        !isBackNavigation ? "animate-outToLeft" : "animate-outToRight"
      );
    } else {
      classie.add(
        currentMenu,
        isBackNavigation ? "animate-outToLeft" : "animate-outToRight"
      );
    }
  };

  MLMenu.prototype._menuIn = function (nextMenuEl, clickPosition) {
    const self = this;
    // The current menu
    const currentMenu = this.menusArr[this.current].menuEl;
    const isBackNavigation = typeof clickPosition == "undefined";
    // Index of the nextMenuEl
    const nextMenuIdx = this.menus.indexOf(nextMenuEl);

    const nextMenuItems = this.menusArr[nextMenuIdx].menuItems;
    const nextMenuItemsTotal = nextMenuItems.length;

    // Slide in next menu items - first, set the delays for the items
    nextMenuItems.forEach((item, pos) => {
      item.style.WebkitAnimationDelay = item.style.animationDelay =
        isBackNavigation
          ? `${parseInt(pos * self.options.itemsDelayInterval)}ms`
          : `${parseInt(
              Math.abs(clickPosition - pos) * self.options.itemsDelayInterval
            )}ms`;

      // We need to reset the classes once the last item animates in
      // the "last item" is the farthest from the clicked item
      // let's calculate the index of the farthest item
      const farthestIdx =
        clickPosition <= nextMenuItemsTotal / 2 || isBackNavigation
          ? nextMenuItemsTotal - 1
          : 0;

      if (pos === farthestIdx) {
        onEndAnimation(item, () => {
          // Reset classes
          if (self.options.direction === "r2l") {
            classie.remove(
              currentMenu,
              !isBackNavigation ? "animate-outToLeft" : "animate-outToRight"
            );
            classie.remove(
              nextMenuEl,
              !isBackNavigation ? "animate-inFromRight" : "animate-inFromLeft"
            );
          } else {
            classie.remove(
              currentMenu,
              isBackNavigation ? "animate-outToLeft" : "animate-outToRight"
            );
            classie.remove(
              nextMenuEl,
              isBackNavigation ? "animate-inFromRight" : "animate-inFromLeft"
            );
          }
          classie.remove(currentMenu, "menu__level--current");
          classie.add(nextMenuEl, "menu__level--current");

          // Reset current
          self.current = nextMenuIdx;

          // Control back button and breadcrumbs navigation elements
          if (!isBackNavigation) {
            // Show back button
            if (self.options.backCtrl) {
              classie.remove(self.backCtrl, "menu__back--hidden");
            }

            // Add breadcrumb
            self._addBreadcrumb(nextMenuIdx);
          } else if (self.current === 0 && self.options.backCtrl) {
            // Hide back button
            classie.add(self.backCtrl, "menu__back--hidden");
          }

          // We can navigate again..
          self.isAnimating = false;
        });
      }
    });

    // Animation class
    if (this.options.direction === "r2l") {
      classie.add(
        nextMenuEl,
        !isBackNavigation ? "animate-inFromRight" : "animate-inFromLeft"
      );
    } else {
      classie.add(
        nextMenuEl,
        isBackNavigation ? "animate-inFromRight" : "animate-inFromLeft"
      );
    }
  };

  MLMenu.prototype._addBreadcrumb = function (idx) {
    if (!this.options.breadcrumbsCtrl) {
      return false;
    }

    const bc = document.createElement("a");
    bc.innerHTML = idx
      ? this.menusArr[idx].name
      : this.options.initialBreadcrumb;
    this.breadcrumbsCtrl.appendChild(bc);

    const self = this;
    bc.addEventListener("click", (ev) => {
      ev.preventDefault();

      // Do nothing if this breadcrumb is the last one in the list of breadcrumbs
      if (!bc.nextSibling || self.isAnimating) {
        return false;
      }
      self.isAnimating = true;

      // Current menu slides out
      self._menuOut();
      // Next menu slides in
      const nextMenu = self.menusArr[idx].menuEl;
      self._menuIn(nextMenu);

      // Remove breadcrumbs that are ahead
      let siblingNode;
      while ((siblingNode = bc.nextSibling)) {
        self.breadcrumbsCtrl.removeChild(siblingNode);
      }
    });
  };

  window.MLMenu = MLMenu;
})(window);
