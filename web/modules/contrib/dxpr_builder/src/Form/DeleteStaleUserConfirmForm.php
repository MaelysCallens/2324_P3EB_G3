<?php

namespace Drupal\dxpr_builder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form before remove a stale user entry.
 */
class DeleteStaleUserConfirmForm extends ConfirmFormBase {

  /**
   * The license service.
   *
   * @var ?\Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   */
  protected $licenseService;

  /**
   * The entity type manager.
   *
   * @var ?\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dxpr_builder_delete_stale_user_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this user?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The user will be removed from all the domains.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('dxpr_builder.user_licenses');
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(new Url('dxpr_builder.user_licenses'));

    $email = $this->getRequest()->query->get('email');
    $userLicenses = $this->licenseService()->getLicenseUsers();

    if (!array_key_exists($email, $userLicenses)) {
      $this->messenger()->addError($this->t('The license entry does not exist.'));
      return;
    }

    $uid = $this->entityTypeManager()->getStorage('user')
      ->getQuery()
      ->condition('mail', $email)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($uid)) {
      $this->messenger()->addError($this->t('The user with the provided email exists in the system.'));
      return;
    }

    $this->licenseService()->removeMailFromCentralStorage($email);
    $this->licenseService()->processSyncQueue();
    $this->messenger()->addStatus($this->t('The user has been removed.'));
  }

  /**
   * Returns the license service.
   *
   * @return \Drupal\dxpr_builder\Service\DxprBuilderLicenseServiceInterface
   *   The license service.
   */
  protected function licenseService() {
    if (empty($this->licenseService)) {
      $this->licenseService = \Drupal::service('dxpr_builder.license_service');
    }
    return $this->licenseService;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function entityTypeManager() {
    if (empty($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

}
