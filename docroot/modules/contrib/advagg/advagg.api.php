<?php

/**
 * @file
 * Hooks provided by the AdvAgg module.
 */

/**
 * @defgroup advagg_hooks Advanced Aggregates Hooks
 *
 * @{
 * Hooks for modules to implement to extend or modify Advanced Aggregates.
 *
 * @see https://api.drupal.org/api/drupal/includes%21module.inc/group/hooks/7.x
 */

/**
 * Allow other modules to modify the aggregate groups.
 *
 * Called once per page at aggregation time (if not cached).
 * Should be in MODULENAME.advagg.inc file.
 *
 * @param array $groups
 *   The generated groups.
 * @param string $type
 *   The asset type ('css' or 'js').
 *
 * @see \Drupal\advagg\Asset\CssCollectionGrouper::group()
 * @see \Drupal\advagg\Asset\JsCollectionGrouper::group()
 * @see advagg_bundler_advagg_aggregate_grouping_alter()
 */
function hook_advagg_aggregate_grouping_alter(array &$groups, $type) {
  $max = 4;
  $modifiable = [];
  $files = 0;
  foreach ($groups as $key => $group) {
    if (isset($group['type'], $group['preprocess']) && $group['type'] == 'file' && $group['preprocess']) {
      $modifiable[$key] = $group;
      $modifiable[$key]['order'] = $key;
      $modifiable[$key]['file_count'] = count($group['items']);
      $files += count($group['items']);
    }
  }

  // If more bundles than $max return. $groups is already set to least
  // possible number of groups with current sort. Enabling sort external first
  // may help decrease number of bundles.
  if (count($modifiable) > $max || !$modifiable) {
    return;
  }

  $target_files = ceil($files / $max);
  $final_groups = [];
  $bundles = 0;
  foreach ($groups as $key => $group) {
    if (!isset($modifiable[$key]) || $bundles == $max) {
      $final_groups[] = $group;
      continue;
    }
    $splits = round($modifiable[$key]['file_count'] / $target_files);
    if ($splits < 2) {
      $final_groups[] = $group;
      $bundles++;
      continue;
    }
    $chunks = array_chunk($group['items'], $target_files);
    foreach ($chunks as $chunk) {
      $group['items'] = $chunk;
      $final_groups[] = $group;
      $bundles++;
    }
  }

  $groups = $final_groups;
}

/**
 * Allow other modules to add in their own settings and hooks.
 *
 * @param array $aggregate_settings
 *   An associative array of hooks and settings used.
 *
 * @see advagg_current_hooks_hash_array()
 * @see advagg_js_minify_advagg_current_hooks_hash_array_alter()
 */
function hook_advagg_current_hooks_hash_array_alter(array &$aggregate_settings) {
  $aggregate_settings['variables']['advagg_js_minify'] = \Drupal::config('advagg_js_minify.settings')->get();
}

/**
 * Allow other modules to modify the contents of individual CSS files.
 *
 * Called once per file at aggregation time.
 *
 * @param string $data
 *   File contents. Depending on settings/modules these may be minified.
 * @param array $css_asset
 *   Asset array.
 *
 * @see \Drupal\advagg\Asset\CssCollectionOptimizer::optimize()
 */
function hook_advagg_css_contents_alter(&$data, array $css_asset) {
  // Remove all font-style rules applying italics.
  preg_replace("/(.*)(font-style\s*:.*italic)(.*)/m", "$0 --> $1 $3", $data);
}

/**
 * Allow other modules to modify the contents of individual JavaScript files.
 *
 * Called once per file at aggregation time.
 *
 * @param string $contents
 *   Raw file data.
 * @param array $js_asset
 *   Asset array.
 *
 * @see \Drupal\advagg\Asset\JsCollectionOptimizer::optimize()
 */
function hook_advagg_js_contents_alter(&$contents, array $js_asset) {
  if ($js_asset['data'] == 'modules/advagg/advagg.admin.js') {
    $contents = str_replace('AdvAgg Bypass Cookie Removed', 'Advanced Aggregates Cookie Removed', $contents);
  }
}

/**
 * Let other modules add/alter additional information about file passed in.
 *
 * @param string $file
 *   The file path/name.
 * @param array $data
 *   An associative array of metadata on the file.
 * @param array $cached
 *   What data was found in the database (if any).
 *
 * @see Drupal\advagg\State\Files::scanFiles()
 */
function hook_advagg_scan_file_alter($file, array $data, array $cached) {
  $data['chars'] = strlen($data['content']);
}

/**
 * Tell advagg about other hooks related to advagg.
 *
 * @param array $hooks
 *   Array of hooks related to advagg.
 * @param bool $all
 *   If FALSE get only the subset of hooks that alter the filename/contents.
 *
 * @see advagg_hooks_implemented()
 * @see advagg_bundler_advagg_hooks_implemented_alter()
 */
function hook_advagg_hooks_implemented_alter(array &$hooks, $all) {
  if ($all) {
    $hooks['advagg_bundler_analysis_alter'] = [];
  }
}

/**
 * Allow other modules to modify the path to save aggregates to.
 *
 * @param string $path
 *   The currently set folder to save the aggregated assets to.
 *
 * @see Drupal\advagg\Asset\AssetDumper::preparePath()
 * @see advagg_mod_advagg_asset_path_alter()
 */
function hook_advagg_asset_path_alter(&$path, $extension) {
  if ($extension == 'js') {
    $path = 'public://javascript/';
  }
}

/**
 * Let other modules modify the analysis array before it is used.
 *
 * @param array $analysis
 *   An associative array; filename -> data.
 *
 * @see advagg_bundler_analysis()
 */
function hook_advagg_bundler_analysis_alter(array &$analysis) {
  foreach ($analysis as $filename => &$data) {
    if ($filename) {
      // This is the filename.
    }

    // This changes often; 604800 is 1 week.
    if ($data['changes'] > 10 && $data['mtime'] >= REQUEST_TIME - 604800) {
      // Modify the group hash so this doesn't end up in a big aggregate.
      $data['group_hash'];
    }
  }
  unset($data);
}

/**
 * @} End of "addtogroup adv_hooks".
 */
