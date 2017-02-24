<?php

namespace Drupal\dcc_email;

/**
 * Class EmailFormater.
 *
 * @package Drupal\dcc_email
 */
class EmailFormater implements FormatterInterface {

  /**
   * The email sender.
   *
   * @var from
   */
  private $from;

  /**
   * The email subject.
   *
   * @var subject
   */
  private $subject;

  /**
   * The email body.
   *
   * @var body
   */
  private $body;

  /**
   * The email parameters.
   *
   * @var param
   */
  private $param;

  /**
   * The email content type.
   *
   * @var contentType
   */
  private $contentType;

  /**
   * Returns the email content type.
   *
   * @return mixed
   *   The content type.
   */
  public function getContentType() {
    return $this->contentType;
  }

  /**
   * Sets the email content type.
   *
   * @param mixed $contentType
   *   The email content type.
   */
  public function setContentType($contentType) {
    $this->contentType = $contentType;
  }

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
   * Creates the message for email.
   *
   * @param array $message
   *   The message of the email.
   * @param mixed $params
   *   The parameters of the email.
   */
  public function formatMessage(array $message, $params) {
    $this->setFrom($message['from']);
    $this->setSubject($message['subject']);
    $this->setBody($message['body']);
    $this->setParam($params);
    $this->setContentType("text/html");
  }

  /**
   * Changes the content type of the email.
   *
   * @param string $message
   *   The email message.
   * @param string $newContentType
   *   The new content type of the email.
   *
   * @return mixed
   *   Returns the message.
   */
  protected function changeContentType($message, $newContentType) {
    $contentType = $message['headers']['Content-Type'];
    $contentTypeArray = explode(';', $contentType);
    $contentTypeArray[0] = $newContentType;

    $message['headers']['Content-Type'] = implode(';', $contentTypeArray);

    return $message;
  }

}
