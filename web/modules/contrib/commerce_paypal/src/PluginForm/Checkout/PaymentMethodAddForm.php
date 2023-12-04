<?php

namespace Drupal\commerce_paypal\PluginForm\Checkout;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\commerce_paypal\CustomCardFieldsBuilderInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * The custom card fields builder.
   *
   * @var \Drupal\commerce_paypal\CustomCardFieldsBuilderInterface
   */
  protected $cardFieldsBuilder;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PaymentMethodAddForm object.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\commerce_paypal\CustomCardFieldsBuilderInterface $card_fields_builder
   *   The custom card fields builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(CurrentStoreInterface $current_store, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager, LoggerInterface $logger, CustomCardFieldsBuilderInterface $card_fields_builder, RouteMatchInterface $route_match) {
    parent::__construct($current_store, $entity_type_manager, $inline_form_manager, $logger);

    $this->cardFieldsBuilder = $card_fields_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_store.current_store'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form'),
      $container->get('logger.channel.commerce_payment'),
      $container->get('commerce_paypal.custom_card_fields_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    // We need to inject the custom card fields, only when this is the solution
    // configured.
    if (!$this->shouldInjectForm($payment_method->getPaymentGateway()->getPlugin())) {
      return $form;
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->routeMatch->getParameter('commerce_order');
    $form['payment_details'] += $this->cardFieldsBuilder->build($order, $payment_method->getPaymentGateway());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_method->setReusable(FALSE);
    // When the gateway is configured to display "Smart payment buttons", the
    // buttons are not injected in the payment information pane but in the
    // "review" step, which means the payment method creation should be skipped.
    if ($this->shouldInjectForm($payment_method->getPaymentGateway()->getPlugin())) {
      parent::submitConfigurationForm($form, $form_state);
    }
    else {
      // Since we're not calling the parent submitConfigurationForm() method
      // we need to duplicate the logic for setting the billing profile.
      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $this->plugin;
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $this->entity;

      if ($payment_gateway_plugin->collectsBillingInformation()) {
        /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
        $inline_form = $form['billing_information']['#inline_form'];
        /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
        $billing_profile = $inline_form->getEntity();
        $payment_method->setBillingProfile($billing_profile);
      }
    }
  }

  /**
   * Determines whether the card fields form should be injected.
   */
  protected function shouldInjectForm(PaymentGatewayInterface $plugin) {
    return $plugin instanceof CheckoutInterface && $plugin->getPaymentSolution() === 'custom_card_fields';
  }

}
