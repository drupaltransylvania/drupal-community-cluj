<?php

namespace Drupal\dcc_gtd_registration\Service;

use Drupal\Core\Database\Database;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList;

class AddEntityToQueue {
  const INTERVAL_PROPERTY = "d";
  const INTERVAL_VALUE = 2;

  public function addToQueue() {
    $correctDate= $this->aWeekBeforeTraining();
    if($correctDate){
      $personAndEncryption = $this->getEcnryptedDataByRegistration();
      $queue = \Drupal::queue('email_queue');
      $queue->createQueue();
      foreach ($personAndEncryption as $person ){
        $queue->createItem($person);
      }
    }
  }
  
  private function getEcnryptedDataByRegistration() {
    $connection = Database::getConnection();
    $query = $connection->select('node__field_first_name', 'f');
    $query->join('node__field_last_name', 'l', 'f.entity_id = l.entity_id');
    $query->join('node__field_registration_status', 'r', 'f.entity_id = r.entity_id');
    $query->join('dcc_gtd_registration_one_time_link', 'o', 'f.entity_id = o.nid');
    $query->join('node__field_email', 'e', 'f.entity_id = e.entity_id');
    $query->condition('r.field_registration_status_value', 1);
    $query->condition('o.link_type', 0);
    $query->condition('o.used', 0);
    $query->addField('f', 'field_first_name_value');
    $query->addField('l', 'field_last_name_value');
    $query->addField('o', 'encryption');
    $query->addField('e', 'field_email_value');

    $results = $query->execute()->fetchAll();

    return $results;
  }

  public function aWeekBeforeTraining() {
    $result = FALSE;
    $sessions = \Drupal::service('entity_type.manager')->getStorage('node')->loadByProperties(['type' => 'drupal_training_scheduler']);
    $session = array_pop($sessions);
    $range = $session->field_registration_period;
    if($this->compareTrainingRegistrationDate($range) == self::INTERVAL_VALUE){
      $result = TRUE;
    }
    return $result;
  }
  private function compareTrainingRegistrationDate( DateRangeFieldItemList $range) {
    $result = 0;
    if(!empty($range->getValue())){
      $now = new \DateTime("now");
      $start = \DateTime::createFromFormat('Y-m-d\TH:i:s', $range->getValue()[0]['value']);
      $interval = $now->diff($start);
      $result= $interval->{self::INTERVAL_PROPERTY};
    }
   return $result;
  }
}