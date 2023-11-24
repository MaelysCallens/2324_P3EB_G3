<?php

namespace Drupal\dxpr_builder\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description.
 */
interface AjaxControllerInterface {

  /**
   * AJAX CSRF refresh: Refreshes csrf token on the fly.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns url of json format.
   */
  public function ajaxRefresh(): JsonResponse;

  /**
   * Handles various operations for frontend drag and drop builder.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns json response.
   */
  public function ajaxCallback(): Response;

  /**
   * Callback to handle AJAX file uploads.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns json response.
   */
  public function fileUpload(): Response;

}
