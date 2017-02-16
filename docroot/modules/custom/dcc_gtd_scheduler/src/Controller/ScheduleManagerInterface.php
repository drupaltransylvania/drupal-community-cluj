<?php

namespace Drupal\dcc_gtd_scheduler\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface ScheduleManagerInterface.
 *
 * @package Drupal\dcc_gtd_scheduler\Controller
 */
interface ScheduleManagerInterface extends ContainerInjectionInterface {

  /**
   * Gets the nid of the latest schedule that's currently active.
   *
   * @return int|null
   *   The ID of the active schedule or NULL if no schedule is currently active.
   */
  public function getActiveSchedulerId();

  /**
   * Gets the last created schedule that's currently active.
   *
   * @return EntityInterface|NULL
   *   The active schedule or NULL if no schedule is currently active.
   */
  public function getActiveScheduler();

  /**
   * Gets the number of remaining seats for a given schedule.
   *
   * @param int $schedule_id
   *   The id of the schedule for which to retrieve open seats.
   *
   * @return int|null
   *   The number of seats remaining or null if the schedule is invalid.
   */
  public function getRemainingPlaces($schedule_id);

}