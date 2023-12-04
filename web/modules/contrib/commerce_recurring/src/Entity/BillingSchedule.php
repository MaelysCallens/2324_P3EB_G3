<?php

namespace Drupal\commerce_recurring\Entity;

use Drupal\commerce\CommerceSinglePluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the billing schedule entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_billing_schedule",
 *   label = @Translation("Billing schedule"),
 *   label_collection = @Translation("Billing schedules"),
 *   label_singular = @Translation("billing schedule"),
 *   label_plural = @Translation("billing schedules"),
 *   label_count = @PluralTranslation(
 *     singular = "@count billing schedule",
 *     plural = "@count billing schedules",
 *   ),
 *   handlers = {
 *     "list_builder" = "\Drupal\commerce_recurring\BillingScheduleListBuilder",
 *     "storage" = "\Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "access" = "Drupal\commerce_recurring\BillingScheduleAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "form" = {
 *       "add" = "\Drupal\commerce_recurring\Form\BillingScheduleForm",
 *       "edit" = "\Drupal\commerce_recurring\Form\BillingScheduleForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_billing_schedule",
 *   config_prefix = "commerce_billing_schedule",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "displayLabel",
 *     "billingType",
 *     "combineSubscriptions",
 *     "retrySchedule",
 *     "unpaidSubscriptionState",
 *     "plugin",
 *     "configuration",
 *     "prorater",
 *     "proraterConfiguration",
 *     "status",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/billing-schedules/add",
 *     "edit-form" = "/admin/commerce/config/billing-schedules/manage/{commerce_billing_schedule}",
 *     "delete-form" = "/admin/commerce/config/billing-schedules/manage/{commerce_billing_schedule}/delete",
 *     "collection" =  "/admin/commerce/config/billing-schedules"
 *   }
 * )
 */
class BillingSchedule extends ConfigEntityBase implements BillingScheduleInterface {

  /**
   * The billing schedule ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The billing schedule label.
   *
   * @var string
   */
  protected $label;

  /**
   * The billing schedule display label.
   *
   * @var string
   */
  protected $displayLabel;

  /**
   * The billing type.
   *
   * One of the BillingScheduleInterface::BILLING_TYPE_ constants.
   *
   * @var string
   */
  protected $billingType = self::BILLING_TYPE_PREPAID;

  /**
   * Whether to combine subscriptions sharing the same billing cycle.
   *
   * @var bool
   */
  protected $combineSubscriptions = FALSE;

  /**
   * The retry schedule.
   *
   * @var array
   */
  protected $retrySchedule = [1, 3, 5];

  /**
   * The unpaid subscription state.
   *
   * @var string
   */
  protected $unpaidSubscriptionState = 'canceled';

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The prorater plugin ID.
   *
   * @var string
   */
  protected $prorater = 'proportional';

  /**
   * The prorater plugin configuration.
   *
   * @var array
   */
  protected $proraterConfiguration = [];

  /**
   * The plugin collection that holds the billing schedule plugin.
   *
   * @var \Drupal\commerce\CommerceSinglePluginCollection
   */
  protected $billingSchedulePluginCollection;

  /**
   * The plugin collection that holds the prorater plugin.
   *
   * @var \Drupal\commerce\CommerceSinglePluginCollection
   */
  protected $proraterPluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->displayLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayLabel($display_label) {
    $this->displayLabel = $display_label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBillingType() {
    return $this->billingType;
  }

  /**
   * {@inheritdoc}
   */
  public function setBillingType($billing_type) {
    if (!in_array($billing_type, [self::BILLING_TYPE_PREPAID, self::BILLING_TYPE_POSTPAID])) {
      throw new \InvalidArgumentException(sprintf('Invalid billing type "%s" provided.'));
    }
    $this->billingType = $billing_type;

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function getRetrySchedule() {
    return $this->retrySchedule;
  }

  /**
   * @inheritdoc
   */
  public function setRetrySchedule(array $schedule) {
    $this->retrySchedule = $schedule;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnpaidSubscriptionState() {
    return $this->unpaidSubscriptionState;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpaidSubscriptionState($state) {
    $this->unpaidSubscriptionState = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->configuration = [];
    $this->billingSchedulePluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->billingSchedulePluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getBillingSchedulePluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getProraterId() {
    return $this->prorater;
  }

  /**
   * {@inheritdoc}
   */
  public function setProraterId($prorater_id) {
    $this->prorater = $prorater_id;
    $this->proraterConfiguration = [];
    $this->proraterPluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProraterConfiguration() {
    return $this->proraterConfiguration;
  }

  /**
   * {@inheritdoc}
   */
  public function setProraterConfiguration(array $configuration) {
    $this->proraterConfiguration = $configuration;
    $this->proraterPluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProrater() {
    return $this->getProraterPluginCollection()->get($this->prorater);
  }

  /**
   * {@inheritdoc}
   */
  public function allowCombiningSubscriptions() {
    return $this->combineSubscriptions;
  }

  /**
   * {@inheritdoc}
   */
  public function setCombineSubscriptions($combine) {
    $this->combineSubscriptions = (bool) $combine;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getBillingSchedulePluginCollection(),
      'proraterConfiguration' => $this->getProraterPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Invoke the setters to clear related properties.
    if ($property_name === 'plugin') {
      $this->setPluginId($value);
    }
    elseif ($property_name === 'configuration') {
      $this->setPluginConfiguration($value);
    }
    else {
      return parent::set($property_name, $value);
    }
  }

  /**
   * Gets the plugin collection that holds the billing schedule plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce\CommerceSinglePluginCollection
   *   The plugin collection.
   */
  protected function getBillingSchedulePluginCollection() {
    if (!$this->billingSchedulePluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_billing_schedule');
      $this->billingSchedulePluginCollection = new CommerceSinglePluginCollection($plugin_manager, $this->plugin, $this->configuration, $this->id);
    }
    return $this->billingSchedulePluginCollection;
  }

  /**
   * Gets the plugin collection that holds the prorater plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce\CommerceSinglePluginCollection
   *   The plugin collection.
   */
  protected function getProraterPluginCollection() {
    if (!$this->proraterPluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_prorater');
      $this->proraterPluginCollection = new CommerceSinglePluginCollection($plugin_manager, $this->prorater, $this->proraterConfiguration, $this->id);
    }
    return $this->proraterPluginCollection;
  }

}
