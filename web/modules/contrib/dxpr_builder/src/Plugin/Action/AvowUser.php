<?php

namespace Drupal\dxpr_builder\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Provides a "Restore DXPR Builder editing" action.
 *
 * Restores the DXPR Builder editing permission for the selected user(s).
 *
 * @Action(
 *   id = "dxpr_builder_avow_user",
 *   label = @Translation("Restore DXPR Builder editing permission for the selected user(s)"),
 *   type = "user",
 *   category = @Translation("DXPR Builder")
 * )
 */
class AvowUser extends FieldUpdateActionBase {

  /**
   * Gets an array of values to be set.
   *
   * @phpstan-return array<string, int>
   */
  protected function getFieldsToUpdate() {
    return [
      'dxpr_user_is_disavowed' => 0,
    ];
  }

}
