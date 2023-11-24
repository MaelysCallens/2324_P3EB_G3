<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class to load and save content locks.
 */
class ContentLock {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ContentLock object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    $this->database = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Toggle content lock.
   *
   * @param string $entity_id
   *   The entity id.
   * @param string $revision_id
   *   The revision id.
   * @param string $entity_type
   *   The entity type.
   * @param string $langcode
   *   The language code of the language.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function contentLockStatus($entity_id, $revision_id, $entity_type, $langcode) {
    $locked_content_data = [];
    $locked_content_data['status'] = FALSE;
    $entity_lock_author_id = $this->getLockedContent($entity_id, $revision_id, $entity_type, $langcode);
    if ($entity_lock_author_id) {
      $locked_content_data['status'] = TRUE;
      $locked_content_data['entity_lock_author_id'] = $entity_lock_author_id;
      $user = $this->entityTypeManager->getStorage('user')->load($entity_lock_author_id);
      $locked_content_data['entity_lock_author_name'] = $user->getDisplayName();
    }
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $locked_content_data['label'] = $entity->label();
    return new JsonResponse($locked_content_data);
  }

  /**
   * Toggle content lock.
   *
   * @param string $entity_id
   *   The entity id.
   * @param string $revision_id
   *   The revision id.
   * @param string $entity_type
   *   The entity type.
   * @param string $langcode
   *   The language code of the language.
   * @param string $toggle_action
   *   The action lock or unlock.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function toggleContentLock($entity_id, $revision_id, $entity_type, $langcode, $toggle_action) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $content_lock = [];
    if ($entity && $entity->access('update', $this->currentUser)) {
      $content_lock['label'] = $entity->label();
      // Create record in database.
      if ($toggle_action == 'lock') {
        // Delete old records.
        $this->deleteLockedContent($entity_id, $revision_id, $entity_type, $langcode);
        // Save the new lock record.
        $this->saveLockedContent($entity_id, $revision_id, $entity_type, $langcode);
        $content_lock['status'] = TRUE;
      }
      else {
        // Delete the lock record.
        $this->deleteLockedContent($entity_id, $revision_id, $entity_type, $langcode);
        $content_lock['status'] = FALSE;
      }
    }

    return new JsonResponse($content_lock);
  }

  /**
   * Save the given locked content nid for the current user.
   *
   * @param string $entity_id
   *   The entity id.
   * @param string $revision_id
   *   The revision id.
   * @param string $entity_type
   *   The entity type.
   * @param string $langcode
   *   The language code of the language.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response
   */
  public function saveLockedContent($entity_id, $revision_id, $entity_type, $langcode) {
    $this->database->insert('dxpr_lock')
      ->fields([
        'entity_id' => $entity_id,
        'revision_id' => $revision_id,
        'entity_type' => $entity_type,
        'langcode' => $langcode,
        'uid' => $this->currentUser->id(),
      ])
      ->execute();

    return new JsonResponse('');
  }

  /**
   * Delete the given locked content.
   *
   * @param string $entity_id
   *   The entity id.
   * @param string $revision_id
   *   The revision id.
   * @param string $entity_type
   *   The entity type.
   * @param string $langcode
   *   The language code of the language.
   */
  public function deleteLockedContent($entity_id, $revision_id, $entity_type, $langcode): void {
    $this->database->delete('dxpr_lock')
      ->condition('entity_id', $entity_id)
      ->condition('revision_id', $revision_id)
      ->condition('entity_type', $entity_type)
      ->condition('langcode', $langcode)
      ->execute();
  }

  /**
   * Get the given locked content.
   *
   * @param string $entity_id
   *   The entity id.
   * @param string $revision_id
   *   The revision id.
   * @param string $entity_type
   *   The entity type.
   * @param string $langcode
   *   The language code of the language.
   *
   * @return mixed[]
   *   get locked content
   */
  public function getLockedContent($entity_id, $revision_id, $entity_type, $langcode) {
    $result = $this->database->select('dxpr_lock', 'g')
      ->fields('g', ['uid'])
      ->condition('g.entity_id', $entity_id)
      ->condition('g.revision_id', $revision_id)
      ->condition('g.entity_type', $entity_type)
      ->condition('g.langcode', $langcode)
      ->execute()
      ->fetchField();

    return $result;
  }

}
