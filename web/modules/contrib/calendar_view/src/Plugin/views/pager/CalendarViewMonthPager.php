<?php

namespace Drupal\calendar_view\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "calendar_month",
 *   title = @Translation("Calendar navigation by month"),
 *   short_title = @Translation("Navigation by month"),
 *   help = @Translation("Create a navigation by month for your Calendar Views."),
 *   display_types = {"calendar"},
 *   theme = "calendar_view_pager"
 * )
 */
class CalendarViewMonthPager extends CalendarViewPagerBase {

  /**
   * {@inheritDoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['label_format']['#description'] .= '<br>' .
      '- <code>M</code>' . ' ' . $this->t('results in @output', ['@output' => 'Jan']);
  }

}
