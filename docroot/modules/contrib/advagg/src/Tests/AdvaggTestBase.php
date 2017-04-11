<?php

namespace Drupal\advagg\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @defgroup advagg_tests Test Suit
 *
 * @{
 * The automated test suit for Advanced Aggregates.
 *
 * @}
 */

/**
 * Base test class for Advagg test cases.
 */
abstract class AdvaggTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Editable Advagg configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Editable system configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $system_config;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
    $this->config = \Drupal::configFactory()->getEditable('advagg.settings');

    // Enable aggregation.
    $this->system_config = \Drupal::configFactory()->getEditable('system.performance');
    $this->system_config->set('css.preprocess', TRUE)
      ->set('js.preprocess', TRUE)
      ->save();

  }

}
