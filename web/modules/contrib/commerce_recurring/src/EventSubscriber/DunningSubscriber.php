<?php

namespace Drupal\commerce_recurring\EventSubscriber;

use Drupal\commerce_recurring\Event\PaymentDeclinedEvent;
use Drupal\commerce_recurring\Event\RecurringEvents;
use Drupal\commerce_recurring\Mail\PaymentDeclinedMailInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends an email when the payment is declined for a recurring order.
 */
class DunningSubscriber implements EventSubscriberInterface {

  /**
   * The payment declined mail service.
   *
   * @var \Drupal\commerce_recurring\Mail\PaymentDeclinedMailInterface
   */
  protected $paymentDeclinedMail;

  /**
   * Constructs a new DunningSubscriber object.
   *
   * @param \Drupal\commerce_recurring\Mail\PaymentDeclinedMailInterface $payment_declined_mail
   *   The payment declined mail service.
   */
  public function __construct(PaymentDeclinedMailInterface $payment_declined_mail) {
    $this->paymentDeclinedMail = $payment_declined_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [RecurringEvents::PAYMENT_DECLINED => ['sendPaymentDeclinedEmail', -100]];
    return $events;
  }

  /**
   * Sends a payment declined email.
   *
   * @param \Drupal\commerce_recurring\Event\PaymentDeclinedEvent $event
   *   The event we subscribed to.
   */
  public function sendPaymentDeclinedEmail(PaymentDeclinedEvent $event) {
    if (empty($event->getOrder()->getEmail())) {
      return;
    }
    $this->paymentDeclinedMail->send($event->getOrder(), $event->getRetryDays(), $event->getNumRetries(), $event->getMaxRetries());
  }

}
