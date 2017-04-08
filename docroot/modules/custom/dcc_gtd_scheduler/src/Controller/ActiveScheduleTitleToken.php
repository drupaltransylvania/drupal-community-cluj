<?php

namespace Drupal\dcc_gtd_scheduler\Controller;

/**
 * Class used as a service to provide token info and token replacement regarding currentlly active training.
 *
 * @package Drupal\dcc_gtd_scheduler\Controller
 */
class ActiveScheduleTitleToken implements TokenGeneratorInterface {

  /**
   * The scheduleManager service.
   *
   * @var \Drupal\dcc_gtd_scheduler\Controller\ScheduleManager
   */
  private $scheduleManager;

  /**
   * ActiveScheduleTitleToken constructor.
   *
   * @param \Drupal\dcc_gtd_scheduler\Controller\ScheduleManager $scheduleManager
   *   Variable used to access scheduleManager service created in module.
   */
  public function __construct(ScheduleManager $scheduleManager) {
    $this->scheduleManager = $scheduleManager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateToken($type, array $tokens) {

    $replacements = array();
    if ($type = 'scheduler') {

      $node = $this->scheduleManager->getActiveScheduler();
      if ($node) {
        foreach ($tokens as $name => $original) {
          if ($original == "[scheduler:title]") {
            $replacements[$original] = $node->getTitle();
          }
        }
      }
    }
    return $replacements;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenInfo() {

    $types['scheduler'] = array(
      'name' => 'Active Scheduler',
      'description' => 'Tokens related to the currentlly active Training.',
    );

    $tokens_array = [
      'scheduler' => [
        'title' => [
          'name' => 'title',
          'description' => 'The title of the currentlly active training',
        ],
      ],
    ];

    return array(
      'types' => $types,
      'tokens' => $tokens_array,
    );
  }

}
