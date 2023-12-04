<?php

namespace Drupal\Tests\commerce_recurring\Kernel\Mail;

use Drupal\commerce_price\Price;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\commerce_recurring\Kernel\RecurringKernelTestBase;

/**
 * Tests the sending of payment declined emails.
 *
 * @coversDefaultClass \Drupal\commerce_recurring\Mail\PaymentDeclinedMail
 * @group commerce_recurring
 */
class PaymentDeclinedMailTest extends RecurringKernelTestBase {

  use AssertMailTrait;

  /**
   * The payment declined mail.
   *
   * @var \Drupal\commerce_recurring\Mail\PaymentDeclinedMail
   */
  protected $mail;

  /**
   * A sample recurring order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mail = $this->container->get('commerce_recurring.payment_declined_mail');
    $this->user->setEmail('test-recipient@example.com');
    $this->user->save();
    $subscription = Subscription::create([
      'type' => 'product_variation',
      'store_id' => $this->store->id(),
      'billing_schedule' => $this->billingSchedule,
      'uid' => $this->user,
      'payment_method' => $this->paymentMethod,
      'purchased_entity' => $this->variation,
      'title' => $this->variation->getOrderItemTitle(),
      'quantity' => '2',
      'unit_price' => new Price('2', 'USD'),
      'state' => 'active',
      'starts' => strtotime('2017-02-24 17:30:00'),
    ]);
    $subscription->save();

    $recurring_order_manager = $this->container->get('commerce_recurring.order_manager');
    $this->order = $recurring_order_manager->startRecurring($subscription);
    $this->order->setOrderNumber($this->order->id());
    $this->order->save();
  }

  /**
   * @covers ::send
   */
  public function testSend() {
    $schedule = [1, 3, 5];
    $max_retries = count($schedule);
    $time = \Drupal::time()->getCurrentTime();
    $this->mail->send($this->order, $schedule[0], 0, $max_retries);

    $emails = $this->getMails();
    $this->assertEquals(1, count($emails));
    $email = reset($emails);
    $this->assertEquals('text/html; charset=UTF-8;', $email['headers']['Content-Type']);
    $this->assertEquals('commerce_recurring_payment_declined', $email['id']);
    $this->assertEquals($this->order->getEmail(), $email['to']);
    $this->assertEquals($this->order->getStore()->getEmail(), $email['from']);
    $this->assertEquals('Payment declined - Order #1.', $email['subject']);
    $this->assertMailString('body', 'We regret to inform you that the most recent charge attempt on your card failed.', 1);
    $this->assertMailString('body', Url::fromRoute('entity.commerce_payment_method.collection', ['user' => $this->user->id()], ['absolute' => TRUE])->toString(), 1);
    $next_retry_time = strtotime("+1 day", $time);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);

    $next_retry_time = strtotime('+3 days', $time);
    $this->mail->send($this->order, $schedule[1], 1, $max_retries);
    $this->assertMailString('body', 'Our next charge attempt will be on: ' . date('F d', $next_retry_time), 1);
    $next_retry_time = strtotime('+5days', $time);
    $this->mail->send($this->order, $schedule[2], 2, $max_retries);
    $this->assertMailString('body', 'Our final charge attempt will be on: ' . date('F d', $next_retry_time), 1);
    $this->mail->send($this->order, 0, 3, $max_retries);
    $this->assertMailString('body', 'This was our final charge attempt.', 1);
  }

}
