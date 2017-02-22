<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\dcc_encryption\Cryptor;
use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;

/**
 * Class SuccessRule.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
class SuccessRule implements RegistrationPostSaveRule {

  const ACTIVATION_LINK = 1;
  const CANCEL_LINK = 0;
  const LINK_IS_NOT_USED = 0;
  const LINK_IS_USED = 1;
  const ONE_TIME_LINK_TABLE = 'dcc_gtd_registration_one_time_link';

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public function applies(FormStateInterface $formState) {
    return $formState->get('email') &&
      $formState->get(GlobalTrainingRegistrationForm::REGISTRATION_NID_FIELD) &&
      $formState->get(GlobalTrainingRegistrationForm::REGISTRATION_SAVED_FIELD);
  }

  /**
   * {@inheritdoc}
   */
  public function operation(FormStateInterface $formState) {
    $encryption = $this->encryptRegistrationInfo($formState);

    $message = $this->sendMail($formState, $encryption);
    $statusMessage = [
      'message' => t('There was a problem with your registration! Please contact us.'),
      'type' => 'error'
    ];
    if ($message['send'] == TRUE && $this->saveOneTimeLink($formState, $encryption)) {
      $statusMessage = [
        'message' => t('Your registration has been submited with success! You will receive an email shortly!'),
        'type' => 'status'
      ];
    }

    drupal_set_message($statusMessage['message'], $statusMessage['type']);
  }

  /**
   * Saves the one time activation and cancel links.
   *
   * @param FormStateInterface $formState
   *   The form state.
   * @param string $encryption
   *   The encrypted information.
   *
   * @return bool
   *   Returns TRUE on success and FALSE on failure.
   */
  private function saveOneTimeLink(FormStateInterface $formState, $encryption) {
    try {
      $nid = $formState->get('registration_nid');
      $values = $this->buildValues($nid, $encryption);
      $query = Database::getConnection()
        ->insert(self::ONE_TIME_LINK_TABLE)
        ->fields($this->getDataTableColumns());
      foreach ($values as $value) {
        $query->values($value);
      }
      $query->execute();

      return TRUE;
    }
    catch (\Exception $exception) {
      watchdog_exception('One Time Link Save', $exception);
      return FALSE;
    }
  }

  /**
   * Returns an array of column names for multiple insert query.
   *
   * @return array
   *   The array of column names.
   */
  private function getDataTableColumns() {
    return [
      'nid',
      'link_type',
      'encryption',
      'used',
      'updated'
    ];
  }

  /**
   * Builds the values for insertion in the datatable.
   *
   * @param int $nid
   *   The registration node id.
   * @param string $encryption
   *   The encrypted information sent in the link.
   *
   * @return array
   *    The array of values.
   */
  private function buildValues($nid, $encryption) {
    return [
      $this->buildValue($nid, $encryption, self::ACTIVATION_LINK, self::LINK_IS_NOT_USED),
      $this->buildValue($nid, $encryption, self::CANCEL_LINK, self::LINK_IS_NOT_USED),
    ];
  }

  /**
   * Builds an array for one datatable entry.
   *
   * @param int $nid
   *   The registration node id.
   * @param string $encryption
   *   The encrypted information sent in the link.
   * @param int $linkType
   *   The link type.
   * @param int $linkIsUsed
   *   Link is used or not.
   *
   * @return array
   *   An array of values.
   */
  private function buildValue($nid, $encryption, $linkType, $linkIsUsed) {
    return [
      $nid,
      $linkType,
      $encryption,
      $linkIsUsed,
      time(),
    ];
  }

  /**
   * Sends email.
   *
   * @param FormStateInterface $formState
   *   The form state of the global registration form.
   * @param string $encryption
   *   The encrypted information.
   *
   * @return array
   *   Returns the message array that was sent.
   */
  private function sendMail(FormStateInterface $formState, $encryption) {
    $params = $this->generateStatusActionLinks($encryption);

    $params += $this->generateUserRegistrationInfo($formState);

    return $this->mailManager()->mail(
      'dcc_email',
      'global_training_registration',
      $formState->get('email'),
      'en',
      $params
    );
  }

  /**
   * Generates an array of parameters with user info.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state of the global registration form.
   *
   * @return array
   *   An array of user info.
   */
  private function generateUserRegistrationInfo(FormStateInterface $formState) {
    $params['registration_info'] = [
      'first_name' => $formState->get('first_name'),
      'last_name' => $formState->get('last_name'),
    ];
    $logoImageFile = file_get_contents("themes/custom/dcc_theme/image/logo.png");
    $bigImageFile = file_get_contents("themes/custom/dcc_theme/image/bigimage.png");
    $leftImageFile = file_get_contents("themes/custom/dcc_theme/image/left-image.png");
    $params['registration_info']['logo_url'] = base64_encode($logoImageFile);
    $params['registration_info']['big_image_url'] = base64_encode($bigImageFile);
    $params['registration_info']['left_image_url'] = base64_encode($leftImageFile);

    return $params;
  }

  /**
   * Generates the registration status action links.
   *
   * @param string $encryption
   *   The encrypted information.
   *
   * @return array
   *   An array of parameters that contain the links.
   */
  private function generateStatusActionLinks($encryption) {
    $activationLink = Link::createFromRoute(t(
      'Activation Link'),
      'dcc_gtd_registration.activation_link',
      [
        'encryption' => $encryption,
      ],
      [
        'absolute' => TRUE,
      ]
    );

    $cancelLink = Link::createFromRoute(t(
      'Cancel Link'),
      'dcc_gtd_registration.cancel_link',
      [
        'encryption' => $encryption,
      ],
      [
        'absolute' => TRUE,
      ]
    );

    $params['registration_status_links'] = [
      'activation_link' => $activationLink,
      'cancel_link' => $cancelLink,
    ];

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

  /**
   * Sends email.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state of the global registration form.
   *
   * @return string
   *   The encrypted string.
   */
  private function encryptRegistrationInfo(FormStateInterface $formState) {
    $firstName = $formState->get('first_name');
    $lastName = $formState->get('last_name');
    $nid = $formState->get('registration_nid');

    $in = $firstName . '/' . $lastName . '/' . $nid;

    $salt = Settings::getHashSalt();

    return Cryptor::encrypt($in, $salt, Cryptor::FORMAT_HEX);
  }

}
