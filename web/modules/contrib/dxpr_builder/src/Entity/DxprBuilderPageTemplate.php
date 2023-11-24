<?php

namespace Drupal\dxpr_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\dxpr_builder\DxprBuilderPageTemplateInterface;

/**
 * Defines the dxpr builder page template entity type.
 *
 * @ConfigEntityType(
 *   id = "dxpr_builder_page_template",
 *   label = @Translation("DXPR Builder Page Template"),
 *   label_collection = @Translation("DXPR Builder Page Templates"),
 *   label_singular = @Translation("DXPR Builder Page Template"),
 *   label_plural = @Translation("DXPR Builder Page Templates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count DXPR Builder Page Template",
 *     plural = "@count DXPR Builder Page Templates",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\dxpr_builder\DxprBuilderPageTemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dxpr_builder\Form\DxprBuilderPageTemplateForm",
 *       "edit" = "Drupal\dxpr_builder\Form\DxprBuilderPageTemplateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "page_template",
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "collection" = "/admin/config/content/dxpr_builder/page_template",
 *     "add-form" = "/admin/config/content/dxpr_builder/page_template/add",
 *     "edit-form" = "/admin/config/content/dxpr_builder/page_template/{dxpr_builder_page_template}",
 *     "delete-form" = "/admin/config/content/dxpr_builder/page_template/{dxpr_builder_page_template}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "category" = "category",
 *     "template" = "template",
 *     "weight" = "weight",
 *     "image" = "image",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class DxprBuilderPageTemplate extends ConfigEntityBase implements DxprBuilderPageTemplateInterface {

  /**
   * The page template ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The page template label.
   *
   * @var string
   */
  protected $label;

  /**
   * The page template category.
   *
   * @var string
   */
  protected $category;

  /**
   * The page template body.
   *
   * @var string
   */
  protected $template;

  /**
   * The page template weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * An array of data about the page template image.
   *
   * @var string
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  public function getImageData() {
    if ($this->image) {
      return 'data:image;base64, ' . $this->image;
    }
    return FALSE;
  }

}
