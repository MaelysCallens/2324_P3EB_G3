<?php

namespace Drupal\dxpr_builder\Service\Handler;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Views;

/**
 * Description.
 */
class ViewHandler implements ViewHandlerInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Construct a BlockHandler entity.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getView($viewId, $exp_input, $displayId, $data, AttachedAssets $assets) {
    /** @var \Drupal\views\ViewExecutable|null $view */
    $view = Views::getView($viewId);
    if ($view) {
      $view->setDisplay($displayId);

      // Views throws an error when rendering exposed filters with remember
      // option is enabled and the session is not available. This occurs during
      // search indexing in cron. Prevent error by disabling the remember
      // option in this situation.
      $filters = $view->display_handler->getOption('filters');
      foreach ($filters as $name => $filter) {
        if (!empty($filters[$name]['expose']) && !empty($filters[$name]['expose']['remember'])) {
          $session = $view->getRequest()->getSession();
          if ($session !== FALSE) {
            $filters[$name]['expose']['remember'] = NULL;
          }
        }
      }
      $view->display_handler->setOption('filters', $filters);

      if (isset($data['originalPath']) && strlen($data['originalPath'])) {
        $url_parts = explode('?', $data['originalPath']);
        $parts = explode('/', preg_replace('/^\//', '', $url_parts[0]));
        array_shift($parts);
        $view->setArguments($parts);
      }

      // Show exposed filters for all users.
      if (isset($data['display_exposed_filters']) && $data['display_exposed_filters'] != 'yes') {
        $view->display_handler->setOption('exposed_block', TRUE);
      }
      $exposed_input = $view->getExposedInput();
      parse_str(html_entity_decode($exp_input), $exposed_input);
      if ((!empty($exposed_input))) {
        $filters = $view->display_handler->getOption('filters');
        foreach ($exposed_input as $key => $value) {
          // Exposed filter token All for terms filter is broken,
          // instead just skip this filter.
          if ($value == 'All') {
            continue;
          }
          foreach ($filters as &$filter) {
            if (isset($filter['exposed']) && $filter['exposed']) {
              if ($filter['expose']['identifier'] == $key && !empty($value)) {
                $filter['value'] = $value;
              }
            }
          }
        }
        $view->display_handler->setOption('filters', $filters);

        $sorts = $view->display_handler->getOption('sorts');
        foreach ($exposed_input as $key => $value) {
          if (isset($sorts[$key])) {
            if (isset($sorts[$key]['exposed']) && $sorts[$key]['exposed']) {
              $sorts[$key]['order'] = $value;
            }
          }
        }
        $view->display_handler->setOption('sorts', $sorts);
      }

      // Override pager.
      if (isset($data['override_pager']) && $data['override_pager'] == 'yes') {

        // Set items count.
        if (!empty($data['items'])) {
          $view->setItemsPerPage($data['items']);
        }

        // Set offset.
        if (!empty($data['offset'])) {
          $view->setOffset($data['offset']);
        }
      }

      // Exclude some fields.
      if (!empty($data['toggle_fields'])) {
        $fields = $view->display_handler->getOption('fields');
        $data['toggle_fields'] = explode(',', $data['toggle_fields']);
        foreach ($fields as $k => $i) {
          if (!in_array($k, $data['toggle_fields'])) {
            $fields[$k]['exclude'] = TRUE;
          }
        }
        $view->display_handler->setOption('fields', $fields);
      }

      // Added arguments for view.
      if (!empty($data['contextual_filter'])) {
        // Multi filter explode by '/'.
        $data['contextual_filter'] = explode('/', $data['contextual_filter']);
        $view->preExecute($data['contextual_filter']);
      }
      else {
        $view->preExecute();
      }

      $rendered_view = $view->render($displayId);
      $output = $this->renderer->renderRoot($rendered_view);

      // Set libraries.
      if (isset($rendered_view['#attached'], $rendered_view['#attached']['library'])) {
        $assets->setLibraries($rendered_view['#attached']['library']);
      }

      // Set settings.
      if (isset($rendered_view['#attached'], $rendered_view['#attached']['drupalSettings'])) {
        $assets->setSettings($rendered_view['#attached']['drupalSettings']);
      }

      // Get views title.
      $title = $view->getTitle();
      if (isset($data['display_title']) && $data['display_title'] == 'yes' && !empty($title)) {
        $title = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['views-title'],
          ],
          'title' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $title,
          ],
        ];

        // Insert title into views $output.
        $output = substr_replace($output, $this->renderer->renderRoot($title), strpos($output, '>') + 1, 0);
      }
    }
    else {
      // View was not able to be loaded.
      return FALSE;
    }

    return $output;
  }

}
