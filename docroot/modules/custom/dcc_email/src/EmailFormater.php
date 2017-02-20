<?php

namespace Drupal\dcc_email;

/**
 * Class EmailFormater.
 *
 * @package Drupal\dcc_email
 */
class EmailFormater {
  private $from;
  private $subject;
  private $body;
  private $param;
  private $encryption;

  /**
   * Returns the email subject.
   *
   * @return mixed
   *   The subject.
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Sets the subject.
   *
   * @param mixed $subject
   *   The email subject.
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * Returns the sender email.
   *
   * @return mixed
   *   The email sender.
   */
  public function getFrom() {
    return $this->from;
  }

  /**
   * Sets the email sender.
   *
   * @param mixed $from
   *   The email sender.
   */
  public function setFrom($from) {
    $this->from = $from;
  }

  /**
   * Returns the email body.
   *
   * @return mixed
   *   The email body.
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Sets the email body.
   *
   * @param mixed $body
   *   The email body.
   */
  public function setBody($body) {
    $this->body = $body;
  }

  /**
   * Returns the parameters for email.
   *
   * @return mixed
   *   The email parameters.
   */
  public function getParam() {
    return $this->param;
  }

  /**
   * Sets the email parameters.
   *
   * @param mixed $param
   *   The email parameters.
   */
  public function setParam($param) {
    $this->param = $param;
  }

  /**
   * Returns the email encryption.
   *
   * @return mixed
   *   The email encryption.
   */
  public function getEncryption() {
    return $this->encryption;
  }

  /**
   * Sets the email encryption.
   *
   * @param mixed $encryption
   *   The email encryption.
   */
  public function setEncryption($encryption) {
    $this->encryption = $encryption;
  }

  /**
   * Creates the message for email.
   *
   * @param string $message
   *   The message of the email.
   * @param string $params
   *   The parameters of the email.
   *
   * @return string
   *   Returns the formatted email.
   */
  public function formatMessage($message, $params) {
    $this->setFrom($message['from']);
    $this->setSubject($message['subject']);
    $this->setBody($message['body']);
    $this->setParam($params);
    $this->setEncryption("text/html");

    return $message;
  }

}
