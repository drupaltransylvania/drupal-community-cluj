<?php

namespace Drupal\advagg_js_minify\Asset;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Optimizes a JavaScript asset.
 */
class JsOptimizer implements AssetOptimizerInterface {

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
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(CacheBackendInterface $minify_cache, ConfigFactoryInterface $config_factory, StateInterface $advagg_files, ModuleHandlerInterface $module_handler, LoggerInterface $logger) {
    $this->cache = $minify_cache;
    $this->config = $config_factory->get('advagg_js_minify.settings');
    $this->advaggConfig = $config_factory->get('advagg.settings');
    $this->advaggFiles = $advagg_files;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
  }

  /**
   * Generate the js minify configuration.
   *
   * @return array
   *   Array($options, $description, $compressors, $functions).
   */
  public function getConfiguration() {
    // Set the defaults.
    $description = '';
    $options = [
      0 => t('Disabled'),
      1 => t('JSMin+ ~1300ms'),
      // 2 => t('Packer ~500ms'),
      // 3 is JSMin c extension.
      4 => t('JShrink ~1000ms'),
      5 => t('JSqueeze ~600ms'),
    ];
    if (function_exists('jsmin')) {
      $options[3] = t('JSMin ~2ms');
      $description .= t('JSMin is the very fast C complied version. Recommend using it.');
    }
    else {
      $description .= t('You can use the much faster C version of JSMin (~2ms) by installing the <a href="@php_jsmin">JSMin PHP Extension</a> on this server.', [
        '@php_jsmin' => 'https://github.com/sqmk/pecl-jsmin/',
      ]);
    }

    $minifiers = [
      1 => 'jsminplus',
      2 => 'packer',
      4 => 'jshrink',
      5 => 'jsqueeze',
    ];
    if (function_exists('jsmin')) {
      $minifiers[3] = 'jsmin';
    }

    $functions = [
      1 => [$this, 'minifyJsminplus'],
      2 => [$this, 'minifyJspacker'],
      3 => [$this, 'minifyJsmin'],
      4 => [$this, 'minifyJshrink'],
      5 => [$this, 'minifyJsqueeze'],
    ];

    // Allow for other modules to alter this list.
    $options_desc = [$options, $description];

    // Call hook_advagg_js_minify_configuration_alter().
    $this->moduleHandler->alter('advagg_js_minify_configuration', $options_desc, $minifiers, $functions);
    list($options, $description) = $options_desc;

    return [$options, $description, $minifiers, $functions];
  }

  /**
   * {@inheritdoc}
   */
  public function optimize(array $js_asset) {
    if ($js_asset['type'] !== 'file') {
      throw new \Exception('Only file JavaScript assets can be optimized.');
    }
    if ($js_asset['type'] === 'file' && !$js_asset['preprocess']) {
      throw new \Exception('Only file JavaScript assets with preprocessing enabled can be optimized.');
    }

    // If a BOM is found, convert the file to UTF-8, then use substr() to
    // remove the BOM from the result.
    $data = file_get_contents($js_asset['data']);
    if ($encoding = (Unicode::encodingFromBOM($data))) {
      $data = Unicode::substr(Unicode::convertToUtf8($data, $encoding), 1);
    }

    // If no BOM is found, check for the charset attribute.
    elseif (isset($js_asset['attributes']['charset'])) {
      $data = Unicode::convertToUtf8($data, $js_asset['attributes']['charset']);
    }
    $minifier = $this->config->get('minifier');
    if ($file_settings = $this->config->get('file_settings')) {
      $file_settings = array_column($file_settings, 'minifier', 'path');
      if (isset($file_settings[$js_asset['data']])) {
        $minifier = $file_settings[$js_asset['data']];
      }
    }

    // Do nothing if js file minification is disabled.
    if (empty($minifier) || $this->advaggConfig->get('cache_level') < 0) {
      return $data;
    }

    // Do not re-minify if the file is already minified.
    $semicolon_count = substr_count($data, ';');
    // @ignore sniffer_whitespace_openbracketspacing_openingwhitespace
    if ( $minifier != 2
      && $semicolon_count > 10
      && $semicolon_count > (substr_count($data, "\n", strpos($data, ';')) * 5)
      ) {
      if ($this->config->get('add_license')) {
        $url = file_create_url($js_asset['data']);
        $data = "/* Source and licensing information for the line(s) below can be found at $url. */\n" . $data . "\n/* Source and licensing information for the above line(s) can be found at $url. */";
      }
      return $data;
    }

    $data_original = $data;
    $before = strlen($data);

    $info = $this->advaggFiles->get($js_asset['data']);
    $cid = 'js_minify:' . $minifier . ':' . $info['filename_hash'];
    $cid .= !empty($info['content_hash']) ? ':' . $info['content_hash'] : '';
    $cached_data = $this->cache->get($cid);
    if (!empty($cached_data->data)) {
      $data = $cached_data->data;
    }
    else {
      // Use the minifier.
      list(, , , $functions) = $this->getConfiguration();
      if (isset($functions[$minifier])) {
        $run = $functions[$minifier];
        if (is_callable($run)) {
          call_user_func_array($run, [&$data, $js_asset]);
        }
      }
      else {
        return $data;
      }

      // Ensure that $data ends with ; or }.
      if (strpbrk(substr(trim($data), -1), ';})') === FALSE) {
        $data = trim($data) . ';';
      }

      // Cache minified data for at least 1 week.
      $this->cache->set($cid, $data, REQUEST_TIME + (86400 * 7), ['advagg_js', $info['filename_hash']]);

      // Make sure minification ratios are good.
      $after = strlen($data);
      $ratio = 0;
      if ($before != 0) {
        $ratio = ($before - $after) / $before;
      }

      // Make sure the returned string is not empty or has a VERY high
      // minification ratio.
      // @ignore sniffer_whitespace_openbracketspacing_openingwhitespace
      if ( empty($data)
        || empty($ratio)
        || $ratio < 0
        || $ratio > $this->config->get('ratio_max')
      ) {
        $data = $data_original;
      }
      elseif ($this->config->get('add_license')) {
        $url = file_create_url($js_asset['data']);
        $data = "/* Source and licensing information for the line(s) below can be found at $url. */\n" . $data . "\n/* Source and licensing information for the above line(s) can be found at $url. */";
      }
    }
    return $data;
  }

  /**
   * Processes the contents of a javascript asset for cleanup.
   *
   * @param string $contents
   *   The contents of the javascript asset.
   *
   * @return string
   *   Contents of the javascript asset.
   */
  public function clean($contents) {
    // Remove JS source and source mapping urls or these may cause 404 errors.
    $contents = preg_replace('/\/\/(#|@)\s(sourceURL|sourceMappingURL)=\s*(\S*?)\s*$/m', '', $contents);

    return $contents;
  }

  /**
   * Minify a JS string using jsmin.
   *
   * @param string $contents
   *   Javascript string.
   */
  public function minifyJsmin(&$contents, $asset) {
    // Do not use jsmin() if the function can not be called.
    if (!function_exists('jsmin')) {
      $this->logger->notice(t('The jsmin function does not exist. Using JSqueeze.'), []);
      $contents = $this->minifyJsqueeze($contents);
      return;
    }

    // Jsmin doesn't handle multi-byte characters before version 2, fall back to
    // different minifier if jsmin version < 2 and $contents contains multi-
    // byte characters.
    if (version_compare(phpversion('jsmin'), '2.0.0', '<') && $this->stringContainsMultibyteCharacters($contents)) {
      $this->logger->notice('The currently installed jsmin version does not handle multibyte characters, you may concider to upgrade the jsmin extension. Using JSqueeze fallback.', []);
      $contents = $this->minifyJsqueeze($contents);
      return;
    }

    // Jsmin may have errors (incorrectly determining EOLs) with mixed tabs
    // and spaces. An example: jQuery.Cycle 3.0.3 - http://jquery.malsup.com/
    $contents = str_replace("\t", " ", $contents);

    $minified = jsmin($contents);

    // Check for JSMin errors.
    $error = jsmin_last_error_msg();
    if ($error != 'No error') {
      $this->logger->warning('JSMin had an error processing, usng JSqueeze fallback. Error details: ' . $error, []);
      $contents = $this->minifyJsqueeze($contents);
      return;
    }

    // Under some unknown/rare circumstances, JSMin can add up to 5
    // extraneous/wrong chars at the end of the string. Check and remove if
    // necessary. The chars unfortunately vary in number and specific chars.
    // Hence this is a poor quality check but should work.
    if (ctype_cntrl(substr(trim($minified), -1)) || strpbrk(substr(trim($minified), -1), ';})') === FALSE) {
      $contents = substr($minified, 0, strrpos($minified, ';'));
      $this->logger->notice(t('JSMin had an error minifying: @file, correcting.', ['@file' => $asset['data']]));
    }
    else {
      $contents = $minified;
    }
    $semicolons = substr_count($contents, ';', strlen($contents) - 5);
    if ($semicolons > 2) {
      $start = substr($contents, 0, -5);
      $contents = $start . preg_replace("/([;)}]*)([\w]*)([;)}]*)/", "$1$3", substr($contents, -5));
      $this->logger->notice(t('JSMin had an error minifying file: @file, attempting to correct.', ['@file' => $asset['data']]));
    }
  }

  /**
   * Minify a JS string using jsmin+.
   *
   * @param string $contents
   *   Javascript string.
   * @param bool $log_errors
   *   FALSE to disable logging to watchdog on failure.
   */
  public function minifyJsminplus(&$contents, $asset, $log_errors = TRUE) {
    $contents_before = $contents;

    // Only include jsminplus.inc if the JSMinPlus class doesn't exist.
    if (!class_exists('\JSMinPlus')) {
      include drupal_get_path('module', 'advagg_js_minify') . '/jsminplus.inc';
      $nesting_level = ini_get('xdebug.max_nesting_level');
      if (!empty($nesting_level) && $nesting_level < 200) {
        ini_set('xdebug.max_nesting_level', 200);
      }
    }
    ob_start();
    try {
      // JSMin+ the contents of the aggregated file.
      $contents = \JSMinPlus::minify($contents);

      // Capture any output from JSMinPlus.
      $error = trim(ob_get_contents());
      if (!empty($error)) {
        throw new \Exception($error);
      }
    }
    catch (\Exception $e) {
      // Log exception thrown by JSMin+ and roll back to uncompressed content.
      if ($log_errors) {
        $this->logger->warning($e->getMessage() . '<pre>' . $contents_before . '</pre>', []);
      }
      $contents = $contents_before;
    }
    ob_end_clean();
  }

  /**
   * Minify a JS string using packer.
   *
   * @param string $contents
   *   Javascript string.
   */
  public function minifyJspacker(&$contents, $asset) {
    // Use Packer on the contents of the aggregated file.
    if (!class_exists('\JavaScriptPacker')) {
      include drupal_get_path('module', 'advagg_js_minify') . '/jspacker.inc';
    }

    // Add semicolons to the end of lines if missing.
    $contents = str_replace("}\n", "};\n", $contents);
    $contents = str_replace("\nfunction", ";\nfunction", $contents);

    $packer = new \JavaScriptPacker($contents, 62, TRUE, FALSE);
    $contents = $packer->pack();
  }

  /**
   * Minify a JS string using jshrink.
   *
   * @param string $contents
   *   Javascript string.
   * @param bool $log_errors
   *   FALSE to disable logging to watchdog on failure.
   */
  public function minifyJshrink(&$contents, $asset, $log_errors = TRUE) {
    $contents_before = $contents;

    // Only include jshrink.inc if the JShrink\Minifier class doesn't exist.
    if (!class_exists('\JShrink\Minifier')) {
      include drupal_get_path('module', 'advagg_js_minify') . '/jshrink.inc';
      $nesting_level = ini_get('xdebug.max_nesting_level');
      if (!empty($nesting_level) && $nesting_level < 200) {
        ini_set('xdebug.max_nesting_level', 200);
      }
    }
    ob_start();
    try {
      // JShrink the contents of the aggregated file.
      $contents = \JShrink\Minifier::minify($contents, ['flaggedComments' => FALSE]);

      // Capture any output from JShrink.
      $error = trim(ob_get_contents());
      if (!empty($error)) {
        throw new \Exception($error);
      }
    }
    catch (\Exception $e) {
      // Log the JShrink exception and rollback to uncompressed content.
      if ($log_errors) {
        $this->logger->warning($e->getMessage() . '<pre>' . $contents_before . '</pre>', []);
      }
      $contents = $contents_before;
    }
    ob_end_clean();
  }

  /**
   * Minify a JS string using jsqueeze.
   *
   * @param string $contents
   *   Javascript string.
   * @param bool $log_errors
   *   FALSE to disable logging to watchdog on failure.
   */
  public function minifyJsqueeze(&$contents, $asset, $log_errors = TRUE) {
    $contents_before = $contents;

    // Only include jshrink.inc if the Patchwork\JSqueeze class doesn't exist.
    if (!class_exists('\Patchwork\JSqueeze')) {
      include drupal_get_path('module', 'advagg_js_minify') . '/jsqueeze.inc';
      $nesting_level = ini_get('xdebug.max_nesting_level');
      if (!empty($nesting_level) && $nesting_level < 200) {
        ini_set('xdebug.max_nesting_level', 200);
      }
    }
    ob_start();
    try {
      // Minify the contents of the aggregated file.
      $jz = new \Patchwork\JSqueeze();
      $contents = $jz->squeeze(
        $contents,
        TRUE,
        !\Drupal::config('advagg_js_minify.settings')->get('add_license'),
        FALSE
      );

      // Capture any output from JSqueeze.
      $error = trim(ob_get_contents());
      if (!empty($error)) {
        throw new \Exception($error);
      }
    }
    catch (\Exception $e) {
      // Log the JSqueeze exception and rollback to uncompressed content.
      if ($log_errors) {
        $this->logger->warning('JSqueeze error, skipping file. ' . $e->getMessage() . '<pre>' . $contents_before . '</pre>', []);
      }
      $contents = $contents_before;
    }
    ob_end_clean();
  }

  /**
   * Checks if string contains multibyte characters.
   *
   * @param string $string
   *   String to check.
   *
   * @return bool
   *   TRUE if string contains multibyte character.
   */
  public function stringContainsMultibyteCharacters($string) {
    // Check if there are multi-byte characters: If the UTF-8 encoded string has
    // multibytes strlen() will return a byte-count greater than the actual
    // character count, returned by drupal_strlen().
    if (strlen($string) == drupal_strlen($string)) {
      return FALSE;
    }

    return TRUE;
  }

}
