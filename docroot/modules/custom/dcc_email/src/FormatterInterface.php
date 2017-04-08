<?php

namespace Drupal\dcc_email;

/**
 * Interface FormatterInterface.
 *
 * @package Drupal\dcc_email
 */
interface FormatterInterface {

  /**
   * Formats the email.
   *
   * @param array $message
   *   The email message.
   * @param mixed $params
   *   The email parameters.
   */
  public function formatMessage(array $message, $params);

}
