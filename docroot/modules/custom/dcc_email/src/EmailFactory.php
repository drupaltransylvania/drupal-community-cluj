<?php

namespace Drupal\dcc_email;

use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class EmailFactory.
 *
 * @package Drupal\dcc_email
 */
class EmailFactory extends EmailFormater {

  /**
   * Prepares the email.
   *
   * @param string $key
   *   The registration key.
   * @param string $message
   *   The email message.
   * @param string $params
   *   The parameters for email.
   *
   * @return mixed
   *   Returns the message.
   */
  public function getEmail($key, $message, $params) {
    switch ($key) {
      case GlobalTrainingRegistrationForm::GLOBAL_TRAINING_REGISTRATION_EMAIL_KEY:
        $formater = \Drupal::service('dcc_email.success_email_formatter');
        $message['body'][] = $formater->formatMessage($message, $params);
        break;
    }

    return $message;
  }

}
