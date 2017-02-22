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
   * @param string $message
   *   The email message.
   * @param string $params
   *   The email parameters.
   */
  public function formatMessage(string $message, string $params);

}
