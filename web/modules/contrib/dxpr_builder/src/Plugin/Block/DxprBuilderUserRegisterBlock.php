<?php

namespace Drupal\dxpr_builder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description.
 */
class DxprBuilderUserRegisterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a DxprBuilderUserReistrationBlock object.
   *
   * @param mixed[] $configuration
   *   User configuration.
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param string $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $formBuilder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed> $configuration
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Build array with form.
   *
   * @phpstan-return array<string, mixed>
   */
  public function build(): array {
    return [
      '#prefix' => '<div id="dxpr_builder_user_registration_form_block">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\user\RegisterForm'),
    ];
  }

}
