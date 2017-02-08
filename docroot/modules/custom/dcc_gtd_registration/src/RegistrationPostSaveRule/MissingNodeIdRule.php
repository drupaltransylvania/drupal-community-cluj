<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_gtd_registration\Exception\MissingNodeIdException;
use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class MissingNodeIdRule.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
class MissingNodeIdRule implements RegistrationPostSaveRule {

  /**
   * {@inheritdoc}
   */
  public function applies(FormStateInterface $formState) {
    return !$formState->get(GlobalTrainingRegistrationForm::REGISTRATION_NID_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function operation(FormStateInterface $formState) {
    $userErrorMsg = t('There was an error with the creation, please contact the administrators.');
    $exception = new MissingNodeIdException("The node id is missing in the form state");
    watchdog_exception('Registration Creation', $exception);

    drupal_set_message($userErrorMsg);
  }

}
