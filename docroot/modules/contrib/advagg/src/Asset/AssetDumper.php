<?php

namespace Drupal\advagg\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Dumps a CSS or JavaScript asset.
 */
class AssetDumper implements AssetDumperInterface {

  /**
   * The path to use for saving the asset.
   *
   * @var string
   */
  protected $path;

  /**
   * The extension to use.
   *
   * @var string
   */
  protected $extension;

  /**
   * Hash of the AdvAgg settings.
   *
   * @var string
   */
  protected $hash;

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Construct the AssetDumper instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->config = $config_factory->get('system.performance');
    $this->hash = '_' . advagg_get_current_hooks_hash() . '.';
    $this->moduleHandler = $module_handler;
  }

  /**
   * Set the folder path to save the data to.
   *
   * @param string $file_extension
   *   The extension of the file.
   */
  public function preparePath($file_extension) {
    $this->extension = $file_extension;
    $this->path = 'public://' . $this->extension . '/';

    // Allow other modules to alter the file path.
    // Call hook_advagg_asset_path_alter().
    $this->moduleHandler->alter('advagg_asset_path', $this->path, $this->extension);
    file_prepare_directory($this->path, FILE_CREATE_DIRECTORY);
    return $this->path;
  }

  /**
   * {@inheritdoc}
   *
   * The file name for the CSS or JS cache file is generated from the hash of
   * the aggregated contents of the files in $data. This forces proxies and
   * browsers to download new CSS when the CSS changes.
   */
  public function dump($data, $file_extension) {
    if (!isset($this->extension) || $this->extension != $file_extension) {
      $this->preparePath($file_extension);
    }

    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = $this->extension . '_' . Crypt::hashBase64($data) . $this->hash . $this->extension;
    $uri = $this->path . $filename;

    // Create the CSS or JS file.
    if (!file_exists($uri) && !file_unmanaged_save_data($data, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }

    // If CSS/JS gzip compression is enabled and the zlib extension is available
    // then create a gzipped version of this file. This file is served
    // conditionally to browsers that accept gzip using .htaccess rules.
    // It's possible that the rewrite rules in .htaccess aren't working on this
    // server, but there's no harm (other than the time spent generating the
    // file) in generating the file anyway. Sites on servers where rewrite rules
    // aren't working can set css.gzip to FALSE in order to skip
    // generating a file that won't be used.
    if (extension_loaded('zlib') && $this->config->get($file_extension . '.gzip')) {
      if (!file_exists($uri . '.gz') && !file_unmanaged_save_data(gzencode($data, 9, FORCE_GZIP), $uri . '.gz', FILE_EXISTS_REPLACE)) {
        return FALSE;
      }
    }
    return [$uri, $filename];
  }

}
