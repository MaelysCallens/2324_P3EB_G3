<?php

namespace Drupal\bootstrap5;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Bootstrap5 subtheme manager.
 */
class SubthemeManager {

  use StringTranslationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SubthemeManager constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(FileSystemInterface $file_system, MessengerInterface $messenger) {
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
  }

  /**
   * Validate callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see hook_form_alter()
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subthemePathValue = $form_state->getValue('subtheme_folder');
    // Check for empty values.
    if (!$subthemePathValue) {
      $form_state->setErrorByName('subtheme_folder', $this->t('Subtheme folder is empty.'));
    }
    if (!$form_state->getValue('subtheme_machine_name')) {
      $form_state->setErrorByName('subtheme_machine_name', $this->t('Subtheme machine name is empty.'));
    }
    if (count($form_state->getErrors())) {
      return;
    }

    // Check for path trailing slash.
    if (strrev(trim($subthemePathValue))[0] === '/') {
      $form_state->setErrorByName('subtheme_folder', $this->t('Subtheme folder should be without trailing slash.'));
    }
    // Check for name validity.
    if (!$form_state->getValue('subtheme_machine_name')) {
      $form_state->setErrorByName('subtheme_machine_name', $this->t('Subtheme name format is incorrect.'));
    }
    if (count($form_state->getErrors())) {
      return;
    }

    // Check for writable path.
    $directory = DRUPAL_ROOT . '/' . $subthemePathValue;
    if ($this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS) === FALSE) {
      $form_state->setErrorByName('subtheme_folder', $this->t('Subtheme cannot be created. Check permissions.'));
    }
    // Check for common theme names.
    if (in_array($form_state->getValue('subtheme_machine_name'), [
      'bootstrap', 'bootstrap4', 'bootstrap5', 'claro', 'bartik', 'seven',
    ])) {
      $form_state->setErrorByName('subtheme_machine_name', $this->t('Subtheme name should not match existing themes.'));
    }
    if (count($form_state->getErrors())) {
      return;
    }

    // Check for reserved terms.
    if (in_array($form_state->getValue('subtheme_machine_name'), [
      'src', 'lib', 'vendor', 'assets', 'css', 'files', 'images', 'js', 'misc', 'templates', 'includes', 'fixtures', 'Drupal',
    ])) {
      $form_state->setErrorByName('subtheme_machine_name', t('Subtheme name should not match reserved terms.'));
    }
    // Validate machine name to ensure correct format.
    if(!preg_match("/^[a-z]+[0-9a-z_]+$/", $form_state->getValue('subtheme_machine_name'))) {
      $form_state->setErrorByName('subtheme_machine_name', t('Subtheme machine name format is incorrect.'));
    }
    // Check machine name is not longer than 50 characters.
    if (strlen($form_state->getValue('subtheme_machine_name')) > 50) {
      $form_state->setErrorByName('subtheme_folder', t('Subtheme machine name must not be longer than 50 characters.'));
    }

    // Check for writable path.
    $themePath = $directory . '/' . $form_state->getValue('subtheme_machine_name');
    if (file_exists($themePath)) {
      $form_state->setErrorByName('subtheme_machine_name', $this->t('Folder already exists.'));
    }
  }

  /**
   * Submit callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see hook_form_alter()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fs = $this->fileSystem;

    // Create subtheme.
    $themeMName = $form_state->getValue('subtheme_machine_name');
    $themeName = $form_state->getValue('subtheme_name');
    if (empty($themeName)) {
      $themeName = $themeMName;
    }

    $subthemePathValue = $form_state->getValue('subtheme_folder');
    $themePath = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $subthemePathValue . DIRECTORY_SEPARATOR . $themeMName;
    if (!is_dir($themePath)) {
      // Copy CSS file replace empty one.
      $subforders = ['css'];
      foreach ($subforders as $subforder) {
        $directory = $themePath . DIRECTORY_SEPARATOR . $subforder . DIRECTORY_SEPARATOR;
        $fs->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

        $files = $fs->scanDirectory(
          \Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . $subforder . DIRECTORY_SEPARATOR, '/.*css/', [
            'recurse' => FALSE,
        ]);
        foreach ($files as $file) {
          //dump($file);
          $fileName = $file->filename;
          $fs->copy(
            \Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . $subforder . DIRECTORY_SEPARATOR . $fileName,
            $themePath . DIRECTORY_SEPARATOR . $subforder . DIRECTORY_SEPARATOR . $fileName, TRUE);
        }
      }

      // Copy image files.
      $files = [
        'favicon.ico',
        'logo.svg',
        'screenshot.png',
      ];
      foreach ($files as $fileName) {
        $fs->copy(\Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . $fileName,
          $themePath . DIRECTORY_SEPARATOR . $fileName, TRUE);
      }

      // Copy files and rename content (array of lines of copy existing).
      $files = [
        'bootstrap5.breakpoints.yml' => -1,
        'bootstrap5.libraries.yml' => [
          'global-styling:',
          '  css:',
          '    theme:',
          '      css/style.css: {}',
          '',
        ],
        'bootstrap5.theme' => [
          '<?php',
          '',
          '/**',
          ' * @file',
          ' * ' . $themeName .' theme file.',
          ' */',
          '',
        ],
        'README.md' => [
          '# ' . $themeName . ' theme',
          '',
          '[Bootstrap 5](https://www.drupal.org/project/bootstrap5) subtheme.',
          '',
          '## Development.',
          '',
          '### CSS compilation.',
          '',
          'Prerequisites: install [sass](https://sass-lang.com/install).',
          '',
          'To compile, run from subtheme directory: `sass scss/style.scss css/style.css && sass scss/ck5style.scss css/ck5style.css`',
          '',
        ],
      ];

      foreach ($files as $fileName => $lines) {
        // Get file content.
        $content = str_replace('bootstrap5', $themeMName, file_get_contents(\Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . $fileName));
        if (is_array($lines)) {
          $content = implode(PHP_EOL, $lines);
        }
        file_put_contents($themePath . DIRECTORY_SEPARATOR . str_replace('bootstrap5', $themeMName, $fileName),
          $content);
      }

      // Info yml file generation.
      $infoYml = Yaml::decode(file_get_contents(\Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . 'bootstrap5.info.yml'));
      $infoYml['name'] = $themeName;
      $infoYml['description'] = $themeName . ' subtheme based on Bootstrap 5 theme.';
      $infoYml['base theme'] = 'bootstrap5';

      $infoYml['libraries'] = [];
      $infoYml['libraries'][] = $themeMName . '/global-styling';
      $infoYml['libraries-override'] = [
        'bootstrap5/global-styling' => false,
      ];

      foreach ([
        'version',
        'project',
        'datestamp',
        'starterkit',
        'generator',
        'libraries-extend',
      ] as $value) {
        if (isset($infoYml[$value])) {
          unset($infoYml[$value]);
        }
      }

      file_put_contents($themePath . DIRECTORY_SEPARATOR . $themeMName . '.info.yml',
        Yaml::encode($infoYml));

      // SCSS files generation.
      $scssPath = $themePath . DIRECTORY_SEPARATOR . 'scss';
      $b5ScssPath = \Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . 'scss' . DIRECTORY_SEPARATOR;
      $fs->prepareDirectory($scssPath, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      $files = [
        'style.scss' => [
          "// Sub theme styling.",
          "@import 'variables_drupal';",
          '',
          "// Bootstrap overriden variables.",
          "// @see https://getbootstrap.com/docs/5.2/customize/sass/#variable-defaults.",
          "@import 'variables_bootstrap';",
          '',
          "// Include bootstrap.",
          "@import '" .
          str_repeat('../', count(explode(DIRECTORY_SEPARATOR, $subthemePathValue)) + 2) .
          \Drupal::service('extension.list.theme')->getPath('bootstrap5') . "/scss/style';",
          '',
        ],
        'ck5style.scss' => $b5ScssPath . 'ck5style.scss',
        '_variables_drupal.scss' => $b5ScssPath . '_variables_drupal.scss',
        '_variables_bootstrap.scss' => $b5ScssPath . '_variables_bootstrap.scss',
      ];

      foreach ($files as $fileName => $lines) {
        // Get file content.
        if (is_array($lines)) {
          $content = implode(PHP_EOL, $lines);
          file_put_contents($scssPath . DIRECTORY_SEPARATOR . $fileName, $content);
        }
        elseif (is_string($lines)) {
          $fs->copy($lines, $scssPath . DIRECTORY_SEPARATOR . $fileName, TRUE);
        }
      }

      // Add block config to subtheme.
      $orig_config_path = \Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . 'config/optional';
      $config_path = $themePath . DIRECTORY_SEPARATOR . 'config/optional';
      $files = scandir($orig_config_path);
      $fs->prepareDirectory($config_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      foreach ($files as $filename) {
        if (substr($filename, 0, 5) === 'block') {
          $confYml = Yaml::decode(file_get_contents($orig_config_path . DIRECTORY_SEPARATOR . $filename));
          $confYml['dependencies']['theme'] = [];
          $confYml['dependencies']['theme'][] = $themeMName;
          $confYml['id'] = str_replace('bootstrap5', $themeMName, $confYml['id']);
          $confYml['theme'] = $themeMName;
          $file_name = str_replace('bootstrap5', $themeMName, $filename);
          file_put_contents($config_path . DIRECTORY_SEPARATOR . $file_name,
            Yaml::encode($confYml));
        }
      }

      // Add install config to subtheme.
      $orig_config_path = \Drupal::service('extension.list.theme')->getPath('bootstrap5') . DIRECTORY_SEPARATOR . 'config/install';
      $config_path = $themePath . DIRECTORY_SEPARATOR . 'config/install';
      $files = scandir($orig_config_path);
      $fs->prepareDirectory($config_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      foreach ($files as $filename) {
        if (substr($filename, 0, 10) === 'bootstrap5') {
          $confYml = Yaml::decode(file_get_contents($orig_config_path . DIRECTORY_SEPARATOR . $filename));
          $file_name = str_replace('bootstrap5', $themeMName, $filename);
          file_put_contents($config_path . DIRECTORY_SEPARATOR . $file_name,
            Yaml::encode($confYml));
        }
      }

      $this->messenger->addStatus(t('Subtheme created at %subtheme', [
        '%subtheme' => $themePath,
      ]));
    }
    else {
      $this->messenger->addError(t('Folder already exists at %subtheme', [
        '%subtheme' => $themePath,
      ]));
    }
  }

}
