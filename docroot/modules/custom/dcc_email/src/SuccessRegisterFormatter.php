<?php

namespace Drupal\dcc_email;

use Drupal\Core\Render\Renderer;
use Drupal\dcc_email\EmailFormater;
use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class SuccessRegisterFormatter.
 *
 * @package Drupal\dcc_email
 */
class SuccessRegisterFormatter extends EmailFormater {

  /**
   * Formats the email.
   *
   * @param string $message
   *   The message of the mail.
   * @param string $params
   *   The parameters of the mail.
   *
   * @return mixed
   *   Returns the formatted message.
   */
  public function formatMessage(string $message, string $params) {
    $renderArray = [
      '#theme' => GlobalTrainingRegistrationForm::GLOBAL_TRAINING_REGISTRATION_EMAIL_KEY,
      '#first_name' => $params['registration_info']['first_name'],
      '#last_name' => $params['registration_info']['last_name'],
      '#logo_url' => $params['registration_info']['logo_url'],
      '#big_image_url' => $params['registration_info']['big_image_url'],
      '#left_image_url' => $params['registration_info']['left_image_url'],
      '#activation_link' => $params['registration_status_links']['activation_link'],
      '#cancel_link' => $params['registration_status_links']['cancel_link'],
    ];

    $message['body'][] = $this->renderer()->render($renderArray);
    $message = $this->changeContentType($message, 'text/html');

    return $message;

  }

  /**
   * The renderer service.
   *
   * @return Renderer
   *   The renderer service.
   */
  private function renderer() {

    return \Drupal::service('renderer');
  }

}
