<?php

namespace Drupal\commerce_recurring\Plugin\Field\FieldWidget;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_payment\PaymentOption;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_recurring_payment_method' widget.
 *
 * @FieldWidget(
 *   id = "commerce_recurring_payment_method",
 *   label = @Translation("Payment method"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class PaymentMethodWidget extends EntityReferenceAutocompleteWidget {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $initial_element = $element;
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $items->getEntity();

    // When adding a subscription we don't yet know the customer ID, so we have
    // to allow all stored payment methods.
    if (!$subscription->isNew()) {
      $options = $this->buildOptions($subscription);

      array_walk($options, function (&$option) {
        $option = $option->getLabel();
      });

      $initial_element += [
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => $subscription->getPaymentMethodId(),
        '#required' => $this->fieldDefinition->isRequired(),
      ];

      return ['target_id' => $initial_element];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['target_id'];
  }

  /**
   * Builds the payment options for the given subscription.
   *
   * @param \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription
   *   A subscription entity.
   *
   * @return \Drupal\commerce_payment\PaymentOption[]
   *   The payment options, keyed by option ID.
   */
  protected function buildOptions(SubscriptionInterface $subscription) {
    $customer = $subscription->getCustomer();

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $payment_gateways = $payment_gateway_storage->loadByProperties(['status' => TRUE]);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways_with_payment_methods */
    $payment_gateways_with_payment_methods = array_filter($payment_gateways, function ($payment_gateway) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      return $payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface;
    });

    $options = [];
    // 1) Add options to reuse stored payment methods for known customers.
    if ($customer->isAuthenticated()) {
      $billing_countries = $subscription->getStore()->getBillingCountries();
      /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
      $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');

      foreach ($payment_gateways_with_payment_methods as $payment_gateway) {
        $payment_methods = $payment_method_storage->loadReusable($customer, $payment_gateway, $billing_countries);

        foreach ($payment_methods as $payment_method_id => $payment_method) {
          $options[$payment_method_id] = new PaymentOption([
            'id' => $payment_method_id,
            'label' => $payment_method->label(),
            'payment_gateway_id' => $payment_gateway->id(),
            'payment_method_id' => $payment_method_id,
          ]);
        }
      }
    }

    // 2) Add the order's payment method if it was not included above.
    if ($subscription_payment_method = $subscription->getPaymentMethod()) {
      $subscription_payment_method_id = $subscription_payment_method->id();
      // Make sure that the payment method's gateway is still available.
      $payment_gateway_id = $subscription_payment_method->getPaymentGatewayId();
      $payment_gateway_ids = EntityHelper::extractIds($payment_gateways_with_payment_methods);

      if (in_array($payment_gateway_id, $payment_gateway_ids) && !isset($options[$subscription_payment_method_id])) {
        $options[$subscription_payment_method_id] = new PaymentOption([
          'id' => $subscription_payment_method_id,
          'label' => $subscription_payment_method->label(),
          'payment_gateway_id' => $subscription_payment_method->getPaymentGatewayId(),
          'payment_method_id' => $subscription_payment_method_id,
        ]);
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_subscription' && $field_name == 'payment_method';
  }

}
