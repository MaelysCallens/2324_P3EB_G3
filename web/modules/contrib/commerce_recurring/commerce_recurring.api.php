<?php

/**
 * @file
 * Hooks provided by the Commerce Recurring module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the list of available prorater plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\commerce_recurring\ProraterManager
 */
function hook_commerce_prorater_info_alter(array &$plugins) {
  $plugins['someplugin']['label'] = t('Better name');
}

/**
 * @} End of "addtogroup hooks".
 */
