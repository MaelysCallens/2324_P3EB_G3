<?php

namespace Drupal\dxpr_builder\Service\Handler;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Description.
 */
class BlockHandler implements BlockHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Construct a BlockHandler entity.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager for content blocks created
   *   through the admin interface.
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager for blocks created through plugins.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, BlockManagerInterface $blockManager, AccountProxyInterface $currentUser, RendererInterface $renderer, EntityRepositoryInterface $entityRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->blockManager = $blockManager;
    $this->currentUser = $currentUser;
    $this->renderer = $renderer;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock(array $blockInfo, string $configuration, AttachedAssets $assets, array $data) {
    if ($blockInfo['provider'] === 'block_content') {
      // Content blocks are loaded by UUID.
      $block = $this->entityRepository->loadEntityByUuid('block_content', $blockInfo['uuid']);
      if ($block && $block->access('view', $this->currentUser)) {
        $render = $this->entityTypeManager->getViewBuilder('block_content')->view($block);
      }
    }
    else {
      $block_config = $this->parseSettings($configuration);
      $block = $this->blockManager->createInstance($blockInfo['id'], $block_config);

      if ($block->access($this->currentUser)) {

        $definition = $block->getPluginDefinition();
        if (isset($data['display_title']) && $data['display_title'] == 'yes' && $definition['admin_label']) {
          $render['title'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['views-title'],
            ],
            'title' => [
              '#type' => 'html_tag',
              '#tag' => 'h2',
              '#value' => $definition['admin_label'],
            ],
          ];
        }

        $render['content'] = $block->build();
      }
    }

    $rendered = $this->renderer->renderRoot($render);

    if (isset($render['#attached']['library']) && is_array($render['#attached']['library'])) {
      $assets->setLibraries($render['#attached']['library']);
    }

    // Adds html_head scripts.
    // Use settings as store because \Drupal\Core\Asset\AttachedAssets
    // doesnt have this property.
    if (isset($render['#attached']['html_head'])) {
      $render['#attached']['drupalSettings']['dxpr_html_head'] = $render['#attached']['html_head'];
    }

    if (isset($render['#attached']['drupalSettings'])) {
      $assets->setSettings($render['#attached']['drupalSettings']);
    }

    return $rendered;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess($block_id, array $definition) {
    try {
      if ($definition['provider'] === 'block_content') {
        // Content blocks are loaded by UUID.
        $uuid = str_replace("block_content:", "", $block_id);
        $block = $this->entityRepository->loadEntityByUuid('block_content', $uuid);
        $access = $block->access('view', $this->currentUser);
      }
      else {
        $block = $this->blockManager->createInstance($block_id, []);
        $access = $block->access($this->currentUser);
      }
    }
    catch (MissingValueContextException $e) {
      // If contexts exist, but have no value, then deny access.
      $access = FALSE;
    }
    catch (ContextException $e) {
      // If any context is missing then deny access.
      $access = FALSE;
    }
    catch (\Error $e) {
      // Catch coding errors in 3rd-party blocks to prevent a WSOD.
      // For example, see https://www.drupal.org/project/sitemap/issues/3273581
      $access = FALSE;
    }

    return $access;
  }

  /**
   * Cast boolean values in the settings string.
   *
   * @param mixed[] $settings
   *   Parsed settings.
   */
  private function castSettings(array &$settings): void {
    foreach ($settings as $key => $value) {
      if ($value === '0') {
        // Prevent unchecked boolean from being overridden by default values.
        $settings[$key] = FALSE;
      }
      if (is_array($settings[$key])) {
        $this->castSettings($settings[$key]);
      }
    }
  }

  /**
   * Parse the settings string.
   *
   * @param string $configuration
   *   The settings string sent by the client.
   *
   * @return mixed[]
   *   Parsed settings.
   */
  private function parseSettings(string $configuration) {
    parse_str(html_entity_decode($configuration), $settings);
    if (!is_array($settings)) {
      return [];
    }
    $this->castSettings($settings);
    return $settings;
  }

}
