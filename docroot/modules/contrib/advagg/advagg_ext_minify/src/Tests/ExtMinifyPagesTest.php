<?php

namespace Drupal\advagg_ext_minify\Tests;

use Drupal\advagg\Tests\AdminPagesTest;

/**
 * Tests that all the AdvAgg External Minification path(s) return valid content.
 *
 * @ingroup advagg_tests
 *
 * @group advagg
 */
class ExtMinifyPagesTest extends AdminPagesTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg_ext_minify'];

  /**
   * Routes to test.
   *
   * @var array
   */
  public $routes = ['advagg_ext_minify.settings'];

}
