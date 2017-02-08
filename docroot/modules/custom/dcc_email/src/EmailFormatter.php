<?php

namespace Drupal\dcc_email;

use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class EmailFormatter.
 *
 * @package Drupal\dcc_email
 */
class EmailFormatter {

  /**
   * Formats the email.
   *
   * @param string $key
   *   The key of the email.
   * @param mixed $message
   *   The message of the email.
   * @param mixed $params
   *   The parameters of the email.
   *
   * @return mixed
   *   Returns the formatted message.
   */
  public function format($key, $message, $params) {
    $message['from'] = \Drupal::config('system.site')->get('mail');
    $message['subject'] = t('Registration');
    $message['body'][] = $this->generateGlobalRegistrationEmailBody($params);

    return $message;
  }

  /**
   * Generates the HTML for the email body.
   *
   * @param array $params
   *   Parameters of the email.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The HTML body of the email.
   */
  private function generateGlobalRegistrationEmailBody(array $params) {
    $renderArray = [
      '#theme' => GlobalTrainingRegistrationForm::GLOBAL_TRAINING_REGISTRATION_EMAIL_KEY,
      '#first_name' => $params['registration_info']['first_name'],
      '#last_name' => $params['registration_info']['last_name'],
      '#activation_link' => $params['registration_status_links']['activation_link'],
      '#cancel_link' => $params['registration_status_links']['cancel_link'],
    ];

    return $this->renderer()->render($renderArray);
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
