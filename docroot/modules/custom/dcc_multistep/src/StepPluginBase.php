<?php

namespace Drupal\dcc_multistep;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class StepPluginBase
 *
 * Provides common functionality for Step plugins.
 *
 * @package Drupal\dcc_multistep
 */
abstract class StepPluginBase extends PluginBase implements StepPluginInspectionInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(FormStateInterface $formState) {}

}
