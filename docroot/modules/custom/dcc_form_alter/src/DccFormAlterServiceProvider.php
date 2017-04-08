<?php

namespace Drupal\dcc_form_alter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\dcc_form_alter\DependencyInjection\Compiler\FormAlterPass;

/**
 * Class DccFormAlterServiceProvider.
 *
 * @package Drupal\dcc_form_alter
 */
class DccFormAlterServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new FormAlterPass());
  }

}
