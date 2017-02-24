<?php

namespace Drupal\advagg\State;

use Drupal\Core\State\State as CoreState;
use Drupal\Core\State\StateInterface;

/**
 * Provides AdvAgg State interfaces with a few extra commands.
 */
abstract class State extends CoreState implements StateInterface {

  /**
   * If the array isn't keyed by filepath the column the filepath is stored in.
   */
  private $pathColumn = NULL;

  /**
   * Gets all stored information from this Key Value Store.
   *
   * @return array
   *   An array of all key value pairs.
   */
  public function getAll() {
    $values = $this->keyValueStore->getAll();
    $this->cache += $values;
    return $values;
  }

  /**
   * Delete all stored information from this Key Value Store.
   */
  public function deleteAll() {
    $this->keyValueStore->deleteAll();
  }

  /**
   * Get a semi-random (randomness not guaranteed) key.
   */
  public function getRandomKey() {
    if (empty($this->cache)) {
      $this->getAll();
    }
    $key = array_rand($this->cache);
    return $key;
  }

  /**
   * Get a semi-random (randomness not guaranteed) value.
   */
  public function getRandom() {
    return $this->get($this->getRandomKey());
  }

  /**
   * Scan the filesystem for missing files and removee from database.
   */
  public function clearMissingFiles() {
    $removed = [];
    $values = $this->getAll();
    if (empty($values)) {
      return $removed;
    }
    if ($this->pathColumn) {
      $values = array_column($values, NULL, $this->pathColumn);
    }
    foreach ($values as $path => $details) {
      if (!file_exists($path)) {
        $removed[$path] = $values[$path];
        $this->delete($path);
      }
    }
    return $removed;
  }

}
