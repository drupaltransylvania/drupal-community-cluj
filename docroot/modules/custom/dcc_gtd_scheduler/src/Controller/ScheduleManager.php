<?php

namespace Drupal\dcc_gtd_scheduler\Controller;

use DateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ScheduleManager.
 *
 * @package Drupal\dcc_gtd_scheduler\Controller
 */
class ScheduleManager implements ScheduleManagerInterface {

  const WAITING_FOR_CONFIRMATION = 0;
  const PLACE_CONFIRMED = 1;
  const PLACE_CANCELED = 2;
  const ON_WAITING_LIST = 3;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ScheduleManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
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
   * Gets the nid of the latest schedule that's currently active.
   *
   * @return mixed|null
   *   The ID of the active schedule or NULL if no schedule is currently active.
   */
  public function getActiveSchedulerId() {
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Query the database for the last added training scheduler which has the
    // registration period open.
    $query = $node_storage->getQuery();
    $query->condition('type', 'drupal_training_scheduler');
    $query->condition('field_registration_period.value', date('Y-m-d\TH:i:s'), '<');
    $query->condition('field_registration_period.end_value', date('Y-m-d\TH:i:s'), '>');
    $query->sort('nid', 'desc');
    $query->range(0, 1);
    $nid = $query->execute();
    if (empty($nid)) {
      return NULL;
    }
    return current($nid);
  }

  /**
   * Gets the remaining number of places for a given session.
   *
   * @param int $schedule_id
   *   The Schedule ID.
   *
   * @return int|null
   *   The number of remaining places or null if the schedule ID is invalid.
   */
  public function getRemainingPlaces($schedule_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Attempt to load the schedule by ID.
    $schedule = $node_storage->load($schedule_id);
    if ($schedule == NULL) {
      return NULL;
    }

    // Count the number of users for a given session which have the place
    // confirmed.
    $query = $node_storage->getQuery();
    $query->condition('type', 'global_training_day_registration');
    $query->condition('field_training_session', $schedule_id, '=');
    $query->condition('field_registration_status', ScheduleManager::PLACE_CONFIRMED, '=');
    $registered_count = $query->count()->execute();

    // Retrieve the number of places for the given session.
    $max_count = $schedule->get('field_number_of_members')->value;

    // Return the number of places available.
    return $max_count - $registered_count;
  }

}
