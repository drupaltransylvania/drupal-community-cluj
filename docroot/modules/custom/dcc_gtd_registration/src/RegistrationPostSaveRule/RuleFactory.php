<?php

namespace Drupal\dcc_gtd_registration\RegistrationPostSaveRule;

/**
 * Class RuleFactory.
 *
 * @package Drupal\dcc_gtd_registration\RegistrationPostSaveRule
 */
class RuleFactory {

  /**
   * Creates rule instances for global registration post save operations.
   *
   * @return \ArrayObject
   *   An array of rule instances.
   */
  public static function buildRules() {

    $rules = [
      new NodeNotSavedRule(),
      new NoEmailRule(),
      new MissingNodeIdRule(),
      new SuccessRule(),
    ];

    return new \ArrayObject($rules);
  }

}
