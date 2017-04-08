<?php

namespace Drupal\advagg\Asset;

use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\JsCollectionOptimizer as CoreJsCollectionOptimizer;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * {@inheritdoc}
 */
class JsCollectionOptimizer extends CoreJsCollectionOptimizer implements AssetCollectionOptimizerInterface {

  /**
   * A config object for the advagg configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $systemConfig;

  /**
   * A state information store for the AdvAgg generated aggregates.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggAggregates;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Hash of the AdvAgg settings.
   *
   * @var string
   */
  protected $settingsHash;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\State\StateInterface $advagg_aggregates
   *   A state information store for the AdvAgg generated aggregates.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(AssetCollectionGrouperInterface $grouper, AssetOptimizerInterface $optimizer, AssetDumperInterface $dumper, StateInterface $state, ConfigFactoryInterface $config_factory, StateInterface $advagg_aggregates, ModuleHandlerInterface $module_handler) {
    $this->grouper = $grouper;
    $this->optimizer = $optimizer;
    $this->dumper = $dumper;
    $this->state = $state;
    $this->config = $config_factory->get('advagg.settings');
    $this->systemConfig = $config_factory->get('system.performance');
    $this->advaggAggregates = $advagg_aggregates;
    $this->moduleHandler = $module_handler;
    $this->settingsHash = '_' . advagg_get_current_hooks_hash();
  }

  /**
   * {@inheritdoc}
   */
  public function optimize(array $js_assets) {
    // Group the assets.
    $js_groups = $this->grouper->group($js_assets);

    // Now optimize (concatenate, not minify) and dump each asset group, unless
    // that was already done, in which case it should appear in
    // system.js_cache_files.
    // Drupal contrib can override this default JS aggregator to keep the same
    // grouping, optimizing and dumping, but change the strategy that is used to
    // determine when the aggregate should be rebuilt (e.g. mtime, HTTPS …).
    $js_assets = [];
    $protocol_relative = $this->config->get('path.convert.absolute_to_protocol_relative');
    $force_https = $this->config->get('path.convert.force_https');
    foreach ($js_groups as $order => $js_group) {
      // We have to return a single asset, not a group of assets. It is now up
      // to one of the pieces of code in the switch statement below to set the
      // 'data' property to the appropriate value.
      $js_assets[$order] = $js_group;
      unset($js_assets[$order]['items']);

      switch ($js_group['type']) {
        case 'file':
          // No preprocessing, single JS asset: just use the existing URI.
          if (!$js_group['preprocess']) {
            $js_assets[$order]['data'] = $js_group['items'][0]['data'];
          }
          // Preprocess (aggregate), unless the aggregate file already exists.
          else {
            $key = $this->generateHash($js_group) . $this->settingsHash;
            $uri = '';
            if ($aggregate = $this->advaggAggregates->get($key)) {
              $uri = $aggregate['uri'];
            }
            if (empty($uri) || !file_exists($uri)) {
              // Concatenate each asset within the group.
              $data = '';
              foreach ($js_group['items'] as $js_asset) {
                // Optimize this JS file, but only if it's not yet minified.
                if (isset($js_asset['minified']) && $js_asset['minified']) {
                  $content = file_get_contents($js_asset['data']);
                }
                else {
                  $content = $this->optimizer->optimize($js_asset);
                }

                // Allow other modules to modify this file's contents.
                // Call hook_advagg_js_contents_alter().
                $this->moduleHandler->alter('advagg_js_contents', $content, $js_asset);
                $data .= $content;

                // Append a ';' and a newline after each JS file to prevent them
                // from running together.
                $data .= ";\n";
              }
              // Remove unwanted JS code that cause issues.
              $data = $this->optimizer->clean($data);
              // Dump the optimized JS for this group into an aggregate file.
              list($uri, $filename) = $this->dumper->dump($data, 'js');
              // Set the URI for this group's aggregate file.
              $js_assets[$order]['data'] = $uri;
              // Persist the URI for this aggregate file.
              $aggregate_info = [
                'uri' => $uri,
                'contents' => $js_group['items'],
                'hooks_hash' => advagg_current_hooks_hash_array(),
                'uid' => $filename,
              ];
              $this->advaggAggregates->set($key, $aggregate_info);
            }
            else {
              // Use the persisted URI for the optimized JS file.
              $js_assets[$order]['data'] = $uri;
            }
            $js_assets[$order]['preprocessed'] = TRUE;
          }
          break;

        case 'external':
          // We don't do any aggregation and hence also no caching for external
          // JS assets.
          $uri = $js_group['items'][0]['data'];
          if ($force_https) {
            $uri = advagg_path_convert_force_https($uri);
          }
          elseif ($protocol_relative) {
            $uri = advagg_path_convert_protocol_relative($uri);
          }
          $js_assets[$order]['data'] = $uri;
          break;
      }
    }

    return $js_assets;
  }

  /**
   * Generate a hash for a given group of JavaScript assets.
   *
   * @param array $js_group
   *   A group of JavaScript assets.
   *
   * @return string
   *   A hash to uniquely identify the given group of JavaScript assets.
   */
  protected function generateHash(array $js_group) {
    $js_data = [];
    foreach ($js_group['items'] as $js_file) {
      $js_data[] = $js_file['data'];
      $js_data[] = filemtime($js_file['data']);
    }
    return hash('sha256', serialize($js_data));
  }

  /**
   * Deletes all optimized collection assets.
   *
   * Note: Core's deleteAll() only deletes old files not all.
   */
  public function deleteAllReal() {
    $log = [];
    $this->state->delete('system.js_cache_files');
    Cache::invalidateTags(['library_info']);
    $delete_all = function ($uri) use (&$log) {
      file_unmanaged_delete($uri);
      $log[] = $uri;
    };
    $this->state->delete('system.js_cache_files');
    file_scan_directory($this->dumper->preparePath('js'), '/.*/', ['callback' => $delete_all]);
    return $log;
  }

  /**
   * Delete stale optimized collection assets.
   */
  public function deleteStale() {
    $log = [];
    $this->state->delete('system.js_cache_files');
    Cache::invalidateTags(['library_info']);
    $delete_stale = function ($uri) use (&$log) {
      // Default stale file threshold is 30 days.
      if (REQUEST_TIME - fileatime($uri) > $this->systemConfig->get('stale_file_threshold')) {
        file_unmanaged_delete($uri);
        $log[] = $uri;
      }
    };
    file_scan_directory($this->dumper->preparePath('js'), '/.*/', ['callback' => $delete_stale]);
    return $log;
  }

  /**
   * Delete old optimized collection assets.
   */
  public function deleteOld() {
    $log = [];
    $this->state->delete('system.js_cache_files');
    Cache::invalidateTags(['library_info']);
    $delete_old = function ($uri) use (&$log) {
      // Default stale file threshold is 30 days.
      // Delete old if > 3 times that.
      if (REQUEST_TIME - filemtime($uri) > $this->systemConfig->get('stale_file_threshold') * 3) {
        file_unmanaged_delete($uri);
        $log[] = $uri;
      }
    };
    file_scan_directory($this->dumper->preparePath('js'), '/.*/', ['callback' => $delete_old]);
    return $log;
  }

}
