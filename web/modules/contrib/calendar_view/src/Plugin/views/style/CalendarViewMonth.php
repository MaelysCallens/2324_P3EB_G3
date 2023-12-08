<?php

namespace Drupal\calendar_view\Plugin\views\style;

/**
 * Custom style plugin to render a calendar.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "calendar_month",
 *   title = @Translation("Calendar by month"),
 *   short_title = @Translation("Month"),
 *   help = @Translation("Displays rows in a calendar by month."),
 *   theme = "views_view_calendar",
 *   display_types = {"normal"}
 * )
 */
class CalendarViewMonth extends CalendarViewBase {

  /**
   * Render a month calendar as a table.
   */
  public function buildTable($year, $month) {
    $days = $this->getOrderedDays();

    $headers = [];
    foreach ($days as $number => $name) {
      $headers[$number] = $name;
    }

    // Dates for this month.
    $month_start = strtotime("$year-$month-01");
    $month_days = date('t', $month_start);
    $first_day = date('w', $month_start);
    $month_weekday_start = array_search($first_day, array_keys($headers));
    $month_weeks = ceil(($month_weekday_start + $month_days) / 7);

    // Next month.
    $next_month = $month == '12' ? '01' : str_pad(($month + 1), 2, '0', STR_PAD_LEFT);
    $next_year = $month == '12' ? $year + 1 : $year;

    // Last month.
    $previous_month = $month == '01' ? '12' : str_pad(($month - 1), 2, '0', STR_PAD_LEFT);
    $previous_year = $month == '01' ? $year - 1 : $year;
    $previous_month_start = strtotime($previous_year . '-' . $previous_month . '-' . '01');
    $previous_month_days = date('t', $previous_month_start);

    $previous_month_offset = [];
    foreach (array_keys($headers) as $number) {
      // Check if month started.
      if ((int) $number == (int) $first_day) {
        break;
      }
      $previous_month_offset[] = $previous_month_days;
      $previous_month_days--;
    }
    $previous_month_offset = array_reverse($previous_month_offset);

    $count = 0;
    for ($i = 0; $i < $month_weeks; $i++) {
      // Prepare row.
      $cells = [];

      // First week.
      if ($i == 0) {
        // Empty days starting the month display.
        foreach ($previous_month_offset as $daynum) {
          $day_number = str_pad($daynum, 2, '0', STR_PAD_LEFT);
          $time_now = strtotime($previous_year . '-' . $previous_month . '-' . $day_number);

          $cells[$time_now] = $this->getCell($time_now);
          $cells[$time_now]['class'][] = 'previous-month';
        }

        // Pending days of this month's first week.
        $x = 7 - count($previous_month_offset);
        do {
          $x--;

          // Count days of the month.
          $count++;

          // Get this day's timestamp.
          $day_number = str_pad($count, 2, '0', STR_PAD_LEFT);
          $time_now = strtotime($year . '-' . $month . '-' . $day_number);

          $cells[$time_now] = $this->getCell($time_now);
          $cells[$time_now]['class'][] = 'current-month';
        } while ($x >= 1);

        // Populate table row.
        $rows[] = ['data' => $cells];

        continue;
      }

      // Rest of the weeks.
      $daynum = 0;
      foreach (array_keys($headers) as $number) {
        // Count days of the month.
        $count++;

        // Fill next months day, if necessary.
        $month_finished = $count > (int) $month_days;
        $week_finished = $number == count($headers);
        if ($month_finished && !$week_finished) {
          $daynum++;
          $time_now = strtotime($next_year . '-' . $next_month . '-' . $daynum);

          $cells[$time_now] = $this->getCell($time_now);
          $cells[$time_now]['class'][] = 'next-month';
          continue;
        }

        // Stop now.
        if ($month_finished) {
          break;
        }

        // Insert day.
        $day_number = str_pad($count, 2, '0', STR_PAD_LEFT);
        $time_now = strtotime($year . '-' . $month . '-' . $day_number);

        $cells[$time_now] = $this->getCell($time_now);
        $cells[$time_now]['class'][] = 'current-month';
      }

      // Populate table row.
      $rows[] = ['data' => $cells];
    }

    // @todo Make this configurable.
    $caption = $this->dateFormatter->format($month_start, 'custom', 'F Y');

    $build = [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => NULL,
      '#attributes' => [
        'summary' => $this->view->getTitle(),
        'data-calendar-view-year' => $year,
        'data-calendar-view-month' => $month,
        'class' => [
          'calendar-view-table',
          'calendar-view-month',
        ],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function buildCalendars(int $selected_timestamp): array {
    $year = date('Y', $selected_timestamp);
    $month = date('m', $selected_timestamp);
    $calendars[$year . $month] = $this->buildTable($year, $month);
    return $calendars;
  }

}
