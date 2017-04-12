<?php

namespace Drupal\advagg_cdn\Tests;

use Drupal\advagg\Tests\AdminPagesTest;

/**
 * Tests that all the AdvAgg CDN path(s) return valid content.
 *
 * @ingroup advagg_tests
 *
 * @group advagg
 */
class CdnPagesTest extends AdminPagesTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg_cdn'];

  /**
   * Routes to test.
   *
   * @var array
   */
  public $routes = ['advagg_cdn.settings'];

}
