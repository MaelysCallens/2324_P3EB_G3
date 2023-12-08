<?php

namespace Drupal\calendar_view\Plugin\views\style;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\Plugin\views\style\DefaultStyle;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for Calendar View style plugin.
 */
abstract class CalendarViewBase extends DefaultStyle implements CalendarViewInterface {

  const DATE_FIELD_TYPES = ['created', 'changed', 'datetime', 'daterange', 'smartdate', 'timestamp'];

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Contains the system.data configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $dateConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->logger = $container->get('logger.channel.calendar_view');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->dateConfig = $container->get('config.factory')->get('system.date');
    return $instance;
  }

  /**
   * Helper method to make sure a timestamp is a timestamp.
   *
   * @param mixed $value
   *   A given value.
   *
   * @return int
   *   The timestamp or the original value.
   */
  public function ensureTimestampValue($value) {
    return !empty($value) && !is_numeric($value) ? strtotime($value) : (int) $value;
  }

  /**
   * Check if a field is supported by this plugin.
   *
   * @param mixed $field
   *   A given View field.
   *
   * @return bool
   *   Wether or not the field is supported in Calendar View.
   */
  public function isDateField($field) {
    $definition = NULL;

    if ($field instanceof EntityField) {
      $entity_type_id = $field->configuration['entity_type'] ?? NULL;
      $field_name = $field->configuration['entity field'] ?? $field->configuration['field_name'] ?? NULL;
      $field_storages = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      $definition = $field_storages[$field_name] ?? NULL;
    }

    return !$definition ? FALSE : in_array($definition->getType(), self::DATE_FIELD_TYPES);
  }

  /**
   * A (not so) scientific method to get the list of days of the week.
   *
   * Core provides a DateHelper already but with no way to set the first day.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   The list of days, keyed by their number.
   *
   * @see \Drupal\Core\Datetime\DateHelper::weekDaysOrdered();
   */
  public function getOrderedDays() {
    // Avoid unnecessary calls with static variable.
    $days = &drupal_static(__METHOD__);
    if (isset($days)) {
      return $days;
    }

    $days = [
      0 => $this->t('Sunday'),
      1 => $this->t('Monday'),
      2 => $this->t('Tuesday'),
      3 => $this->t('Wednesday'),
      4 => $this->t('Thursday'),
      5 => $this->t('Friday'),
      6 => $this->t('Saturday'),
    ];

    $weekday_start = $this->options['calendar_weekday_start'] ?: $this->dateConfig->get('first_day') ?? 0;
    $weekdays = range($weekday_start, 6);
    $days = array_replace(array_flip($weekdays), $days);

    return $days;
  }

  /**
   * Retrieve all fields.
   *
   * @return array
   *   List of field, keyed by field ID.
   */
  public function getFields() {
    // Improve performance with static variables.
    $view_fields = &drupal_static(__METHOD__);
    if (isset($view_fields)) {
      return $view_fields;
    }

    $view_fields = $this->view->display_handler->getHandlers('field') ?? [];
    return $view_fields;
  }

  /**
   * Retrieve all Date fields.
   *
   * @return array
   *   List of View field plugin, keyed by their name.
   */
  public function getDateFields() {
    // Improve performance with static variables.
    $date_fields = &drupal_static(__METHOD__);
    if (isset($date_fields)) {
      return $date_fields;
    }

    $date_fields = array_filter($this->view->display_handler->getHandlers('field'), function ($field) {
      return $this->isDateField($field);
    });

    return $date_fields;
  }

  /**
   * Determine timezone relative to a given date field or to the current user.
   *
   * @param \Drupal\views\Plugin\views\field\EntityField $field
   *   (optional) A given date field.
   *
   * @return string The timezone, as a string.
   */
  public function getTimezone(EntityField $field = NULL) {
    $timezone = $this->dateConfig->get('timezone')['default'];
    // Get user's timezone, if enabled.
    if ($this->dateConfig->get('timezone.user.configurable')) {
      $timezone = $this->currentUser->getTimeZone() ?: $timezone;
    }
    // Get field overridden timezone.
    if ($field && isset($field->options['settings']['timezone_override'])) {
      $timezone = $field->options['settings']['timezone_override'] ?: $timezone;
    }

    return $timezone;
  }

  /**
   * Calculate time offset between two timezones.
   *
   * @param string $time
   *   A date/time string compatible with \DateTime. It is used as the
   *   reference for computing the offset, which can vary based on the time
   *   zone rules.
   * @param string $timezone
   *   The time zone that $time is in.
   *
   * @return int
   *   The computed offset in seconds.
   *
   * @see \Drupal\datetime\Plugin\views\filter\Date::getOffset()
   */
  public function getTimezoneOffset(string $time, string $timezone) {
    $tz = new \DateTimeZone($timezone);
    return $tz->getOffset(new \DateTime($time, $tz));
  }

  /**
   * {@inheritDoc}
   */
  public function getCalendarTimestamp($use_cache = TRUE): int {
    // Avoid unnecessary calls with static variable.
    $timestamp = &drupal_static(__METHOD__);
    if (isset($timestamp) && $use_cache) {
      return $this->ensureTimestampValue($timestamp);
    }

    // Allow user to pass query string.
    // (i.e "<url>?calendar_timestamp=2022-12-31" or "<url>?calendar_timestamp=tomorrow").
    $selected_timestamp = $this->view->getExposedInput()['calendar_timestamp'] ?? NULL;

    // Get date (default: today).
    $default_timestamp = !empty($this->options['calendar_timestamp']) ? $this->options['calendar_timestamp'] : NULL;

    // Get first result's timestamp.
    $first_timestamp = NULL;
    if (empty($this->options['calendar_timestamp'])) {
      $available_date_fields = $this->getDateFields();
      $field = reset($available_date_fields) ?? NULL;
      $first_result = reset($this->view->result) ?? NULL;
      if ($first_result instanceof ResultRow && $field instanceof EntityField) {
        $row_values = $this->getRowValues($first_result, $field);
        $first_timestamp = $row_values['value'] ?? NULL;
      }
    }

    $timestamp = $selected_timestamp ?? $default_timestamp ?? $first_timestamp ?? date('U');

    return $this->ensureTimestampValue($timestamp);
  }

  /**
   * Helper to render the message when no fields available.
   *
   * @return array
   *   The message as render array.
   */
  public function getOutputNoFields() {
    $view_edit_url = Url::fromRoute('entity.view.edit_form', ['view' => $this->view->id()]);

    $build = [];

    $build['#markup'] = $this->t('Missing calendar field.');
    $build['#markup'] .= '<br>';
    $build['#markup'] .= $this->t('Please select at least one field in the @link.', [
      '@link' => Link::fromTextAndUrl(
        $this->t('Calendar View settings'),
        $view_edit_url,
      )->toString(),
    ]);

    $build['#access'] = $view_edit_url->access();

    return $build;
  }

  /**
   * Render array for a table cell.
   *
   * @param int $timestamp
   *   A given UNIX timestamp.
   * @param array $children
   *   A given list of children elements.
   *
   * @return array
   *   A cell content, as a render array.
   */
  public function getCell(int $timestamp, array $children = []) {
    $cell = [];
    $cell['data'] = [
      '#theme' => 'calendar_view_day',
      '#timestamp' => $timestamp,
      '#children' => $children,
      '#view' => $this->view,
    ];

    $cell['data-calendar-view-day'] = date('d', $timestamp);
    $cell['data-calendar-view-month'] = date('m', $timestamp);
    $cell['data-calendar-view-year'] = date('y', $timestamp);

    $relation = (date('Ymd', $timestamp) <=> date('Ymd'));
    $cell['class'][] = $relation === 0 ? 'today' : ($relation === 1 ? 'future' : 'past');

    if ($relation === 0) {
      $cell['data-calendar-view-today'] = TRUE;
    }

    $cell['class'][] = strtolower(
      $this->getOrderedDays()[date('w', $timestamp)]->getUntranslatedString()
    );

    return $cell;
  }

  /**
   * Get default options, statically.
   *
   * @return array
   *   The value list.
   */
  public static function getDefaultOptions() {
    return [
      'calendar_fields' => [],
      'calendar_display_rows' => 0,
      // Start on Monday by default.
      'calendar_weekday_start' => 1,
      'calendar_sort_order' => 'ASC',
      'calendar_timestamp' => 'this month',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $defaults = self::getDefaultOptions();
    foreach ($defaults as $key => $value) {
      $options[$key] = ['default' => $value];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['calendar_display_rows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display default View results'),
      '#description' => $this->t('If selected, View results rows are also display along the calendar.'),
      '#default_value' => $this->options['calendar_display_rows'] ?? 0,
    ];

    $date_fields = $this->getDateFields();
    $date_fields_keys = array_keys($date_fields);
    $default_date_field = [reset($date_fields_keys)];

    $form['calendar_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Date fields'),
      '#empty_option' => $this->t('- Select -'),
      '#options' => array_combine($date_fields_keys, $date_fields_keys),
      '#default_value' => $this->options['calendar_fields'] ?? $default_date_field,
      '#disabled' => empty($date_fields),
    ];
    if (empty($date_fields)) {
      $form['calendar_fields']['#description'] = $this->t('Add a date field in <em>fields</em> on this View and edit this setting again to activate the Calendar.');
    }

    $form['calendar_weekday_start'] = [
      '#type' => 'select',
      '#title' => $this->t('Start week on:'),
      '#options' => [
        1 => t('Monday'),
        2 => t('Tuesday'),
        3 => t('Wednesday'),
        4 => t('Thursday'),
        5 => t('Friday'),
        6 => t('Saturday'),
        0 => t('Sunday'),
      ],
      '#default_value' => $this->options['calendar_weekday_start'] ?? NULL,
      '#empty_option' => $this->t("Use site's default"),
    ];

    $form['calendar_timestamp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default date'),
      '#description' => $this->t('Default starting date of this calendar, in any machine readable format.') . '<br>' .
      $this->t('Leave empty to use the date of the first result out of the first selected Date filter above.') . '<br>' .
      $this->t('NB: The first result is controlled by the <em>@sort_order</em> on this View.', [
        '@sort_order' => $this->t('Sort order'),
      ]),
      '#default_value' => $this->options['calendar_timestamp'] ?? 'this month',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function preRender($results) {
    parent::preRender($results);

    // Build calendars.
    $this->view->calendars = $this->buildCalendars($this->getCalendarTimestamp());

    // Build calendar by fields.
    $available_date_fields = $this->getDateFields();
    $calendar_fields = $this->options['calendar_fields'] ?? [];
    $calendar_fields = array_filter($calendar_fields, function ($field_name) use ($available_date_fields) {
      return ($field_name !== 0) && isset($available_date_fields[$field_name]);
    });

    // Stop now if no field selected.
    if (empty($calendar_fields)) {
      $output = $this->getOutputNoFields();
      $this->view->calendars = [$output];
      $this->view->calendar_error = TRUE;
      return;
    }

    // Populate calendars.
    foreach ($results as $result) {
      foreach ($calendar_fields as $field_id) {
        $field = $available_date_fields[$field_id] ?? NULL;
        if (!$field instanceof EntityField) {
          continue;
        }

        $row_values = $this->getRowValues($result, $field);
        $this->populateCalendar($result, $row_values);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function render() {
    if (!isset($this->view->calendars)) {
      $this->view->calendars = [];
    }

    $cache_tags = $this->view->getCacheTags() ?? [];

    foreach (Element::children($this->view->calendars) as $i) {
      // Add default cache tags to Calendars.
      $calendar = &$this->view->calendars[$i];
      $calendar['#cache']['contexts'] = ['url.query_args:calendar_timestamp'];
      $calendar['#cache']['tags'] = $cache_tags;

      // Inject helpful variables for template suggestions.
      // @see calendar_view_theme_suggestions_table_alter()
      $calendar['#attributes'] = $calendar['#attributes'] ?? [];
      $calendar['#attributes']['data-calendar-view'] = $this->getPluginId();
      $calendar['#attributes']['data-calendar-view-view-id'] = $this->view->id();
      $calendar['#attributes']['data-calendar-view-view-display'] = $this->view->current_display;
      // Reorder attributes for a cleaner rendering.
      ksort($calendar['#attributes']);
    }

    return parent::render();
  }

  /**
   * Get the value out of a view Result for a given date field.
   *
   * @param \Drupal\views\ResultRow $result
   *   A given view result.
   * @param \Drupal\views\Plugin\views\field\EntityField $field
   *   A given date field.
   *
   * @return array
   *   Either the timestamp or nothing.
   */
  public function getRowValues(ResultRow $row, EntityField $field) {
    $delta = 0;
    if ($delta_field = $field->aliases['delta'] ?? NULL) {
      $delta = $row->{$delta_field} ?? 0;
    }

    // Get the result we need from the entity.
    $this->view->row_index = $row->index ?? 0;
    $items = $field->getItems($row) ?? [];
    $item = $items[$delta]['raw'] ?? $items[0]['raw'] ?? NULL;
    $values = $item instanceof FieldItemInterface ? $item->getValue() : [];
    unset($this->view->row_index);

    // Skip empty fields.
    if (empty($values) || empty($values['value'])) {
      return [];
    }

    // Make sure values are timestamps.
    $values['value'] = $this->ensureTimestampValue($values['value']);
    $values['end_value'] = ($this->ensureTimestampValue($values['end_value'] ?? $values['value']));

    // Get offset to fix start/end datetime values.
    $timezone = $this->getTimezone($field);
    $same_tz = date_default_timezone_get() == $timezone;
    $offset = $same_tz ? 0 : $this->getTimezoneOffset('now', $timezone);
    $values['value'] += $offset;
    $values['end_value'] += $offset;

    // Get first item value to reorder multiday events in cells.
    $all_values = $field->getValue($row);
    $all_values = \is_array($all_values) ? $all_values : [$all_values];
    $first_value = reset($all_values);

    // Transform ISO8601 to timestamp.
    if (!ctype_digit($first_value)) {
      $first_instance_date = new DateTimePlus($first_value);
      $first_value = $first_instance_date->getTimestamp();
    }

    $values['first_instance'] = (int) $first_value;

    // Expose the date field if other modules need it in preprocess.
    $config = $field->configuration ?? [];
    $field_id = $config['field_name'] ?? $config['entity field'] ?? $config['id'] ?? NULL;
    $values['field'] = $field_id;

    // Get a unique identifier for this event.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $field->getEntity($row);
    $key = $entity->getEntityTypeId() . ':' . $entity->id() . ':' . $field_id;
    $values['hash'] = md5($key . $delta);

    // Prepare a title by default (e.g. on hover).
    $start = $values['value'];
    $end = $values['end_value'] ?? $start;
    $title_string = $start && ($start !== $end) ? '@title from @start to @end (@timezone)' : '@field: @date (@timezone)';

    $values['title'] = $this->t($title_string, [
      '@title' => $entity->label(),
      '@field' => $field->label() ?: ($field->configuration['title'] ?? $this->t('Date')),
      '@date' => $this->dateFormatter->format($start, 'long'),
      '@start' => $this->dateFormatter->format($start, 'short'),
      '@end' => $this->dateFormatter->format($end, 'short'),
      '@timezone' => $timezone,
    ]);

    return $values;
  }

  /**
   * Fill calendar with View results.
   *
   * @param \Drupal\views\ResultRow $result
   *   A given view result.
   * @param int $row_timestamp
   *   (optional) The timestamp value of this result.
   */
  public function populateCalendar(ResultRow $result, array $values = []) {
    // Skip empty rows.
    if (empty($values)) {
      return;
    }

    $start = $values['value'] ?? NULL;
    if (empty($start)) {
      return;
    }

    /** @var \Drupal\Core\Datetime\DrupalDateTime $now */
    $now = new DrupalDateTime('', $this->getTimezone());

    $start_day = clone $now;
    $start_day->setTimestamp($start);
    $start_day->setTime(0, 0, 0);

    $end = $values['end_value'] ?? $start;
    $end_day = clone $now;
    $end_day->setTimestamp($end);
    $end_day->setTime(0, 0, 0);

    $interval = $start_day->diff($end_day);
    $instances = $interval->format('%a');
    $values['instances'] = $instances;

    $timestamps = [];
    $day = clone $start_day;
    for ($i = 0; $i <= $instances; $i++) {
      $timestamps[] = $day->getTimestamp();
      $day->modify('+1 day');
    }

    // Render row and insert content in cell.
    // @see template_preprocess_calendar_view_day()
    $renderable_row = $this->view->rowPlugin->render($result);

    $this->view->calendars = $this->view->calendars ?? [];
    foreach (Element::children($this->view->calendars) as $i) {
      $table = &$this->view->calendars[$i];
      foreach ($table['#rows'] as $r => $rows) {
        foreach (array_keys($rows['data']) as $timestamp) {
          if (in_array($timestamp, $timestamps)) {
            $today = clone $now;
            $today->setTimestamp($timestamp);
            $today->setTime(0, 0, 0);

            $interval = $start_day->diff($today);
            $values['instance'] = $interval->format('%a');
            $renderable_row['#values'] = $values;

            $cell = &$table['#rows'][$r]['data'][$timestamp];
            $cell['data']['#children'][$start][] = $renderable_row;
          }
        }
      }
    }
  }

  /**
   * Make filter date values relative to the calendar's timestamp.
   */
  public function makeFilterValuesRelative() {
    $display_id = $this->view->current_display;
    $timestamp = $this->getCalendarTimestamp(FALSE);
    $filters = $this->view->displayHandlers->get($display_id)->getOption('filters');

    $date_fields = [];
    foreach ($this->getDateFields() as $field) {
      $date_fields[] = $field->realField;
    }

    foreach ($filters as $filter_id => $filter) {
      // @todo Better check date filter/fields.
      if (!in_array($filter['field'] ?? NULL, $date_fields)) {
        continue;
      }

      // Relative dates only for offset filters (e.g. `-1 week`).
      if (($filter['value']['type'] ?? NULL) !== 'offset') {
        continue;
      }

      foreach (['min', 'max', 'value'] as $key) {
        $offset = $filter['value'][$key];
        if (empty($offset)) {
          continue;
        }

        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        $date->modify($offset);
        $relative_date = $date->format(DateTimePlus::FORMAT);
        $filters[$filter_id]['value'][$key] = $relative_date;
      }

      $filters[$filter_id]['value']['type'] = 'date';
    }

    // Update view filters with new values.
    $this->view->displayHandlers->get($display_id)->overrideOption('filters', $filters);
  }

}
