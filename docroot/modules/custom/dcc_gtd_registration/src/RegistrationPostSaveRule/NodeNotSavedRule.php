<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_gtd_registration\Exception\RegistrationException;
use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class NodeNotSavedRule.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
class NodeNotSavedRule implements RegistrationPostSaveRule {

  /**
   * {@inheritdoc}
   */
  public function applies(FormStateInterface $formState) {
    return !$formState->get(GlobalTrainingRegistrationForm::REGISTRATION_SAVED_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function operation(FormStateInterface $formState) {
    $userErrorMsg = t('There was an error with the creation, please contact the administrators.');
    $exception = new RegistrationException("The node could not be saved.");
    watchdog_exception('Registration Creation', $exception);

    drupal_set_message($userErrorMsg);
  }

}
