<?php

namespace Drupal\advagg\Asset;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetResolver as CoreAssetResolver;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * The default asset resolver.
 */
class AssetResolver extends CoreAssetResolver implements AssetResolverInterface {

  /**
   * The CSS collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The JS collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * The AdvAgg file status state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;

  /**
   * Constructs a new AssetResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $library_dependency_resolver
   *   The library dependency resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS collection optimizer.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JS collection optimizer.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, LanguageManagerInterface $language_manager, CacheBackendInterface $cache, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer, StateInterface $advagg_files) {
    $this->libraryDiscovery = $library_discovery;
    $this->libraryDependencyResolver = $library_dependency_resolver;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->languageManager = $language_manager;
    $this->cache = $cache;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->advaggFiles = $advagg_files;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize) {
    $theme_info = $this->themeManager->getActiveTheme();
    // Add the theme name to the cache key since themes may implement
    // hook_css_alter().
    $libraries_to_load = $this->getLibrariesToLoad($assets);
    $cid = 'css:' . $theme_info->getName() . ':' . Crypt::hashBase64(serialize($libraries_to_load)) . (int) $optimize;
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $css = [];
    $default_options = [
      'type' => 'file',
      'group' => CSS_AGGREGATE_DEFAULT,
      'weight' => 0,
      'media' => 'all',
      'preprocess' => TRUE,
      'browsers' => [],
    ];

    foreach ($libraries_to_load as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (isset($definition['css'])) {
        foreach ($definition['css'] as $options) {
          $options += $default_options;
          $options['browsers'] += [
            'IE' => TRUE,
            '!IE' => TRUE,
          ];

          // Files with a query string cannot be preprocessed.
          if ($options['type'] === 'file' && $options['preprocess'] && strpos($options['data'], '?') !== FALSE) {
            $options['preprocess'] = FALSE;
          }

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($css) / 1000;

          // CSS files are being keyed by the full path.
          $css[$options['data']] = $options;
        }
      }
    }

    // Allow modules and themes to alter the CSS assets.
    $this->moduleHandler->alter('css', $css, $assets);
    $this->themeManager->alter('css', $css, $assets);

    // After alter get file information (in case alter changes things).
    $this->advaggFiles->getMultiple(array_column($css, 'data'));

    // Sort CSS items, so that they appear in the correct order.
    uasort($css, 'static::sort');

    // Allow themes to remove CSS files by CSS files full path and file name.
    // @todo Remove in Drupal 9.0.x.
    if ($stylesheet_remove = $theme_info->getStyleSheetsRemove()) {
      foreach ($css as $key => $options) {
        if (isset($stylesheet_remove[$key])) {
          unset($css[$key]);
        }
      }
    }

    if ($optimize) {
      $css = $this->cssCollectionOptimizer->optimize($css);
    }
    $this->cache->set($cid, $css, CacheBackendInterface::CACHE_PERMANENT, ['library_info']);

    return $css;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsAssets(AttachedAssetsInterface $assets, $optimize) {
    $theme_info = $this->themeManager->getActiveTheme();
    // Add the theme name to the cache key since themes may implement
    // hook_js_alter(). Additionally add the current language to support
    // translation of JavaScript files.
    $libraries_to_load = $this->getLibrariesToLoad($assets);
    $cid = 'js:' . $theme_info->getName() . ':' . $this->languageManager->getCurrentLanguage()->getId() . ':' . Crypt::hashBase64(serialize($libraries_to_load)) . (int) (count($assets->getSettings()) > 0) . (int) $optimize;

    if ($cached = $this->cache->get($cid)) {
      list($js_assets_header, $js_assets_footer, $settings, $settings_in_header) = $cached->data;
    }
    else {
      $javascript = [];
      $default_options = [
        'type' => 'file',
        'group' => JS_DEFAULT,
        'weight' => 0,
        'cache' => TRUE,
        'preprocess' => TRUE,
        'attributes' => [],
        'version' => NULL,
        'browsers' => [],
      ];

      // Collect all libraries that contain JS assets and are in the header.
      $header_js_libraries = [];
      foreach ($libraries_to_load as $library) {
        list($extension, $name) = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if (isset($definition['js']) && !empty($definition['header'])) {
          $header_js_libraries[] = $library;
        }
      }
      // The current list of header JS libraries are only those libraries that
      // are in the header, but their dependencies must also be loaded for them
      // to function correctly, so update the list with those.
      $header_js_libraries = $this->libraryDependencyResolver->getLibrariesWithDependencies($header_js_libraries);

      foreach ($libraries_to_load as $library) {
        list($extension, $name) = explode('/', $library, 2);
        $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
        if (isset($definition['js'])) {
          foreach ($definition['js'] as $options) {
            $options += $default_options;

            // 'scope' is a calculated option, based on which libraries are
            // marked to be loaded from the header (see above).
            $options['scope'] = in_array($library, $header_js_libraries) ? 'header' : 'footer';

            // Preprocess can only be set if caching is enabled and no
            // attributes are set.
            $options['preprocess'] = $options['cache'] && empty($options['attributes']) ? $options['preprocess'] : FALSE;

            // Always add a tiny value to the weight, to conserve the insertion
            // order.
            $options['weight'] += count($javascript) / 1000;

            // Local and external files must keep their name as the associative
            // key so the same JavaScript file is not added twice.
            $javascript[$options['data']] = $options;
          }
        }
      }

      // Allow modules and themes to alter the JavaScript assets.
      $this->moduleHandler->alter('js', $javascript, $assets);
      $this->themeManager->alter('js', $javascript, $assets);

      // After alter get file information (in case alter changes things).
      $this->advaggFiles->getMultiple(array_column($javascript, 'data'));

      // Sort JavaScript assets, so that they appear in the correct order.
      uasort($javascript, 'static::sort');

      // Prepare the return value: filter JavaScript assets per scope.
      $js_assets_header = [];
      $js_assets_footer = [];
      foreach ($javascript as $key => $item) {
        if ($item['scope'] == 'header') {
          $js_assets_header[$key] = $item;
        }
        elseif ($item['scope'] == 'footer') {
          $js_assets_footer[$key] = $item;
        }
      }

      if ($optimize) {
        $js_assets_header = $this->jsCollectionOptimizer->optimize($js_assets_header);
        $js_assets_footer = $this->jsCollectionOptimizer->optimize($js_assets_footer);
      }

      // If the core/drupalSettings library is being loaded or is already
      // loaded, get the JavaScript settings assets, and convert them into a
      // single "regular" JavaScript asset.
      $libraries_to_load = $this->getLibrariesToLoad($assets);
      $settings_required = in_array('core/drupalSettings', $libraries_to_load) || in_array('core/drupalSettings', $this->libraryDependencyResolver->getLibrariesWithDependencies($assets->getAlreadyLoadedLibraries()));
      $settings_have_changed = count($libraries_to_load) > 0 || count($assets->getSettings()) > 0;

      // Initialize settings to FALSE since they are not needed by default. This
      // distinguishes between an empty array which must still allow
      // hook_js_settings_alter() to be run.
      $settings = FALSE;
      if ($settings_required && $settings_have_changed) {
        $settings = $this->getJsSettingsAssets($assets);
        // Allow modules to add cached JavaScript settings.
        foreach ($this->moduleHandler->getImplementations('js_settings_build') as $module) {
          $function = $module . '_js_settings_build';
          $function($settings, $assets);
        }
      }
      $settings_in_header = in_array('core/drupalSettings', $header_js_libraries);
      $this->cache->set($cid, [
        $js_assets_header,
        $js_assets_footer,
        $settings,
        $settings_in_header,
      ], CacheBackendInterface::CACHE_PERMANENT, ['library_info']);
    }

    if ($settings !== FALSE) {
      // Attached settings override both library definitions and
      // hook_js_settings_build().
      $settings = NestedArray::mergeDeepArray([$settings, $assets->getSettings()], TRUE);
      // Allow modules and themes to alter the JavaScript settings.
      $this->moduleHandler->alter('js_settings', $settings, $assets);
      $this->themeManager->alter('js_settings', $settings, $assets);
      // Update the $assets object accordingly, so that it reflects the final
      // settings.
      $assets->setSettings($settings);
      $settings_as_inline_javascript = [
        'type' => 'setting',
        'group' => JS_SETTING,
        'weight' => 0,
        'browsers' => [],
        'data' => $settings,
      ];
      $settings_js_asset = ['drupalSettings' => $settings_as_inline_javascript];
      // Prepend to the list of JS assets, to render it first. Preferably in
      // the footer, but in the header if necessary.
      if ($settings_in_header) {
        $js_assets_header = $settings_js_asset + $js_assets_header;
      }
      else {
        $js_assets_footer = $settings_js_asset + $js_assets_footer;
      }
    }
    return [
      $js_assets_header,
      $js_assets_footer,
    ];
  }

}
