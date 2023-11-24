<?php

namespace Drupal\dxpr_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\dxpr_builder\DxprBuilderProfileInterface;

/**
 * Defines the dxpr builder profile entity type.
 *
 * @ConfigEntityType(
 *   id = "dxpr_builder_profile",
 *   label = @Translation("DXPR Builder Profile"),
 *   handlers = {
 *     "list_builder" = "Drupal\dxpr_builder\DxprBuilderProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dxpr_builder\Form\DxprBuilderProfileForm",
 *       "edit" = "Drupal\dxpr_builder\Form\DxprBuilderProfileForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "dxpr_builder_profile",
 *   admin_permission = "administer dxpr_builder_profile",
 *   links = {
 *     "collection" = "/admin/config/content/dxpr_builder/profile",
 *     "add-form" = "/admin/config/content/dxpr_builder/profile/add",
 *     "edit-form" = "/admin/config/content/dxpr_builder/profile/{dxpr_builder_profile}",
 *     "delete-form" = "/admin/config/content/dxpr_builder/profile/{dxpr_builder_profile}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "status",
 *     "dxpr_editor",
 *     "weight",
 *     "roles",
 *     "elements",
 *     "blocks",
 *     "views",
 *     "page_templates",
 *     "user_templates",
 *     "inline_buttons",
 *     "modal_buttons"
 *   }
 * )
 */
class DxprBuilderProfile extends ConfigEntityBase implements DxprBuilderProfileInterface {

  /**
   * The profile ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The profile label.
   *
   * @var string
   */
  protected $label;

  /**
   * The profile status.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * DXPR Editor state.
   *
   * @var bool
   */
  protected $dxpr_editor = TRUE;

  /**
   * Profile weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The profile roles.
   *
   * @var string[]
   */
  protected $roles = [];

  /**
   * The profile elements.
   *
   * @var string[]
   */
  protected $elements = [];

  /**
   * The profile blocks.
   *
   * @var string[]
   */
  protected $blocks = [];

  /**
   * The profile views.
   *
   * @var string[]
   */
  protected $views = [];

  /**
   * The profile page templates.
   *
   * @var string[]
   */
  protected $page_templates = [];

  /**
   * The profile user templates.
   *
   * @var string[]
   */
  protected $user_templates = [];

  /**
   * The profile buttons (inline mode).
   *
   * @var string[]
   */
  protected $inline_buttons = [];

  /**
   * The profile modal buttons.
   *
   * @var string[]
   */
  protected $modal_buttons = [];

  /**
   * Loads the first profile available for specified roles.
   *
   * @phpstan-param array<string, mixed> $roles
   *   The roles.
   *
   * @phpstan-return \Drupal\dxpr_builder\DxprBuilderProfileInterface|null
   *   User profile or empty array.
   */
  public static function loadByRoles(array $roles): ?DxprBuilderProfileInterface {
    $profiles = DxprBuilderProfile::loadMultiple();
    uasort($profiles, [DxprBuilderProfile::class, 'sort']);

    foreach ($profiles as $profile) {
      if ($profile->status && array_intersect($profile->get('roles'), $roles)) {
        return $profile;
      }
    }

    return NULL;
  }

}
