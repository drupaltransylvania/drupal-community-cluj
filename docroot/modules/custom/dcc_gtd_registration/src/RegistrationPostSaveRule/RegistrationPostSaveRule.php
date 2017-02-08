<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface RegistrationPostSaveRule.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
interface RegistrationPostSaveRule {

  /**
   * Checks if the rule applies for a particular operation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state of the global registration form.
   *
   * @return bool
   *   Returns the value of the check.
   */
  public function applies(FormStateInterface $formState);

  /**
   * Performs an oeperation corresponding to the check performed above.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state of the global registration form.
   */
  public function operation(FormStateInterface $formState);

}
