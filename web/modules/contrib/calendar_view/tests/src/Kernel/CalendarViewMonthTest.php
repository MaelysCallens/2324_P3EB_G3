<?php

namespace Drupal\Tests\calendar_view\Kernel;

use Drupal\Tests\views\Kernel\Plugin\PluginKernelTestBase as ViewsTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Test Calendar View Month style plugin works correctly.
 *
 * @group calendar_view
 */
class CalendarViewMonthTest extends ViewsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['calendar_view_by_month_test'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'calendar_view',
    'calendar_view_test_config',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp(FALSE);

    if ($import_test_views) {
      ViewTestData::createTestViews(static::class, ['calendar_view_test_config']);
    }
  }

  /**
   * Tests the Calendar View by month style.
   */
  public function testCalendarViewByMonth() {
    $view = Views::getView('calendar_view_by_month_test');
    $this->prepareView($view);

    // Render an empty view to quickly check texts in the output.
    $view->executed = TRUE;
    $view->result = [];
    $output = $view->preview();
    $output = \Drupal::service('renderer')->renderRoot($output);

    // Test calendar_timestamp.
    $now = new \DateTime('now');
    $style_plugin = $view->style_plugin;
    $this->assertTrue($style_plugin->options['calendar_timestamp'] == 'today', 'Calendar timestamp not set up properly by default.');
    $this->assertStringContainsString('<caption>' . $now->format('F Y') . '</caption>', $output, 'Calendar timestamp not rendered properly.');
    $this->assertStringContainsString('data-calendar-view-today', $output, 'Current day not rendered properly.');

    // Test pagination.
    $pager = $view->pager;
    $previous = clone $now;
    $previous->modify('-1 month');
    $next = clone $now;
    $next->modify('+1 month');
    $this->assertTrue($pager->options['use_previous_next'] == 1, 'Month pagination not set up properly by default.');
    $this->assertStringContainsString('aria-label="Previous month, ' . $previous->format('F Y') . '"', $output, 'Link to previous month not correct.');
    $this->assertStringContainsString('aria-label="Next month, ' . $next->format('F Y') . '"', $output, 'Link to last month not correct.');

    // @todo what other tests would be useful?
  }

  /**
   * Prepares a view executable by initializing everything which is needed.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executable to prepare.
   *
   * @see \Drupal\Tests\views\Kernel\Plugin\StyleTableUnitTest
   */
  protected function prepareView(ViewExecutable $view) {
    $view->setDisplay();
    $view->initStyle();
    $view->initHandlers();
    $view->initQuery();
  }

}
