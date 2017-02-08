<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\dcc_encryption\Cryptor;
use Drupal\dcc_gtd_registration\Form\GlobalTrainingRegistrationForm;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SuccessRule.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
class SuccessRule implements RegistrationPostSaveRule {

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
    $this->sendMail($formState);
    drupal_set_message(t('Your registration has been submited with success!'));
  }

  /**
   * Sends email.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state of the global registration form.
   */
  private function sendMail(FormStateInterface $formState) {
    $encryption = $this->encryptRegistrationInfo($formState);
    $params = $this->generateStatusActionLinks($encryption);

    $params += $this->generateUserRegistrationInfo($formState);
    $this->mailManager()->mail(
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
