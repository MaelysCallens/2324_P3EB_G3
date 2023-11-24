<?php

namespace Drupal\dxpr_builder\Common;

/**
 * Adds dependencies into configuration.
 */
class ConfigEnforceDependency {

  /**
   * Config list.
   *
   * @var mixed[]
   */
  private $configs;

  /**
   * Module name.
   *
   * @var string
   */
  private $moduleName;

  /**
   * Constructor.
   *
   * @param string $module_name
   *   Module name.
   * @param mixed[] $configs
   *   Config list.
   */
  public function __construct($module_name, array $configs) {
    $this->moduleName = $module_name;
    $this->configs = $configs;
  }

  /**
   * Adds enforced dependencies to config.
   */
  public function execute(): void {
    $config_factory = \Drupal::configFactory();
    $list_all = $config_factory->listAll();

    foreach ($this->configs as $config_name) {
      if (!in_array($config_name, $list_all)) {
        continue;
      }
      $config = $config_factory->getEditable($config_name);
      if ($config->isNew()) {
        continue;
      }
      $dependencies = $config->get('dependencies');

      if (!is_array($dependencies['enforced']['module'])
        || !in_array($this->moduleName, $dependencies['enforced']['module'])) {
        $dependencies['enforced']['module'][] = $this->moduleName;
        $config->set('dependencies', $dependencies)
          ->save();
      }
    }
  }

}
