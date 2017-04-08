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
   *   The current form state.
   * @param \Drupal\Core\Form\FormInterface $form
   *   The form object.
   *
   * @return mixed
   *   A render array.
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form);

  /**
   * Performs step specific validations.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function validate(FormStateInterface $formState);

  /**
   * Sets the submitted values of the current step.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function setCurrentValues(FormStateInterface $formState);

}
