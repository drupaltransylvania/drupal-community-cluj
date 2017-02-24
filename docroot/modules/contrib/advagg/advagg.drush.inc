<?php

/**
 * @file
 * Drush commands for Advanced CSS/JS Aggregation.
 */

/**
 * Implements hook_drush_cache_clear().
 */
function advagg_drush_cache_clear(&$types) {
  // Add in Advanced CSS/JS Aggregation.
  $types['advagg'] = 'drush_advagg_smart_cache_flush';
}

/**
 * Implements hook_drush_help().
 */
function advagg_drush_help($command) {
  switch ($command) {
    case 'drush:advagg-cron':
      return dt('Run the advagg cron hook. This will clear out all stale advagg aggregated files, remove aggregates that include missing files, and remove unused aggregates.');

    case 'drush:advagg-clear-db-cache':
      return dt('Remove all entries from the advagg cache bins. Useful if you suspect a cache is not getting cleared.');

    case 'drush:advagg-clear-all-files':
      return dt('Remove all generated files. Useful if you think some of the generated files got corrupted and thus need to be deleted.');

    case 'drush:advagg-force-new-aggregates':
      return dt('Force the creation of all new aggregates by incrementing a global counter. Current value of counter: %value. This is useful if a CDN has cached an aggregate incorrectly as it will force new ones to be used even if nothing else has changed.', ['%value' => advagg_get_global_counter()]);
  }
}

/**
 * Implements hook_drush_command().
 */
function advagg_drush_command() {
  $items = [];
  $items['advagg-cron'] = [
    'description' => dt('Run the advagg cron hook.'),
    'examples' => [
      'Standard example' => 'drush advagg-cron',
    ],
    'aliases' => ['advagg-c'],
  ];
  $items['advagg-clear-db-cache'] = [
    'description' => dt('Remove all entries from the advagg cache bins.'),
    'examples' => [
      'Standard example' => 'drush advagg-clear-db-cache',
    ],
    'aliases' => ['advagg-cdc'],
  ];
  $items['advagg-clear-all-files'] = [
    'description' => dt('Remove all generated files.'),
    'examples' => [
      'Standard example' => 'drush advagg-clear-all-files',
    ],
    'aliases' => ['advagg-caf'],
  ];
  $items['advagg-force-new-aggregates'] = [
    'description' => dt('Force the creation of all new aggregates by incrementing a global counter.'),
    'examples' => [
      'Standard example' => 'drush advagg-force-new-aggregates',
    ],
    'aliases' => ['advagg-fna'],
  ];
  return $items;
}

/**
 * Callback function for drush advagg-force-new-aggregates.
 *
 * Callback is called by using drush_hook_command() where
 * hook is the name of the module (advagg) and command is the name of
 * the Drush command with all "-" characters converted to "_" characters.
 */
function drush_advagg_force_new_aggregates() {
  // Clear out the cache.
  drush_advagg_clear_db_cache();

  // Increment counter.
  $config = \Drupal::service('config.factory')->getEditable('advagg.settings');
  $new_value = $config->get('global_counter') + 1;
  $config->set('global_counter', $new_value)->save();
  drush_log(dt('Global counter is now set to @new_value', ['@new_value' => $new_value]), 'ok');
}

/**
 * Callback function for drush advagg-clear-all-files.
 *
 * Callback is called by using drush_hook_command() where
 * hook is the name of the module (advagg) and command is the name of
 * the Drush command with all "-" characters converted to "_" characters.
 */
function drush_advagg_clear_all_files() {
  // Run the command.
  $css_files = \Drupal::service('asset.css.collection_optimizer')->deleteAllReal();
  $js_files = \Drupal::service('asset.js.collection_optimizer')->deleteAllReal();

  // Report back the results.
  drush_log(dt('All AdvAgg files have been deleted. @css_count CSS files and @js_count JS files have been removed.', [
    '@css_count' => count($css_files),
    '@js_count' => count($js_files),
  ]), 'ok');
}

/**
 * Callback function for drush advagg-clear-db-cache.
 *
 * Callback is called by using drush_hook_command() where
 * hook is the name of the module (advagg) and command is the name of
 * the Drush command with all "-" characters converted to "_" characters.
 */
function drush_advagg_clear_db_cache() {
  \Drupal::service('cache.advagg.minify')->deleteAll();

  // Report back the results.
  drush_log(dt('All AdvAgg cache bins have been cleared.'), 'ok');
}

/**
 * Callback function for drush advagg-cron.
 *
 * Callback is called by using drush_hook_command() where
 * hook is the name of the module (advagg) and command is the name of
 * the Drush command with all "-" characters converted to "_" characters.
 */
function drush_advagg_cron() {
  // Run AdvAgg cron job.
  $output = advagg_cron(TRUE);

  // Output results from running AssetCollectionOptimizer::deleteStale().
  list($css_files, $js_files) = $output['stale'];
  if (!empty($css_files) || !empty($js_files)) {
    drush_log(dt('All stale aggregates have been deleted. %css_count CSS files and %js_count JS files have been removed.', [
      '%css_count' => count($css_files),
      '%js_count' => count($js_files),
    ]), 'ok');
  }
  else {
    drush_log(dt('No stale aggregates found. Nothing was deleted.'), 'ok');
  }
}

/**
 * Flush the correct caches so CSS/JS changes go live.
 */
function drush_advagg_smart_cache_flush() {
  // Run the commands.
  \Drupal::service('asset.js.collection_optimizer')->deleteAll();
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
  _drupal_flush_css_js();
  drush_log('Advagg Aggregates Cache Cleared', 'ok');
}
