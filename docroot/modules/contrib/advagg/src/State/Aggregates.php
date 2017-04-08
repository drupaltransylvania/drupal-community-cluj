<?php

namespace Drupal\advagg\State;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides AdvAgg with saved aggregrate information using a key value store.
 */
class Aggregates extends State implements StateInterface {

  /**
   * Constructs the State object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory) {
    $this->keyValueStore = $key_value_factory->get('advagg_aggregates');
    $this->pathColumn = 'uri';
  }

}
