<?php

namespace Drupal\dcc_gtd_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\dcc_encryption\Cryptor;
use Drupal\dcc_gtd_scheduler\Controller\ScheduleManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RegistrationStatusController.
 *
 * @package Drupal\dcc_gtd_registration\Controller
 */
class RegistrationStatusController extends ControllerBase {

  /**
   * An array of status messages displayed for the user.
   *
   * @var array
   */
  private $messages = [
    'activation' => [
      'success' => 'Your registration has been activated successfully.',
      'failure' => 'Your activation failed. Please contact the administrators.',
    ],
    'cancel' => [
      'success' => 'Your registration has been canceled successfully.',
      'failure' => 'Your registration cancel failed. Please contact the administrators.',
    ]
  ];

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RegistrationActivationController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Registration activation page controller.
   *
   * @param string $encryption
   *   A onetime activation string.
   *
   * @return array
   *   Render array.
   */
  public function activate($encryption) {
    $decryptedInformation = $this->getDecryptedInfo($encryption);

    /* @var NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($decryptedInformation['nid']);

    return [
      '#theme' => ACTIVATION_STATUS_PAGE,
      '#first_name' => $decryptedInformation['first_name'],
      '#last_name' => $decryptedInformation['last_name'],
      '#message' => $this->registrationStatusMessage($node, ScheduleManager::PLACE_CONFIRMED, $this->messages['activation']),
    ];
  }

  /**
   * Cancel registration page controller.
   *
   * @param string $encryption
   *   A onetime activation string.
   *
   * @return array
   *   Render array.
   */
  public function cancel($encryption) {
    $decryptedInformation = $this->getDecryptedInfo($encryption);

    /* @var NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($decryptedInformation['nid']);

    return [
      '#theme' => ACTIVATION_STATUS_PAGE,
      '#first_name' => $decryptedInformation['first_name'],
      '#last_name' => $decryptedInformation['last_name'],
      '#message' => $this->registrationStatusMessage($node, ScheduleManager::PLACE_CANCELED, $this->messages['cancel']),
    ];
  }

  /**
   * Displays the message after node save.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The registration node.
   * @param int $registrationStatusCode
   *   The registration status code.
   * @param array $messages
   *   An array of status messages displayed for the user.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The status message of the node save.
   */
  private function registrationStatusMessage(NodeInterface $node, $registrationStatusCode, array $messages) {
    $registration_status = $registrationStatusCode;

    try {
      $node->set('field_registration_status', $registration_status);
      $node->save();
      $key = 'success';
    }
    catch (\Exception $exception) {
      $key = 'failure';
      watchdog_exception('Registration Status', $exception);
    }

    return $messages[$key];
  }

  /**
   * Gets the information from the encrypted string in the URL.
   *
   * @param string $encryption
   *   The encrypted string from the URL.
   *
   * @return array
   *   An array of information from the decrypted data.
   */
  private function getDecryptedInfo($encryption) {
    $hashSalt = Settings::getHashSalt();
    $decrypted = Cryptor::decrypt($encryption, $hashSalt, Cryptor::FORMAT_HEX);
    $decryptedInfo = explode('/', $decrypted);

    return $this->processDecryptedInfo($decryptedInfo);
  }

  /**
   * Defines the field names for information that comes from the decryption.
   *
   * @return array
   *   An array of field names.
   */
  private function getDecryptionKeyMatching() {
    return [
      'first_name',
      'last_name',
      'nid',
    ];
  }

  /**
   * Processes the decrypted information.
   *
   * @param array $decryptedInfo
   *   The original decrypted array.
   *
   * @return array
   *   An array of decrypted info with the correct field names.
   */
  private function processDecryptedInfo(array $decryptedInfo) {
    $processedInfo = [];
    foreach ($this->getDecryptionKeyMatching() as $key => $value) {
      $processedInfo[$value] = $decryptedInfo[$key];
    }

    return $processedInfo;
  }

}
