<?php

namespace Drupal\calendar_view\Plugin\views\pager;

/**
 * Defines required methods class for Calendar View pager plugin.
 */
interface CalendarViewPagerInterface {

  /**
   * Retrieve the calendar date from Calendar style plugin.
   *
   * @return int
   *   The timestamp (default: now).
   */
  public function getCalendarTimestamp(): int;

}
