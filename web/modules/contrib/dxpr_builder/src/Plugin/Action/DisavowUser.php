<?php

namespace Drupal\dxpr_builder\Plugin\Action;

use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Provides a "Disable DXPR Builder editing" action.
 *
 * Disables the DXPR Builder editing permission for the selected user(s).
 *
 * @Action(
 *   id = "dxpr_builder_disavow_user",
 *   label = @Translation("Disable DXPR Builder editing permission for the selected user(s)"),
 *   type = "user",
 *   category = @Translation("DXPR Builder")
 * )
 */
class DisavowUser extends FieldUpdateActionBase {

  /**
   * Gets an array of values to be set.
   *
   * @phpstan-return array<string, int>
   */
  protected function getFieldsToUpdate() {
    return [
      'dxpr_user_is_disavowed' => 1,
    ];
  }

}
