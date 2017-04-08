<?php

/**
 * @file
 * Advanced aggregation bundler module.
 */

/**
 * Implements hook_advagg_aggregate_grouping_alter().
 */
function advagg_bundler_advagg_aggregate_grouping_alter(&$groups, $type) {
  // If bundler is disabled then do not do any more processing.
  if (!advagg_bundler_enabled($type)) {
    return;
  }

  $config = \Drupal::config('advagg_bundler.settings');
  $max = $config->get('max_' . $type);
  $logic = $config->get($type . '_logic');
  if ($logic == 1) {
    $file_info = \Drupal::service('state.advagg.files');
  }
  $modifiable = [];
  $counter = 0;
  foreach ($groups as $key => $group) {
    if (isset($group['type'], $group['preprocess']) && $group['type'] == 'file' && $group['preprocess']) {
      $modifiable[$key] = $group;
      $modifiable[$key]['order'] = $key;
      if ($logic == 0) {
        $modifiable[$key]['counter'] = count($group['items']);
        $counter += count($group['items']);
      }
      elseif ($logic == 1) {
        $file_info = \Drupal::service('state.advagg.files');
        $modifiable[$key]['counter'] = 0;
        foreach ($modifiable[$key]['items'] as &$asset) {
          $asset['counter'] = $file_info->get($asset['data'])['filesize'];
          $counter += $asset['counter'];
          $modifiable[$key]['counter'] += $asset['counter'];
        }
      }
    }
  }

  // If more bundles than $max return. $groups is already set to least
  // possible number of groups with current sort. Enabling sort external first
  // may help decrease number of bundles.
  if (count($modifiable) > $max || !$modifiable) {
    return;
  }

  $target_count = ceil($counter / $max);
  $final_groups = [];
  $bundles = 1;
  foreach ($groups as $key => $group) {
    if (!isset($modifiable[$key]) || $bundles == $max) {
      $final_groups[] = $group;
      continue;
    }
    $splits = round($modifiable[$key]['counter'] / $target_count);
    if ($splits < 2) {
      $final_groups[] = $group;
      $bundles++;
      continue;
    }
    if ($logic == 0) {
      $chunks = array_chunk($modifiable[$key]['items'], $target_count);
    }
    elseif ($logic == 1) {
      $target_size = $modifiable[$key]['counter'] / $splits;
      $chunks = [0 => []];
      $i = 0;
      $current_chunk_size = 0;
      foreach ($modifiable[$key]['items'] as $asset) {
        $current_chunk_size += $asset['counter'];
        $chunks[$i][] = $asset;
        if ($current_chunk_size > $target_size) {
          $i++;
          $chunks[$i] = [];
          $current_chunk_size = 0;
        }
      }
    }
    foreach ($chunks as $chunk) {
      if (!$chunk) {
        continue;
      }
      $group['items'] = $chunk;
      $final_groups[] = $group;
      $bundles++;
    }
  }

  $groups = $final_groups;
}
