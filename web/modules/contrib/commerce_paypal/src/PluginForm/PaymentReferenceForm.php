<?php

namespace Drupal\commerce_paypal\PluginForm;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\commerce_price\Price;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for the payment transaction reference.
 */
class PaymentReferenceForm extends PaymentGatewayFormBase implements ContainerInjectionInterface {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new PaymentReferenceForm object.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   Currency formatter service.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter) {
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $form['#success_message'] = $this->t('Reference transaction processed successfully.');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    $balance = $order->getBalance();
    $formatted_balance = $this->currencyFormatter->format($balance->getNumber(), $balance->getCurrencyCode());

    $description = $this->t('Order balance: @balance', [
      '@balance' => $formatted_balance,
    ]);

    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Capture amount'),
      '#default_value' => $payment->getBalance()->toArray(),
      '#required' => TRUE,
      '#available_currencies' => [$payment->getAmount()->getCurrencyCode()],
      '#description' => $description,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    // If the transaction has expired, display an error message and redirect.
    if ($payment->getCompletedTime() &&
      $payment->getCompletedTime() < strtotime('-365 days')) {
      $form_state->setError($form, $this->t('This transaction has passed its 365 day limit and can no longer be used for reference transactions.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = Price::fromArray($values['amount']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\PayflowLinkInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->referencePayment($payment, $amount);
  }

}
