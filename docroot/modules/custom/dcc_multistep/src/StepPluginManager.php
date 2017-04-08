<?php

namespace Drupal\dcc_multistep;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Step plugin manager.
 */
class StepPluginManager extends DefaultPluginManager implements StepPluginManagerInterface  {

  /**
   * Constructs an StepPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Step', $namespaces, $module_handler, 'Drupal\dcc_multistep\StepPluginInspectionInterface', 'Drupal\dcc_multistep\Annotation\Step');
    $this->alterInfo('dcc_multistep_step_info');
    $this->setCacheBackend($cache_backend, 'dcc_multistep_steps');
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps($form_id) {
    $instances = [];
    $steps = $this->getDefinitionsForElement($form_id);
    foreach ($steps as $key => $step) {
      $instances[$key] = $this->createInstance($step['id']);

    }
    return new \ArrayObject($instances);
  }

  /**
   * Get plugin definitions that need to be created for the form.
   *
   * @param $form_id
   *   The form id of the form.
   * @return array
   *   An array of definitions keyed by the id of the plugin.
   */
  protected function getDefinitionsForElement($form_id) {
    $stepDefinitions = $this->getDefinitions();
    $steps = [];
    foreach ($stepDefinitions as $definition) {
      if (array_key_exists('form_id', $definition) && $definition['form_id'] == $form_id) {
        $steps[$definition['step_number']] = $definition;
      }
    }
    return $steps;
  }

}
