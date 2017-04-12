<?php

namespace Drupal\advagg\Asset;

use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\CssCollectionRenderer as CoreCssCollectionRenderer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * {@inheritdoc}
 */
class CssCollectionRenderer extends CoreCssCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * A config object for the advagg configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory) {
    $this->state = $state;
    $this->config = $config_factory->get('advagg.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $css_assets) {
    $elements = ['prefetch' => []];
    $prefetch = $this->config->get('dns_prefetch');

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed.
    $query_string = $this->state->get('system.css_js_query_string') ?: '0';

    // Defaults skeleton for elements.
    $link_element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'stylesheet',
      ],
    ];
    $style_element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'style',
    ];
    $prefetch_element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'link',
      '#attributes' => [
        'rel' => 'dns-prefetch',
      ],
    ];

    // For filthy IE hack.
    $current_ie_group_key = NULL;
    $get_ie_group_key = function ($css_asset) {
      return [
        $css_asset['type'],
        $css_asset['preprocess'],
        $css_asset['group'],
        $css_asset['media'],
        $css_asset['browsers'],
      ];
    };

    // Loop through all CSS assets, by key, to allow for the special IE
    // workaround.
    $css_assets_keys = array_keys($css_assets);
    for ($i = 0; $i < count($css_assets_keys); $i++) {
      $css_asset = $css_assets[$css_assets_keys[$i]];
      switch ($css_asset['type']) {
        // For file items, there are three possibilities.
        // - There are up to 31 CSS assets on the page (some of which may be
        //   aggregated). In this case, output a LINK tag for file CSS assets.
        // - There are more than 31 CSS assets on the page, yet we must stay
        //   below IE<10's limit of 31 total CSS inclusion tags, we handle this
        //   in two ways:
        //   - file CSS assets that are not eligible for aggregation (their
        //     'preprocess' flag has been set to FALSE): in this case, output a
        //     LINK tag.
        //   - file CSS assets that can be aggregated (and possibly have been):
        //     in this case, figure out which subsequent file CSS assets share
        //     the same key properties ('group', 'inline', 'browsers' and if
        //     set also 'media') and output this group into as few STYLE tags
        //     as possible (each STYLE tag must have less than 31 @import
        //     statements).
        case 'file':
          // Prefetch contained domains (ie calls to url()).
          if ($prefetch && !empty($css_asset['dns_prefetch'])) {
            foreach ($css_asset['dns_prefetch'] as $domain) {
              $element = $prefetch_element_defaults;
              $element['#attributes']['href'] = '//' . $domain;
              $elements['prefetch'][] = $element;
              if ($domain == 'fonts.googleapis.com') {
                // Add fonts.gstatic.com when fonts.googleapis.com is added.
                $element['#attributes']['href'] = 'https://fonts.gstatic.com';
                array_unshift($elements, $element);
              }
            }
          }

          // The dummy query string needs to be added to the URL to control
          // browser-caching.
          $query_string_separator = (strpos($css_asset['data'], '?') !== FALSE) ? '&' : '?';

          // As long as the current page will not run into IE's limit for CSS
          // assets: output a LINK tag for a file CSS asset.
          if (count($css_assets) <= 31) {
            $element = $link_element_defaults;
            $element['#attributes']['href'] = file_url_transform_relative(file_create_url($css_asset['data'])) . $query_string_separator . $query_string;
            $element['#browsers'] = $css_asset['browsers'];
            if (isset($css_asset['media'])) {
              $element['#attributes']['media'] = $css_asset['media'];
            }
            $element['#inline'] = !empty($css_asset['inline']) ? TRUE : FALSE;
            $elements[] = $element;
          }
          // The current page will run into IE's limits for CSS assets: work
          // around these limits by performing a light form of grouping.
          // Once Drupal only needs to support IE10 and later, we can drop this.
          else {
            // The file CSS asset is ineligible for aggregation: output it in a
            // LINK tag.
            if (!$css_asset['preprocess']) {
              $element = $link_element_defaults;
              $element['#attributes']['href'] = file_url_transform_relative(file_create_url($css_asset['data'])) . $query_string_separator . $query_string;
              $element['#attributes']['media'] = $css_asset['media'];
              $element['#browsers'] = $css_asset['browsers'];
              $element['#inline'] = !empty($css_asset['inline']) ? TRUE : FALSE;
              $elements[] = $element;
            }
            // The file CSS asset can be aggregated, but hasn't been: combine
            // multiple items into as few STYLE tags as possible.
            else {
              $import = [];
              // Start with the current CSS asset, iterate over subsequent CSS
              // assets and find which ones have the same 'type', 'group',
              // 'preprocess', 'inline', 'browsers' properties and depending on
              // settings, also 'media'.
              $j = $i;
              $next_css_asset = $css_asset;
              $current_ie_group_key = $get_ie_group_key($css_asset);
              do {
                // The dummy query string needs to be added to the URL to
                // control browser-caching. IE7 does not support a media type on
                // the @import statement, so we instead specify the media for
                // the group on the STYLE tag.
                $import[] = '@import url("' . Html::escape(file_url_transform_relative(file_create_url($next_css_asset['data'])) . '?' . $query_string) . '");';
                // Move the outer for loop skip the next item, since we
                // processed it here.
                $i = $j;
                // Retrieve next CSS asset, unless there is none: then break.
                if ($j + 1 < count($css_assets_keys)) {
                  $j++;
                  $next_css_asset = $css_assets[$css_assets_keys[$j]];
                }
                else {
                  break;
                }
              } while ($get_ie_group_key($next_css_asset) == $current_ie_group_key);

              // In addition to IE's limit of 31 total CSS inclusion tags, it
              // also has a limit of 31 @import statements per STYLE tag.
              while (!empty($import)) {
                $import_batch = array_slice($import, 0, 31);
                $import = array_slice($import, 31);
                $element = $style_element_defaults;
                // This simplifies the JavaScript regex, allowing each line
                // (separated by \n) to be treated as a completely different
                // string. This means that we can use ^ and $ on one line at a
                // time, and not worry about style tags since they'll never
                // match the regex.
                $element['#value'] = "\n" . implode("\n", $import_batch) . "\n";
                if (isset($css_asset['media'])) {
                  $element['#attributes']['media'] = $css_asset['media'];
                }
                $element['#browsers'] = $css_asset['browsers'];
                $elements[] = $element;
              }
            }
          }
          break;

        // Output a LINK tag for an external CSS asset. The asset's 'data'
        // property contains the full URL.
        case 'external':
          $element = $link_element_defaults;
          $element['#attributes']['href'] = $css_asset['data'];
          $element['#attributes']['media'] = $css_asset['media'];
          $element['#browsers'] = $css_asset['browsers'];
          $elements[] = $element;
          if ($prefetch) {
            $element = $prefetch_element_defaults;
            $element['#attributes']['href'] = '//' . parse_url($css_asset['data'], PHP_URL_HOST);
            $elements['prefetch'][] = $element;
            if ($element['#attributes']['href'] == 'fonts.googleapis.com') {
              // Add fonts.gstatic.com when fonts.googleapis.com is added.
              $element['#attributes']['href'] = 'https://fonts.gstatic.com';
              $elements['prefetch'][] = $element;
            }
          }
          break;

        default:
          throw new \Exception('Invalid CSS asset type.');
      }
    }
    if (empty($elements['prefetch'])) {
      unset($elements['prefetch']);
    }
    return $elements;
  }

}
