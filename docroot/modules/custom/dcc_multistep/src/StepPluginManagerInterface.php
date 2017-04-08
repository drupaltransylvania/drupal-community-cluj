<?php

namespace Drupal\dcc_multistep;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;

interface StepPluginManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Returns an array of Step plugin instances.
   *
   * @param $form_id
   *   The form id to which these steps belong to.
   * @return array
   */
  public function getSteps($form_id);

}
