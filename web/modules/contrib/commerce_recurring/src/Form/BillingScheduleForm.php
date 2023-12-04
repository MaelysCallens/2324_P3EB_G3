<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\commerce_recurring\ProraterManager;
use Drupal\commerce_recurring\BillingScheduleManager;
use Drupal\commerce_recurring\Entity\BillingSchedule;
use Drupal\commerce_recurring\Entity\BillingScheduleInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BillingScheduleForm extends EntityForm {

  /**
   * The billing schedule plugin manager.
   *
   * @var \Drupal\commerce_recurring\BillingScheduleManager
   */
  protected $billingSchedulePluginManager;

  /**
   * The prorater plugin manager.
   *
   * @var \Drupal\commerce_recurring\ProraterManager
   */
  protected $proraterPluginManager;

  /**
   * Constructs a new BillingScheduleForm object.
   *
   * @param \Drupal\commerce_recurring\BillingScheduleManager $billing_schedule_plugin_manager
   *   The billing schedule plugin manager.
   * @param \Drupal\commerce_recurring\ProraterManager $prorater_manager
   *   The prorater plugin manager.
   */
  public function __construct(BillingScheduleManager $billing_schedule_plugin_manager, ProraterManager $prorater_manager) {
    $this->billingSchedulePluginManager = $billing_schedule_plugin_manager;
    $this->proraterPluginManager = $prorater_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_billing_schedule'),
      $container->get('plugin.manager.commerce_prorater')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $this->entity;
    $plugins = array_column($this->billingSchedulePluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);
    $proraters = array_column($this->proraterPluginManager->getDefinitions(), 'label', 'id');
    asort($proraters);

    // Use the first available plugin as the default value.
    if (!$billing_schedule->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $billing_schedule->setPluginId($plugin);
    }
    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $billing_schedule->getPluginId());
    $prorater = $form_state->getValue('prorater', $billing_schedule->getProraterId());
    // Pass the configuration only if the plugin hasn't been changed via #ajax.
    $plugin_configuration = $billing_schedule->getPluginId() == $plugin ? $billing_schedule->getPluginConfiguration() : [];
    $prorater_configuration = $billing_schedule->getProraterId() == $prorater ? $billing_schedule->getProraterConfiguration() : [];

    $wrapper_id = Html::getUniqueId('billing-schedule-form');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $billing_schedule->label(),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $billing_schedule->id(),
      '#machine_name' => [
        'exists' => [BillingSchedule::class, 'load'],
        'source' => ['label'],
      ],
      '#disabled' => !$billing_schedule->isNew(),
    ];
    $form['displayLabel'] = [
      '#type' => 'textfield',
      '#title' => t('Display label'),
      '#description' => t('Used to identify the billing schedule on the frontend.'),
      '#default_value' => $billing_schedule->getDisplayLabel(),
      '#required' => TRUE,
    ];
    $form['billingType'] = [
      '#type' => 'radios',
      '#title' => $this->t('Billing type'),
      '#options' => [
        BillingScheduleInterface::BILLING_TYPE_PREPAID => $this->t('Prepaid'),
        BillingScheduleInterface::BILLING_TYPE_POSTPAID => $this->t('Postpaid'),
      ],
      '#default_value' => $billing_schedule->getBillingType(),
    ];
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_billing_schedule',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
    ];
    $form['prorater'] = [
      '#type' => 'radios',
      '#title' => $this->t('Prorater'),
      '#description' => $this->t('Modifies unit prices to reflect partial billing periods.'),
      '#options' => $proraters,
      '#default_value' => $prorater,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    $form['prorater_configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_prorater',
      '#plugin_id' => $prorater,
      '#default_value' => $prorater_configuration,
    ];

    $retry_schedule = $billing_schedule->getRetrySchedule();
    $retries = range(1, 8);
    $retry_labels = [
      $this->t('If the initial attempt fails, retry after'),
      $this->t('If the first retry fails, retry after'),
      $this->t('If the second retry fails, retry after'),
      $this->t('If the third retry fails, retry after'),
      $this->t('If the fourth retry fails, retry after'),
      $this->t('If the fifth retry fails, retry after'),
      $this->t('If the sixth retry fails, retry after'),
      $this->t('If the seventh retry fails, retry after'),
    ];
    $num_retries = $form_state->getValue(['dunning', 'num_retries'], count($retry_schedule));

    $form['dunning'] = [
      '#type' => 'details',
      '#title' => $this->t('Dunning'),
      '#open' => TRUE,
    ];
    $form['dunning']['help'] = [
      '#plain_text' => $this->t("Defines what should happen when a recurring order's payment fails."),
    ];
    $form['dunning']['num_retries'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of retries'),
      '#options' => array_combine($retries, $retries),
      '#default_value' => $num_retries,
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];
    for ($i = 0; $i < $num_retries; $i++) {
      $form['dunning']['retry'][$i] = [
        '#type' => 'number',
        '#title' => $retry_labels[$i],
        '#field_suffix' => $this->t('days'),
        '#default_value' => isset($retry_schedule[$i]) ? $retry_schedule[$i] : 2,
        '#min' => 1,
      ];
    }
    $form['dunning']['unpaid_subscription_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('After the final retry'),
      '#weight' => 1000,
      '#options' => [
        'active' => $this->t('Keep the subscription active'),
        'canceled' => $this->t('Cancel the subscription (non-reversible)'),
      ],
      '#default_value' => $billing_schedule->getUnpaidSubscriptionState(),
    ];

    $form['combine_subscriptions'] = [
      '#title' => $this->t('Combine subscriptions into a single recurring order'),
      '#description' => $this->t('When possible, use a common recurring order for all subscriptions sharing the same billing cycle.'),
      '#type' => 'checkbox',
      '#default_value' => $billing_schedule->allowCombiningSubscriptions(),
    ];

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        FALSE => $this->t('Disabled'),
        TRUE => $this->t('Enabled'),
      ],
      '#default_value' => $billing_schedule->status(),
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $this->entity;

    if ($billing_schedule->isNew()) {
      return $form;
    }

    $is_referenced = (boolean) $this->entityTypeManager
      ->getStorage('commerce_subscription')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('billing_schedule', $billing_schedule->id())
      ->count()
      ->execute();

    if ($is_referenced) {
      // Disable some fields when the billing shedule is already in use by subscriptions.
      $form['billingType']['#disabled'] = TRUE;
      $form['plugin']['#disabled'] = TRUE;
      $form['configuration']['#disabled'] = TRUE;
      $form['prorater']['#disabled'] = TRUE;
      $form['prorater_configuration']['#disabled'] = TRUE;

      if (empty($form_state->getUserInput())) {
        // The form is not submitted, set a message explaining why some of the
        // fields are disabled.
        $link = Link::createFromRoute('subscriptions page', 'entity.commerce_subscription.collection');
        $this->messenger()->addWarning($this->t('Some fields are disabled since the %billing_schedule billing schedule is used by subscriptions. Check the @link.', [
          '%billing_schedule' => $billing_schedule->label(),
          '@link' => $link->toString(),
        ]));
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    /** @var \Drupal\commerce_recurring\Entity\BillingScheduleInterface $billing_schedule */
    $billing_schedule = $this->entity;
    $billing_schedule->setPluginConfiguration($values['configuration']);
    $billing_schedule->setProraterConfiguration($values['prorater_configuration']);
    $billing_schedule->setRetrySchedule($values['dunning']['retry']);
    $billing_schedule->setUnpaidSubscriptionState($values['dunning']['unpaid_subscription_state']);
    $billing_schedule->setCombineSubscriptions($values['combine_subscriptions']);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage($this->t('Saved the @label billing schedule.', ['@label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_billing_schedule.collection');
  }

}
