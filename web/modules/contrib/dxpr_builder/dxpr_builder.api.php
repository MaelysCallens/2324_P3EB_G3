<?php

/**
 * @file
 * Hooks provided by the DXPR Builder module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to modify DXPR Builder utility classes list.
 *
 * Implements hook_dxpr_builder_classes().
 *   Array of classes with one or more classes as key (spaces are allowed) and
 *   a description of the class as value. You can start a new option group in
 *   the classes selectbox by adding an item with key optgroup-yourname and a
 *   description as value.
 *   You can also extend classes in a theme's info file.
 *
 * @see http://www.dxpr.com/documentation/extend-carbide-builder-utility-classes
 */
function hook_dxpr_builder_classes(&$dxpr_builder_classes) {
  $dxpr_builder_classes['optgroup-my-group'] = t('My Option Group');
  $dxpr_builder_classes['my-class'] = t('My label');
}

/**
 * Modify list of folders containing DXPR Builder button styles.
 *
 * Implements hook_dxpr_builder_classes().
 *   Array of paths pointing to folders that contain HTML files
 *   describing buttons.
 *   This function looks for .html files in the list of paths and then
 *   searches for class attributes containing a bootstrap button class "btn" and
 *   extracts the rest of the classes in class attribute to define the
 *   button style.
 *
 * @see dxpr_elements/Buttons
 */
function hook_dxpr_builder_buttons_folders(&$dxpr_buttons_folders) {
  $dxpr_buttons_folders[] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dxpr_elements/Buttons';
}

/**
 * @} End of "addtogroup hooks".
 */
