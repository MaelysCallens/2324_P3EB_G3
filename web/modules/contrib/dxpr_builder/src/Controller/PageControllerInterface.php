<?php

namespace Drupal\dxpr_builder\Controller;

/**
 * Description.
 */
interface PageControllerInterface {

  /**
   * Page controller for the dxpr builder configuration page.
   *
   * @return mixed[]
   *   A render array representing the page, and containing
   *   the configuration form
   */
  public function configPage();

  /**
   * Page controller for the dxpr builder paths page.
   *
   * @return mixed[]
   *   A render array representing the page, and containing the paths form
   */
  public function pathsPage();

  /**
   * Page controller for the DXPR Builder user licenses page.
   *
   * @return mixed[]
   *   A render array representing the page.
   */
  public function userLicensesPage();

  /**
   * Page controller for the user licenses sites modal.
   *
   * @return mixed[]
   *   A render array representing the page.
   */
  public function userLicensesSitesPage();

  /**
   * Page controller for the DXPR Content list.
   *
   * @return mixed[]
   *   A render array representing the page.
   */
  public function licensedContentPage();

}
