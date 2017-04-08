<?php

namespace Drupal\advagg_css_minify\Tests;

use Drupal\advagg\Tests\AdminPagesTest;

/**
 * Tests that all the AdvAgg CSS Minifier path(s) return valid content.
 *
 * @ingroup advagg_tests
 *
 * @group advagg
 */
class CssMinifyPagesTest extends AdminPagesTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['advagg_css_minify'];

  /**
   * Routes to test.
   *
   * @var array
   */
  public $routes = ['advagg_css_minify.settings'];

}
