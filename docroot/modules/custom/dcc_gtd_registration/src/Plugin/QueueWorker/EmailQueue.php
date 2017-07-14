<?php

namespace Drupal\dcc_gtd_registration\Plugin\QueueWorker;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\dcc_email\Entity\PersonalInformation;
use Drupal\dcc_email\RegistrationEmailTrait;
use Drupal\dcc_gtd_registration\RegistrationPostSaveRule\SuccessRule;

/**
 * Sending reminder emails.
 *
 * @QueueWorker(
 *   id = "email_queue",
 *   title = @Translation("Sending reminder emails"),
 *   cron = {"time" = 20}
 * )
 */
class EmailQueue extends QueueWorkerBase {
  use RegistrationEmailTrait;

  /**
   * {@inheritdoc}
   */
  public function processItem($person) {
    $message = $this->sendMail($person->field_first_name_value, $person->field_last_name_value, $person->field_email_value, $person->encryption);
    if ($message['send'] == TRUE) {
      $statusMessage = [
        'message' => t('Your registration has been submited with success! You will receive an email shortly!'),
        'type' => 'status'
      ];
    }

    drupal_set_message($statusMessage['message'], $statusMessage['type']);
  }

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Sends email.
   *
   * @param string $firstName
   *   The first name of the person.
   * @param string $lastName
   *   The last name of the person.
   * @param string $email
   *   The email of the person.
   * @param string $encryption
   *   The link encryption.
   *
   * @return array
   *   Returns the message array that was sent.
   */
  private function sendMail($firstName, $lastName, $email, $encryption) {
    $personalInformation = new PersonalInformation($firstName, $lastName);

    $params = $this->buildParams($personalInformation, $encryption);

    return $this->mailManager()->mail(
      'dcc_email',
      'global_training_reminder',
      $email,
      'en',
      $params
    );
  }

  /**
   * Builds the email parameters.
   *
   * @param \Drupal\dcc_email\Entity\PersonalInformation $personalInformation
   *   The resever personal information.
   * @param string $encryption
   *   The link encryption.
   *
   * @return array|void
   *   The email parameters.
   */
  public function buildParams(PersonalInformation $personalInformation, $encryption) {
    $this->images += ['reminder_image_url' => 'reminder.png'];
    $params['registration_status_links'] = $this->generateStatusActionLink($encryption, SuccessRule::CANCEL_LINK);

    $params += $this->generateUserRegistrationInfo($personalInformation);
    $params = $this->generateImages($params);

    return $params;
  }

  /**
   * The mail manager service.
   *
   * We can't inject this service, because there is an issue with the
   * serialization of this object.
   *
   * @return MailManagerInterface
   *   The mail manager service.
   */
  private function mailManager() {
    return \Drupal::service('plugin.manager.mail');
  }

}
