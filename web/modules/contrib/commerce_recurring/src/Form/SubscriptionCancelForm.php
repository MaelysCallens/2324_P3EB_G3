<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for cancelling a subscription.
 *
 * @internal
 */
class SubscriptionCancelForm extends ContentEntityConfirmFormBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel the %label subscription?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Keep subscription');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_subscription.edit_form', [
      'commerce_subscription' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $subscription = $this->entity;
    assert($subscription instanceof SubscriptionInterface);

    // Check if the subscription is already canceled.
    if ($subscription->getState()->getId() === 'canceled') {
      return [
        'description' => [
          '#markup' => $this->t('The subscription has already been canceled.'),
        ],
      ];
    }

    if ($current_billing_period = $subscription->getCurrentBillingPeriod()) {
      $end_date = $current_billing_period->getEndDate()->getTimestamp();
      $end_date = $this->dateFormatter->format($end_date, 'long');

      $form['cancel_option'] = [
        '#type' => 'radios',
        '#title' => $this->t('Cancellation options'),
        '#options' => [
          'scheduled' => $this->t('End of the current billing period (@end_date)', [
            '@end_date' => $end_date,
          ]),
          'now' => $this->t('Immediately'),
        ],
        '#default_value' => 'scheduled',
        '#weight' => -10,
      ];

      // Disable the 'scheduled' option if one has already been scheduled.
      if ($subscription->hasScheduledChange('state', 'canceled')) {
        $form['cancel_option']['scheduled']['#disabled'] = TRUE;
        $form['cancel_option']['#default_value'] = 'now';
        $form['cancel_option']['#description'] = $this->t('A cancellation has already been scheduled for @end_date.', [
          '@end_date' => $end_date,
        ]);
      }
    }
    else {
      if ($subscription->hasScheduledChange('state', 'canceled')) {
        $form['description'] = [
          '#markup' => $this->t('A cancellation has already been scheduled for this subscription.'),
        ];

        $form['actions']['submit']['#value'] = $this->t('Cancel immediately');
        $form['actions']['cancel']['#title'] = $this->t('Keep existing cancellation schedule');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subscription = $this->entity;
    assert($subscription instanceof SubscriptionInterface);

    $scheduled = $form_state->getValue('cancel_option') === 'scheduled';
    $subscription->cancel($scheduled);
    $subscription->save();

    if ($scheduled) {
      $this->messenger()->addMessage($this->t('The subscription has been scheduled for cancellation.'));
    }
    else {
      $this->messenger()->addMessage($this->t('The subscription has been canceled.'));
    }

    $form_state->setRedirect('entity.commerce_subscription.edit_form', [
      'commerce_subscription' => $subscription->id(),
    ]);
  }

}
