<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_gtd_registration\Exception\NoEmailException;

/**
 * Class NoEmailRule.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
class NoEmailRule implements RegistrationPostSaveRule {

  /**
   * {@inheritdoc}
   */
  public function applies(FormStateInterface $formState) {
    return !$formState->get('email');
  }

  /**
   * {@inheritdoc}
   */
  public function operation(FormStateInterface $formState) {
    $userErrorMsg = t('There was an error with the creation, please contact the administrators.');
    $exception = new NoEmailException("The email address was not found in the form state.");
    watchdog_exception('Registration Creation', $exception);

    drupal_set_message($userErrorMsg);
  }

}
