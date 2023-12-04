<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Provides the default billing period formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_billing_period_default",
 *   module = "commerce_recurring",
 *   label = @Translation("Billing period"),
 *   field_types = {
 *     "commerce_billing_period"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class BillingPeriodDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    /** @var \Drupal\commerce_recurring\Plugin\Field\FieldType\BillingPeriodItem $item */
    foreach ($items as $delta => $item) {
      $billing_period = $item->toBillingPeriod();
      $start_date = $billing_period->getStartDate()->format('M jS Y H:i:s');
      $end_date = $billing_period->getEndDate()->format('M jS Y H:i:s');

      $build[$delta] = [
        '#plain_text' => $start_date . ' - ' . $end_date,
      ];
    }
    return $build;
  }

}
