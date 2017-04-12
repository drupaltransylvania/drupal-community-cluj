<?php

namespace Drupal\advagg\Asset;

use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\CssCollectionOptimizer as CoreCssCollectionOptimizer;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class CssCollectionOptimizer extends CoreCssCollectionOptimizer implements AssetCollectionOptimizerInterface {

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
   * The AdvAgg file status state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;

  /**
   * Hash of the AdvAgg settings.
   *
   * @var string
   */
  protected $settingsHash;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\State\StateInterface $advagg_aggregates
   *   A state information store for the AdvAgg generated aggregates.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AssetCollectionGrouperInterface $grouper, AssetOptimizerInterface $optimizer, AssetDumperInterface $dumper, StateInterface $state, ConfigFactoryInterface $config_factory, StateInterface $advagg_aggregates, ModuleHandlerInterface $module_handler, StateInterface $advagg_files, RequestStack $request_stack) {
    $this->grouper = $grouper;
    $this->optimizer = $optimizer;
    $this->dumper = $dumper;
    $this->state = $state;
    $this->config = $config_factory->get('advagg.settings');
    $this->systemConfig = $config_factory->get('system.performance');
    $this->advaggAggregates = $advagg_aggregates;
    $this->moduleHandler = $module_handler;
    $this->advaggFiles = $advagg_files;
    $this->settingsHash = '_' . advagg_get_current_hooks_hash();
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function optimize(array $css_assets) {
    // Group the assets.
    $css_groups = $this->grouper->group($css_assets);

    // Now optimize (concatenate + minify) and dump each asset group, unless
    // that was already done, in which case it should appear in
    // drupal_css_cache_files.
    // Drupal contrib can override this default CSS aggregator to keep the same
    // grouping, optimizing and dumping, but change the strategy that is used to
    // determine when the aggregate should be rebuilt (e.g. mtime, HTTPS …).
    $css_assets = [];
    $protocol_relative = $this->config->get('path.convert.absolute_to_protocol_relative');
    $force_https = $this->config->get('path.convert.force_https');
    $combine_media = $this->config->get('css.combine_media');
    $page_uri = $this->requestStack->getCurrentRequest()->getUri();
    foreach ($css_groups as $order => $css_group) {
      // We have to return a single asset, not a group of assets. It is now up
      // to one of the pieces of code in the switch statement below to set the
      // 'data' property to the appropriate value.
      $css_assets[$order] = $css_group;
      unset($css_assets[$order]['items']);
      switch ($css_group['type']) {
        case 'file':
          // No preprocessing, single CSS asset: just use the existing URI.
          if (!$css_group['preprocess']) {
            $css_assets[$order]['data'] = $css_group['items'][0]['data'];
          }
          // Preprocess (aggregate), unless the aggregate file already exists.
          else {
            $key = $this->generateHash($css_group) . $this->settingsHash;
            $uri = '';
            if ($aggregate = $this->advaggAggregates->get($key)) {
              $uri = $aggregate['uri'];
              $aggregate['basic']['pages'][] = $page_uri;
              $this->advaggAggregates->set($key, $aggregate);
            }
            if (empty($uri) || !file_exists($uri)) {
              // Optimize each asset within the group.
              $data = '';
              $group_file_info = $this->advaggFiles->getMultiple(array_column($css_group['items'], 'data'));

              // Add aggregate & page to advaggFiles store per included file.
              foreach ($group_file_info as $k => &$file) {
                $file['aggregates'][] = $key;
                $file['pages'][] = $page_uri;
              }
              if (array_column($group_file_info, 'dns_prefetch')) {
                $css_assets[$order]['dns_prefetch'] = [];
                foreach ($group_file_info as $file) {
                  if (!empty($file['dns_prefetch'])) {
                    $css_assets[$order]['dns_prefetch'] = array_merge($css_assets[$order]['dns_prefetch'], $file['dns_prefetch']);
                  }
                }
              }
              foreach ($css_group['items'] as $css_asset) {
                $content = $this->optimizer->optimize($css_asset);
                if ($combine_media && isset($css_asset['media']) && $css_asset['media'] != 'all') {
                  $content = '@media ' . $css_asset['media'] . ' {' . $content . '}';
                }

                // Allow other modules to modify this file's contents.
                // Call hook_advagg_css_contents_alter().
                $this->moduleHandler->alter('advagg_css_contents', $content, $css_asset, $group_file_info);
                $data .= $content;
              }
              // Per the W3C specification at
              // http://www.w3.org/TR/REC-CSS2/cascade.html#at-import, @import
              // rules must precede any other style, so we move those to the
              // top.
              $regexp = '/@import[^;]+;/i';
              preg_match_all($regexp, $data, $matches);
              $data = preg_replace($regexp, '', $data);
              $data = implode('', $matches[0]) . $data;
              // Dump the optimized CSS for this group into an aggregate file.
              list($uri, $filename) = $this->dumper->dump($data, 'css');
              // Set the URI for this group's aggregate file.
              $css_assets[$order]['data'] = $uri;
              // Persist the URI for this aggregate file.
              $aggregate_info = [
                'uri' => $uri,
                'uid' => $filename,
                'basic' => [
                  'files' => array_keys($css_group['items']),
                  'pages' => [$page_uri],
                ],
                'detailed' => [
                  'files' => $css_group['items'],
                  'hooks_hash' => advagg_current_hooks_hash_array(),
                ],
              ];
              $this->advaggAggregates->set($key, $aggregate_info);
            }
            else {
              // Use the persisted URI for the optimized CSS file.
              $css_assets[$order]['data'] = $uri;
            }
            $css_assets[$order]['preprocessed'] = TRUE;
          }
          break;

        case 'external':
          // We don't do any aggregation and hence also no caching for external
          // CSS assets.
          $uri = $css_group['items'][0]['data'];
          if ($force_https) {
            $uri = advagg_path_convert_force_https($uri);
          }
          elseif ($protocol_relative) {
            $uri = advagg_path_convert_protocol_relative($uri);
          }
          $css_assets[$order]['data'] = $uri;
          break;
      }
    }
    return $css_assets;
  }

  /**
   * Generate a hash for a given group of CSS assets.
   *
   * @param array $css_group
   *   A group of CSS assets.
   *
   * @return string
   *   A hash to uniquely identify the given group of CSS assets.
   */
  protected function generateHash(array $css_group) {
    $css_data = [];
    foreach ($css_group['items'] as $css_file) {
      $css_data[] = $css_file['data'];
      $css_data[] = filemtime($css_file['data']);
    }
    return hash('sha256', serialize($css_data));
  }

  /**
   * Deletes all optimized collection assets.
   *
   * Note: Core's deleteAll() only deletes old files not all.
   */
  public function deleteAllReal() {
    $log = [];
    $this->state->delete('system.css_cache_files');
    Cache::invalidateTags(['library_info']);
    $delete_all = function ($uri) use (&$log) {
      file_unmanaged_delete($uri);
      $log[] = $uri;
    };
    $this->state->delete('system.js_cache_files');
    file_scan_directory($this->dumper->preparePath('css'), '/.*/', ['callback' => $delete_all]);
    return $log;
  }

  /**
   * Delete stale optimized collection assets.
   */
  public function deleteStale() {
    $log = [];
    $this->state->delete('system.css_cache_files');
    Cache::invalidateTags(['library_info']);
    $delete_stale = function ($uri) use (&$log) {
      // Default stale file threshold is 30 days.
      if (REQUEST_TIME - fileatime($uri) > $this->systemConfig->get('stale_file_threshold')) {
        file_unmanaged_delete($uri);
        $log[] = $uri;
      }
    };
    file_scan_directory($this->dumper->preparePath('css'), '/.*/', ['callback' => $delete_stale]);
    return $log;
  }

  /**
   * Delete old optimized collection assets.
   */
  public function deleteOld() {
    $log = [];
    $this->state->delete('system.css_cache_files');
    Cache::invalidateTags(['library_info']);
    $delete_old = function ($uri) use (&$log) {
      // Default stale file threshold is 30 days.
      // Delete old if > 3 times that.
      if (REQUEST_TIME - filemtime($uri) > $this->systemConfig->get('stale_file_threshold') * 3) {
        file_unmanaged_delete($uri);
        $log[] = $uri;
      }
    };
    file_scan_directory($this->dumper->preparePath('css'), '/.*/', ['callback' => $delete_old]);
    return $log;
  }

}
