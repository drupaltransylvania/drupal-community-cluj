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
   * {@inheritdoc}
   */
  public function formatMessage(array $message, $params) {
    $renderArray = [
      '#theme' => GlobalTrainingRegistrationForm::GLOBAL_TRAINING_REGISTRATION_EMAIL_KEY,
      '#first_name' => $params['registration_info']['first_name'],
      '#last_name' => $params['registration_info']['last_name'],
      '#logo_url' => $params['registration_info']['images']['logo_url'],
      '#big_image_url' => $params['registration_info']['images']['big_image_url'],
      '#left_image_url' => $params['registration_info']['images']['left_image_url'],
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
