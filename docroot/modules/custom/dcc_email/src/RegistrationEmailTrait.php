<?php

namespace Drupal\dcc_email;

use Drupal\Core\Link;
use Drupal\dcc_email\Entity\PersonalInformation;
use Drupal\dcc_gtd_registration\RegistrationPostSaveRule\SuccessRule;

/**
 * Class RegistrationEmailTrait.
 *
 * @package Drupal\dcc_email
 */
trait RegistrationEmailTrait {

  /**
   * Array with images and files.
   *
   * @var array
   */
  protected $images = array(
    'logo_url' => 'logo.png',
    'big_image_url' => 'bigimage.png',
    'left_image_url' => 'left-image.png',
  );

  /**
   * Build the email parameters.
   *
   * @param \Drupal\dcc_email\Entity\PersonalInformation $personalInformation
   *   Perfonal information of person.
   * @param mixed $encryption
   *   The link encryption.
   *
   * @return array
   *   Parameters of the email.
   */
  public function buildParams(PersonalInformation $personalInformation, $encryption) {
    $params['registration_status_links'] = $this->generateStatusActionLink($encryption, SuccessRule::ACTIVATION_LINK);
    $params['registration_status_links'] += $this->generateStatusActionLink($encryption, SuccessRule::CANCEL_LINK);

    $params += $this->generateUserRegistrationInfo($personalInformation);
    $this->generateImages($params);

    return $params;
  }

  /**
   * Generates an array of parameters with user info.
   *
   * @param \Drupal\dcc_email\Entity\PersonalInformation $personalInformation
   *   The personal information of the receiver email.
   *
   * @return array
   *   An array of user info.
   */
  private function generateUserRegistrationInfo(PersonalInformation $personalInformation) {
    $params['registration_info'] = [
      'first_name' => $personalInformation->getFirstName(),
      'last_name' => $personalInformation->getLastName(),
    ];

    return $params;
  }

  /**
   * Generates the images.
   *
   * @param mixed $params
   *   Parameters of the email.
   */
  public function generateImages(&$params) {
    $basePath = 'themes/custom/dcc_theme/image/';

    foreach ($this->images as $key => $imageName) {
      $params['registration_info']['images'][$key] = $this->getEncodedImage($basePath . $imageName);
    }
  }

  /**
   * Encode a file content.
   *
   * @param string $imagePath
   *   Image path.
   *
   * @return string
   *   Encoded file content.
   */
  private function getEncodedImage($imagePath) {
    return base64_encode(file_get_contents($imagePath));
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
  private function generateStatusActionLink($encryption, $type) {
    if ($type) {
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

      $output = [
        'activation_link' => $activationLink,
      ];
    }
    else {
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

      $output = [
        'cancel_link' => $cancelLink,
      ];
    }

    return $output;
  }

}
