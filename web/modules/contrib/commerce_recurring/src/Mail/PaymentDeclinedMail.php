<?php

namespace Drupal\commerce_recurring\Mail;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Sends an email when the payment is declined for a recurring order.
 */
class PaymentDeclinedMail implements PaymentDeclinedMailInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The order total summary.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PaymentDeclinedMail object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   The mail handler.
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary
   *   The order total summary.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailHandlerInterface $mail_handler, OrderTotalSummaryInterface $order_total_summary, TimeInterface $time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailHandler = $mail_handler;
    $this->orderTotalSummary = $order_total_summary;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function send(OrderInterface $order, $retry_days, $num_retries, $max_retries) {
    $customer = $order->getCustomer();
    if ($customer->isAnonymous()) {
      return FALSE;
    }
    $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
    /** @var \Drupal\commerce_order\Entity\OrderTypeInterface $order_type */
    $order_type = $order_type_storage->load($order->bundle());
    $payment_methods_url = Url::fromRoute('entity.commerce_payment_method.collection', [
      'user' => $order->getCustomerId(),
    ], ['absolute' => TRUE]);

    $subject = $this->t('Payment declined - Order #@number.', ['@number' => $order->getOrderNumber()]);
    $body = [
      '#theme' => 'commerce_recurring_payment_declined',
      '#order_entity' => $order,
      '#retry_num' => $num_retries,
      '#retry_days' => "+$retry_days days",
      '#max_retries' => $max_retries,
      '#remaining_retries' => $max_retries - $num_retries,
      '#now' => $this->time->getCurrentTime(),
      '#payment_method_link' => $payment_methods_url->toString(),
      '#totals' => $this->orderTotalSummary->buildTotals($order),
    ];
    if ($billing_profile = $order->getBillingProfile()) {
      $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
      $body['#billing_information'] = $profile_view_builder->view($billing_profile);
    }
    $params = [
      'id' => 'recurring_payment_declined',
      'from' => $order->getStore()->getEmail(),
      'bcc' => $order_type->getReceiptBcc(),
      'order' => $order,
      'langcode' => $customer->getPreferredLangcode(),
    ];

    return $this->mailHandler->sendMail($order->getEmail(), $subject, $body, $params);
  }

}
