<?php

namespace Drupal\commerce_recurring\Form;

use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubscriptionForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new SubscriptionForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $subscription */
    $subscription = $this->entity;
    $form = parent::form($form, $form_state);

    $form['#theme'] = ['commerce_subscription_form'];
    $form['#attached']['library'][] = 'commerce_recurring/subscription_form';

    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];

    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'state' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $subscription->getState()->getLabel(),
        '#attributes' => [
          'class' => ['entity-meta__title'],
        ],
      ],
    ];

    $form['trial_date_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Trial dates'),
      '#group' => 'advanced',
    ];

    $form['date_details'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Dates'),
      '#group' => 'advanced',
    ];

    $field_details_mapping = [
      'trial_starts' => 'trial_date_details',
      'trial_ends' => 'trial_date_details',
      'starts' => 'date_details',
      'ends' => 'date_details',
    ];
    foreach ($field_details_mapping as $field => $group) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = $group;
      }
    }

    // The trial date field should be editable only when in trial mode, or
    // when the subscription is new.
    if (!$subscription->isNew() && $subscription->getState()->getId() != 'trial') {
      $form['trial_date_details']['#access'] = FALSE;

    }
    if ($trial_starts = $subscription->getTrialStartTime()) {
      $trial_starts = $this->dateFormatter->format($trial_starts, 'short');
      $form['meta']['trial_starts'] = $this->fieldAsReadOnly($this->t('Trial starts'), $trial_starts);
    }
    if ($trial_ends = $subscription->getTrialEndTime()) {
      $trial_ends = $this->dateFormatter->format($trial_ends, 'short');
      $form['meta']['trial_ends'] = $this->fieldAsReadOnly($this->t('Trial ends'), $trial_ends);
    }
    // Hide the dates if the subscription is canceled and show read-only dates
    // instead.
    if ($subscription->getState()->getId() == 'canceled') {
      $form['date_details']['#access'] = FALSE;
      if ($starts = $subscription->getStartTime()) {
        $starts = $this->dateFormatter->format($starts, 'short');
        $form['meta']['starts'] = $this->fieldAsReadOnly($this->t('Starts'), $starts);
      }
      if ($ends = $subscription->getEndTime()) {
        $ends = $this->dateFormatter->format($ends, 'short');
        $form['meta']['ends'] = $this->fieldAsReadOnly($this->t('Ends'), $ends);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $subscription = $this->entity;
    assert($subscription instanceof SubscriptionInterface);

    // No need for a Cancel button when creating a subscription.
    if (!$subscription->isNew()) {
      $actions['delete']['#weight'] = 50;
      $actions['cancel'] = [
        '#type' => 'submit',
        '#button_type' => 'danger',
        '#value' => t('Cancel subscription'),
        '#submit' => ['::cancelSubscription'],
        '#access' => $subscription->getState()->getId() !== 'canceled' && $subscription->access('cancel'),
      ];
    }

    return $actions;
  }

  /**
   * Builds a read-only form element for a field.
   *
   * @param string $label
   *   The element label.
   * @param string $value
   *   The element value.
   *
   * @return array
   *   The form element.
   */
  protected function fieldAsReadOnly($label, $value) {
    return [
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => [Html::cleanCssIdentifier(strtolower($label)), 'container-inline'],
      ],
      '#markup' => '<h4 class="label inline">' . $label . '</h4> ' . $value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // If the "trial_starts" value submitted is empty, but the trial start field
    // on the subscription isn't, this means a default value (the current date)
    // has been set by the "datetime_timestamp" widget.
    if ($form_state->getValue(['trial_starts', 0, 'value']) === NULL && !empty($this->entity->getTrialStartTime())) {
      $this->entity->set('trial_starts', NULL);
    }
    $this->entity->save();
    $this->messenger()->addMessage($this->t('A subscription been successfully saved.'));
    $form_state->setRedirect('entity.commerce_subscription.collection');
  }

  /**
   * Submit handler for canceling a subscription.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancelSubscription(array $form, FormStateInterface $form_state) {
    // Prevent ?destination from overriding our redirect.
    // @todo remove after https://www.drupal.org/project/drupal/issues/2950883
    $this->getRequest()->query->remove('destination');
    $form_state->setRedirect('entity.commerce_subscription.cancel_form', [
      'commerce_subscription' => $this->entity->id(),
    ]);
  }

}
