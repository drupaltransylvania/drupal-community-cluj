<?php

namespace Drupal\advagg_js_minify\Tests;

use Drupal\advagg\Tests\AdminPagesTest;

/**
 * Tests that all the AdvAgg JS Minifier path(s) return valid content.
 *
 * @ingroup advagg_tests
 *
 * @group advagg
 */
class JsMinifyPagesTest extends AdminPagesTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg_js_minify'];

  /**
   * Routes to test.
   *
   * @var array
   */
  public $routes = ['advagg_js_minify.settings'];

}
