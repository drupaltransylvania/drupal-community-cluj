<?php

namespace Drupal\advagg_css_minify\Asset;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\CssOptimizer as CoreCssOptimizer;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Optimizes a CSS asset.
 */
class CssOptimizer extends CoreCssOptimizer implements AssetOptimizerInterface {

  /**
   * The minify cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * A config object for the advagg css minify configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A config object for the advagg configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $advaggConfig;

  /**
   * The AdvAgg file status state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct the optimizer instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $minify_cache
   *   The minify cache.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(CacheBackendInterface $minify_cache, ConfigFactoryInterface $config_factory, StateInterface $advagg_files, ModuleHandlerInterface $module_handler) {
    $this->cache = $minify_cache;
    $this->config = $config_factory->get('advagg_css_minify.settings');
    $this->advaggConfig = $config_factory->get('advagg.settings');
    $this->advaggFiles = $advagg_files;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Generate the css minification configuration.
   *
   * @return array
   *   Array($options, $description, $minifiers, $functions).
   */
  public function getConfiguration() {
    $description = '';
    $options = [
      0 => t('Disabled'),
      1 => t('Core'),
      2 => t('YUI'),
    ];

    $minifiers = [NULL, NULL, NULL];
    $functions = [NULL, NULL, NULL];

    // Allow for other modules to alter this list.
    $options_desc = [$options, $description];
    $this->moduleHandler->alter('advagg_css_minify_configuration', $options_desc, $minifiers, $functions);
    list($options, $description) = $options_desc;

    return [$options, $description, $minifiers, $functions];
  }

  /**
   * Loads the stylesheet and resolves all @import commands.
   *
   * Loads a stylesheet and replaces @import commands with the contents of the
   * imported file. Use this instead of file_get_contents when processing
   * stylesheets.
   *
   * The returned contents are compressed removing white space and comments only
   * when CSS aggregation is enabled. This optimization will not apply for
   * color.module enabled themes with CSS aggregation turned off.
   *
   * Note: the only reason this method is public is so color.module can call it;
   * it is not on the AssetOptimizerInterface, so future refactorings can make
   * it protected.
   *
   * @param string $file
   *   Name of the stylesheet to be processed.
   * @param bool $optimize
   *   (optional) Defines if CSS contents should be compressed or not. Not used
   *   in AdvAgg implementation.
   * @param bool $reset_basepath
   *   (optional) Used internally to facilitate recursive resolution of @import
   *   commands.
   *
   * @return string
   *   Contents of the stylesheet, including any resolved @import commands.
   */
  public function loadFile($file, $optimize = NULL, $reset_basepath = TRUE) {
    // These statics are not cache variables, so we don't use drupal_static().
    static $basepath;
    if ($reset_basepath) {
      $basepath = '';
    }

    // Stylesheets are relative one to each other. Start by adding a base path
    // prefix provided by the parent stylesheet (if necessary).
    if ($basepath && !file_uri_scheme($file)) {
      $file = $basepath . '/' . $file;
    }
    // Store the parent base path to restore it later.
    $parent_base_path = $basepath;
    // Set the current base path to process possible child imports.
    $basepath = dirname($file);

    // Load the CSS stylesheet. We suppress errors because themes may specify
    // stylesheets in their .info.yml file that don't exist in the theme's path,
    // but are merely there to disable certain module CSS files.
    $content = '';
    if ($contents = @file_get_contents($file)) {
      // If a BOM is found, convert the file to UTF-8, then use substr() to
      // remove the BOM from the result.
      if ($encoding = (Unicode::encodingFromBOM($contents))) {
        $contents = Unicode::substr(Unicode::convertToUtf8($contents, $encoding), 1);
      }
      // If no BOM, check for fallback encoding. Per CSS spec the regex is very
      // strict.
      elseif (preg_match('/^@charset "([^"]+)";/', $contents, $matches)) {
        if ($matches[1] !== 'utf-8' && $matches[1] !== 'UTF-8') {
          $contents = substr($contents, strlen($matches[0]));
          $contents = Unicode::convertToUtf8($contents, $matches[1]);
        }
      }

      $minifier = $this->config->get('minifier');
      if ($file_settings = $this->config->get('file_settings')) {
        $file_settings = array_column($file_settings, 'minifier', 'path');
        if (isset($file_settings[$file])) {
          $minifier = $file_settings[$file];
        }
      }

      $info = $this->advaggFiles->get($file);
      $cid = 'css_minify:' . $minifier . ':' . $info['filename_hash'];
      $cid .= !empty($info['content_hash']) ? ':' . $info['content_hash'] : '';
      $cached_data = $this->cache->get($cid);
      if (!empty($cached_data->data)) {
        $content = $cached_data->data;
      }
      else {
        if (!$minifier || $this->advaggConfig->get('cache_level') < 0) {
          $content = $this->processCss($contents, FALSE);
        }
        elseif ($minifier == 1) {
          $content = $this->processCss($contents, TRUE);
        }
        elseif ($minifier == 2) {
          $content = $this->processCssMin($contents);
        }
        else {
          $content = $this->processCssOther($contents, $minifier);
        }
      }
      // Cache minified data for at least 1 week.
      $this->cache->set($cid, $content, REQUEST_TIME + (86400 * 7), ['advagg_css', $info['filename_hash']]);
    }

    // Restore the parent base path as the file and its children are processed.
    $basepath = $parent_base_path;
    return $content;
  }

  /**
   * Processes the contents of a stylesheet through CSSMin for minification.
   *
   * @param string $contents
   *   The contents of the stylesheet.
   *
   * @return string
   *   Minified contents of the stylesheet including the imported stylesheets.
   */
  protected function processCssMin($contents) {
    $contents = $this->clean($contents);
    if (!class_exists('CSSmin')) {
      include drupal_get_path('module', 'advagg_css_minify') . '/yui/CSSMin.inc';
    }
    $cssmin = new \CSSmin(TRUE);

    // Minify the CSS splitting lines after 4k of text.
    $contents = $cssmin->run($contents, 4096);

    // Replaces @import commands with the actual stylesheet content.
    // This happens recursively but omits external files.
    $contents = preg_replace_callback('/@import\s*(?:url\(\s*)?[\'"]?(?![a-z]+:)(?!\/\/)([^\'"\()]+)[\'"]?\s*\)?\s*;/', [$this, 'loadNestedFile'], $contents);

    return $contents;
  }

  /**
   * Processes the contents of a stylesheet for minification.
   *
   * @param string $contents
   *   The contents of the stylesheet.
   *
   * @return string
   *   Minified contents of the stylesheet including the imported stylesheets.
   */
  protected function processCssOther($contents, $minifier) {
    $contents = $this->clean($contents);
    list(, , , $functions) = $this->getConfiguration();
    if (isset($functions[$minifier])) {
      $run = $functions[$minifier];
      if (function_exists($run)) {
        $run($contents);
      }
    }
    // Replaces @import commands with the actual stylesheet content.
    // This happens recursively but omits external files.
    $contents = preg_replace_callback('/@import\s*(?:url\(\s*)?[\'"]?(?![a-z]+:)(?!\/\/)([^\'"\()]+)[\'"]?\s*\)?\s*;/', [$this, 'loadNestedFile'], $contents);

    return $contents;
  }

}
