<?php

namespace Drupal\dxpr_builder\Service;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Description.
 */
interface DxprBuilderServiceInterface {

  /**
   * Replaces url components with tokens used by the DXPR Builder.
   *
   * Database values store tokens for the base url, files directory and
   * dxpr builder module directory rather than the actual path.
   * This function takes inserts those tokens into values before they are
   * stored to the database.
   *
   * @param string $content
   *   Field data value containing DXPR Builder tokens.
   *
   * @return string
   *   Field data value with full URLs reflecting current environment.
   */
  public function insertBaseTokens($content);

  /**
   * Replaces path tokens used with the actual elements in current environment.
   *
   * @param string $content
   *   The content for which tokens should be replaced.
   */
  public function replaceBaseTokens(&$content): void;

  /**
   * Replaces deprecated brand strings with new strings in content.
   *
   * @param string $content
   *   The content for which tokens should be replaced.
   */
  public function replaceDeprecatedStrings(&$content): void;

  /**
   * Analyzes the raw fields value and readies it for being output to the page.
   *
   * Expands URL tokens to full urls, removes inline <script> and <link> tags
   * and puts them in list for later processing by drupal_add_css/js.
   * Replaces Drupal Blocks/Views element placeholders with full content.
   *
   * @param string $dataString
   *   Identification string for container consisting of
   *   entitytype|bundle|entity ID|field machine name.
   * @param bool $enable_editor
   *   Description.
   *
   * @return mixed[]
   *   An array containing the following keys:
   *   - output: the value to be altered by this function
   *   - css: an array of CSS files to be included
   *   - js: an array of JS files to be included
   *   - mode: the mode of the response
   */
  public function updateHtml($dataString, $enable_editor);

  /**
   * Attaches js and css assets to field render array.
   *
   * Also adds some libraries to page output using drupal_add_library().
   * Adds several settings to inline JS output for the builder UI to use.
   * If the default theme implements the color modulew e also provide a
   * subset of it's colors to be used as default colors in color pickers.
   *
   * @param mixed[] $element
   *   A renderable array for the $items, as an array of child elements
   *   keyed by numeric indexes starting from 0.
   * @param mixed[] $settings
   *   An array of settings that will be attached to the element.
   */
  public function editorAttach(array &$element, array &$settings): void;

  /**
   * Retrieves and caches CMS elements (blocks and views) for Builder interface.
   *
   * @return mixed[]
   *   Array of Drupal blocks and views displays. Blocks are keyed by an
   *   identifier consisting of "block"-module-delta and view displays
   *   are keyed by "view"-module-display.
   */
  public function getCmsElementNames();

  /**
   * Get the path to the installation's files directory.
   *
   * @param string|null $default_scheme
   *   Selected scheme.
   */
  public function getFilesDirectoryPath($default_scheme = NULL): string;

  /**
   * Renders Blocks and Views Displays so they can be inserted into fields.
   *
   * @param mixed[] $element_info
   *   Identifies the element to be rendered. The array will contain the
   *   following three keys:
   *   - type: Either 'block' or 'view'
   *   - module: The module that handles the element
   *   - delta: The delta of the block.
   * @param string $settings
   *   Array containing settings for this CMS element, including title display,
   *   views pager settings, views fields toggling, and contextual
   *   filter settings.
   * @param mixed[] $data
   *   Additional settings Drupal views.
   * @param \Drupal\Core\Asset\AttachedAssets $assets
   *   An AttachedAssets object to which any assets should be attached.
   *
   * @return string
   *   Rendered element.
   */
  public function loadCmsElement(
    array $element_info,
    string $settings,
    array $data,
    AttachedAssets $assets
  );

  /**
   * Retrieve all folders containing dxpr elment templates.
   *
   * @return mixed[]
   *   A list of folders in which dxpr elements may exist. Each element
   *   of the array will have the following keys:
   *   - folder: the path to the folder
   *   - folder_url: The URL to the folder
   */
  public function getDxprElementsFolders();

  /**
   * Get the base URL of the installation.
   *
   * @return string
   *   The base URL of the installation
   */
  public function getBaseUrl();

  /**
   * Get the base URL of the installation.
   *
   * @return string
   *   The base URL of the installation
   */
  public function getBasePath();

  /**
   * Parses a string for the relevant element info.
   *
   * @param string $string
   *   The string to parse.
   *
   * @return mixed[]
   *   An array containing the relevant element info parsed
   *   from the string
   */
  public function parseStringForCmsElementInfo($string);

  /**
   * Parse an entity and force an empty value to any dxpr fields.
   *
   * If no value was entered.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be manipulated.
   */
  public function setEmptyStringToDxprFieldsOnEntity(ContentEntityInterface $entity): void;

}
