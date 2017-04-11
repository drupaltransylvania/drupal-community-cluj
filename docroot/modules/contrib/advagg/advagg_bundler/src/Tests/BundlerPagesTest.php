<?php

namespace Drupal\advagg_bundler\Tests;

use Drupal\advagg\Tests\AdminPagesTest;

/**
 * Tests that all the AdvAgg Bundler path(s) return valid content.
 *
 * @ingroup advagg_tests
 *
 * @group advagg
 */
class BundlerPagesTest extends AdminPagesTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg_bundler'];

  /**
   * Routes to test.
   *
   * @var array
   */
  public $routes = ['advagg_bundler.settings'];

}
