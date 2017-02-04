<?php

namespace Drupal\dcc_gtd_registration;

use Drupal\Core\Access\AccessResult;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;

/**
 * Class RegistrationAccess.
 *
 * @package Drupal\dcc_gtd_registration
 */
class RegistrationAccess {

  /**
   * Allows access if there is an open registration window.
   */
  public function access() {
    if (self::getCurrentSessionNid() != -1) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden("Not in a registration period.");
  }

  /**
   * Provides the session with an open registration period.
   *
   * @return int
   *   The node id or -1 if none.
   */
  public static function getCurrentSessionNid() {
    $sessions = \Drupal::service('entity.manager')->getStorage('node')->loadByProperties(['type' => 'drupal_training_scheduler']);
    $now = new \DateTime("now");
    // Iterate through all training sessions and check
    // if we are in a registration period.
    foreach ($sessions as $nid => $session) {
      $range = $session->field_registration_period;
      if ($range instanceof DateRangeFieldItemList && !empty($range->getValue())) {
        $start = \DateTime::createFromFormat('Y-m-d\TH:i:s', $range->getValue()[0]['value']);
        $end = \DateTime::createFromFormat('Y-m-d\TH:i:s', $range->getValue()[0]['end_value']);
        if ($start instanceof \DateTime && $end instanceof \DateTime) {
          if ($start <= $now && $now <= $end) {
            return $nid;
          }
        }
      }
    }
    return -1;
  }

}
