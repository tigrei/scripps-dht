<?php

namespace Drupal\scripps_node\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Device Submission Thank You page.
 */
class ThankYouController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $config = $this->config('scripps_node.adminsettings');

    $build['#title'] = [
      '#type' => 'markup',
      '#markup' => $config->get('title')
    ];

    $build['body'] = [
      '#type' => 'markup',
      '#markup' => $config->get('thank_you_text')['value']
    ];
    return $build;
  }

}