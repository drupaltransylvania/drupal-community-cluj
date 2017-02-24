<?php

namespace Drupal\advagg\Tests;

use Drupal\Core\Url;

/**
 * Tests that all the asset path settings function correctly.
 *
 * @group advagg
 */
class AdvaggPathManipulationTest extends AdvaggTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg', 'advagg_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests force_https.
   */
  public function testForceHttps() {
    $this->config->set('path.convert.force_https', TRUE)
      ->set('path.convert.absolute_to_protocol_relative', FALSE)
      ->save();
    $this->drupalGet('');
    $this->assertRaw('src="https://cdn.jsdelivr.net/jquery.actual/1.0.18/jquery.actual.min.js');
  }

  /**
   * Tests absolute_to_protocol_relative.
   */
  public function testAbsoluteToProtocolRelative() {
    $this->config->set('path.convert.absolute_to_protocol_relative', TRUE)
      ->set('path.convert.force_https', FALSE)
      ->save();
    $this->drupalGet('');
    $this->assertRaw('src="//cdn.jsdelivr.net/jquery.actual/1.0.18/jquery.actual.min.js');
  }

}
