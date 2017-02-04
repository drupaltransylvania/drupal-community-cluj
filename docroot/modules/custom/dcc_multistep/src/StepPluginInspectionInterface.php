<?php

namespace Drupal\dcc_multistep;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Step plugins.
 */
interface StepPluginInspectionInterface extends PluginInspectionInterface {

  /**
   * Return the name of the step.
   *
   * @return string
   *   The name of the validator.
   */
  public function getName();

  /**
   * Builds the form elements for the step.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form);

  /**
   * Performs step specific validations.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   * @return mixed
   */
  public function validate(FormStateInterface $formState);

}
