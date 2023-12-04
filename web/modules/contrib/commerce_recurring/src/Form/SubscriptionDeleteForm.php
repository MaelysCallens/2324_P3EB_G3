<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

class SubscriptionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = $this->t("This will also delete the subscription's related order items and orders.");
    return $description . '<br>' . parent::getDescription();
  }

}
