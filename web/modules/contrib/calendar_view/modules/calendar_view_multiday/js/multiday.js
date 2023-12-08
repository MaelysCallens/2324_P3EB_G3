/**
 * @file
 * Calendar multiple day events behaviors.
 */

(function (Drupal, once) {

  const hashAttribute = 'data-calendar-view-hash';

  /**
   * Alter multiday events theming.
   *
   * This behavior is dependent on preprocess hook.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   *
   * @see template_preprocess_calendar_view_day()
   */
  Drupal.behaviors.calendarViewMultiday = {
    attach(context, settings) {
      // Find first instance multiday event from the past.
      let firstInstances = {};
      context.querySelectorAll('[' + hashAttribute + ']').forEach(function (el) {
        if (el.hasAttribute(hashAttribute)) {
          let hash = el.getAttribute(hashAttribute);
          if (!firstInstances[hash]) {
            firstInstances[hash] = el;
          }
        }
      });

      if (!firstInstances || firstInstances.length < 1) {
        return;
      }

      // Alter all other instances of a multiday event.
      once('calendar-view-multiday', Object.values(firstInstances), context).forEach(function (el) {
        if (!el.hasAttribute(hashAttribute)) {
          return;
        }

        let rowHash = el.getAttribute(hashAttribute);
        let rowInstances = context.querySelectorAll('[' + hashAttribute + '="' + rowHash + '"]');
        if (!rowInstances || rowInstances.length < 1) {
          return;
        }

        // Simulate first instance for multiday spanning in the past.
        if (el.classList.contains('is-multi--middle')) {
          el.classList.add('is-multi--first');
        }

        // Get reference "sizes".
        let elBound = el.getBoundingClientRect();

        // Loop on cloned events.
        rowInstances.forEach(function (instance) {
          // Hover all at once.
          instance.addEventListener('mouseover', function (event) {
            rowInstances.forEach(function (other) {
              other.classList.add('hover');
            });
          });
          instance.addEventListener('mouseleave', function (event) {
            rowInstances.forEach(function (other) {
              other.classList.remove('hover');
            });
          });

          // Simulate same size and position in cell.
          if (instance != el) {
            instance.style.height = elBound.height + 'px';

            if (instance.offsetTop < el.offsetTop) {
              instance.style.marginTop = (el.offsetTop - instance.offsetTop) + 'px';
            }
          }
        });
      });
    },
  };
})(Drupal, once);
