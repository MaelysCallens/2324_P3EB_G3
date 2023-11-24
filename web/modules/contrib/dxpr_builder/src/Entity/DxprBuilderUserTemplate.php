<?php

namespace Drupal\dxpr_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the dxpr builder user template entity type.
 *
 * @ConfigEntityType(
 *   id = "dxpr_builder_user_template",
 *   label = @Translation("DXPR Builder User Templates"),
 *   handlers = {
 *     "list_builder" = "Drupal\dxpr_builder\DxprBuilderUserTemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dxpr_builder\Form\DxprBuilderUserTemplateForm",
 *       "edit" = "Drupal\dxpr_builder\Form\DxprBuilderUserTemplateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "user_template",
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "collection" = "/admin/config/content/dxpr_builder/user_templates",
 *     "add-form" = "/admin/config/content/dxpr_builder/user_templates/add",
 *     "edit-form" = "/admin/config/content/dxpr_builder/user_templates/{dxpr_builder_user_template}",
 *     "delete-form" = "/admin/config/content/dxpr_builder/user_templates/{dxpr_builder_user_template}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "template" = "template",
 *     "uid" = "uid",
 *     "global" = "global",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class DxprBuilderUserTemplate extends ConfigEntityBase {

  /**
   * The user template ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The user template label.
   *
   * @var string
   */
  protected $label;

  /**
   * The user template body.
   *
   * @var string
   */
  protected $template;

  /**
   * The user template user id.
   *
   * @var int
   */
  protected $uid;

  /**
   * The user template state.
   *
   * @var bool
   */
  protected $global = TRUE;

}
