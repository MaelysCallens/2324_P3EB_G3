<?php

namespace Drupal\commerce_paypal\Controller;

use Drupal\commerce_payment\Controller\PaymentCheckoutController;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides checkout endpoints for off-site payments.
 */
class PayflowlinkIframeCheckoutController extends PaymentCheckoutController {

  /**
   * {@inheritdoc}
   */
  public function returnPage(Request $request, RouteMatchInterface $route_match) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $route_match->getParameter('commerce_order');
    $step_id = $route_match->getParameter('step');
    $commerce_payflow_data = $order->getData('commerce_payflow');
    $commerce_payflow_data['received_parameters'] = $request->request->all();
    $order->setData('commerce_payflow', $commerce_payflow_data);
    $order->save();
    $build = ['#markup' => ''];
    $build['#attached']['drupalSettings']['commercePayflow']['returnUrl'] = Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => $step_id,
    ])->toString();
    $build['#attached']['drupalSettings']['commercePayflow']['page'] = 'return';
    $build['#attached']['library'][] = 'commerce_paypal/paypal_payflow_link_iframe_fix';
    $build['#attached']['library'][] = 'commerce_paypal/paypal_payflow_link';

    return $build;
  }

}
