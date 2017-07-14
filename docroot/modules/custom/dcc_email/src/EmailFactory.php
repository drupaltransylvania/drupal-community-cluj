<?php

namespace Drupal\dcc_email;

use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class EmailFactory.
 *
 * @package Drupal\dcc_email
 */
class EmailFactory {

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
   * @return FormatterInterface
   *   Returns the message.
   */
  public static function getEmail($key, $message, $params) {
    switch ($key) {
      case GlobalTrainingRegistrationForm::GLOBAL_TRAINING_REGISTRATION_EMAIL_KEY:
        return \Drupal::service('dcc_email.success_email_formatter');

      case 'global_training_reminder':
        return \Drupal::service('dcc_email.reminder_email_formatter');

      default:
        return NULL;
      break;

    }
  }

}
