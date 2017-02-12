<?php

namespace Drupal\dcc_gtd_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\dcc_encryption\Cryptor;
use Drupal\dcc_gtd_registration\RegistrationPostSaveRule\SuccessRule;
use Drupal\dcc_gtd_scheduler\Controller\ScheduleManager;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
   * The active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * RegistrationActivationController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager interface.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->databaseConnection = Database::getConnection();
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
   * @return array|AccessDeniedHttpException
   *   Render array or access denied exception.
   */
  public function activate($encryption) {
    $decryptedInformation = $this->getDecryptedInfo($encryption);

    $this->checkOneTimeLinkUsage($decryptedInformation, $encryption, SuccessRule::ACTIVATION_LINK);

    /* @var NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($decryptedInformation['nid']);

    $message = $this->registrationStatusMessage(
      $node,
      ScheduleManager::PLACE_CONFIRMED,
      $this->messages['activation']
    );

    return [
      '#theme' => ACTIVATION_STATUS_PAGE,
      '#first_name' => $decryptedInformation['first_name'],
      '#last_name' => $decryptedInformation['last_name'],
      '#message' => $message,
    ];
  }

  /**
   * Cancel registration page controller.
   *
   * @param string $encryption
   *   A onetime activation string.
   *
   * @return array|AccessDeniedHttpException
   *   Render array or access denied exception.
   */
  public function cancel($encryption) {
    $decryptedInformation = $this->getDecryptedInfo($encryption);
    $this->checkOneTimeLinkUsage($decryptedInformation, $encryption, SuccessRule::CANCEL_LINK);

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
   * Checks if the status link has been used already.
   *
   * @param array $decryptedInformation
   *   The decrypted information.
   * @param string $encryption
   *   The encryption from the URL.
   * @param int $linkType
   *   The link type to check for: activation or cancel.
   *
   * @throws AccessDeniedException
   */
  private function checkOneTimeLinkUsage(array $decryptedInformation, $encryption, $linkType) {
    $oneTimeLink = $this->getUnusedActivationLink($decryptedInformation, $encryption, $linkType);

    $updated = 0;
    if (!is_null($oneTimeLink)) {
      $updated = $this->updateOneTimeLink($oneTimeLink);
    }

    if ($updated == 0) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Selects the unused activation entry from the data table.
   *
   * @param array $decryptedInformation
   *   The decrypted information.
   * @param string $encryption
   *   The encryption from the URL.
   * @param int $linkType
   *   The link type to check for: activation or cancel.
   *
   * @return \stdClass|null
   *   Returns the one time link entry from the data table.
   */
  private function getUnusedActivationLink(array $decryptedInformation, $encryption, $linkType) {
    $oneTimeLink = NULL;
    try {
      $query = $this->databaseConnection->select(SuccessRule::ONE_TIME_LINK_TABLE, 'r', array());
      $query->fields('r')
        ->condition('r.nid', $decryptedInformation['nid'])
        ->condition('r.link_type', $linkType)
        ->condition('r.encryption', $encryption)
        ->condition('r.used', SuccessRule::LINK_IS_NOT_USED);

      $results = $query->execute()->fetchAll();

      $oneTimeLink = $this->getOneTimeLinkResult($results);
    }
    catch (\Exception $exception) {
      watchdog_exception('One Time Link Select', $exception);
    }

    return $oneTimeLink;
  }

  /**
   * Gets the one time activation entry from the database query results.
   *
   * @param array $results
   *   The results from the database.
   *
   * @return \stdClass|null
   *   The one time link object.
   */
  private function getOneTimeLinkResult(array $results) {
    $output = NULL;
    if ($results) {
      $output = $results[0];
    }

    return $output;
  }

  /**
   * Updates the database entry and sets the one time link to used.
   *
   * @param \stdClass $oneTimeLink
   *   The one time link entry from the database.
   *
   * @return int
   *   Returns the number of updated entries.
   */
  private function updateOneTimeLink(\stdClass $oneTimeLink) {
    $updatedAmount = 0;
    if ($oneTimeLink) {
      $fields = [
        'used' => SuccessRule::LINK_IS_USED,
        'updated' => time(),
      ];

      $updatedAmount = $this->databaseConnection->update(SuccessRule::ONE_TIME_LINK_TABLE)
        ->fields($fields)
        ->condition('lid', $oneTimeLink->lid)
        ->execute();
    }

    return $updatedAmount;
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
