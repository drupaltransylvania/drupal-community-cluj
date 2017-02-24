<?php

/**
 * @file
 * Advanced CSS/JS aggregation module.
 *
 * File used to store hook_advagg_* hooks.
 */

/**
 * Implements hook_advagg_scan_file_alter().
 *
 * Used to make sure the add/modify the file meta data saved in the database.
 */
function advagg_advagg_scan_file_alter($file, &$data) {
  // Capture hosts for DNS prefetching.
  // Skip if not a css file.
  if (empty($data['fileext']) || $data['fileext'] !== 'css') {
    return;
  }

  // Get domain names in this css file.
  $matches = [];
  $pattern = '%url\(\s*+[\'"]?+(http:\/\/|https:\/\/|\/\/)([^\'"()\s]++)[\'"]?+\s*+\)%i';
  preg_match_all($pattern, $data['contents'], $matches);
  $urls = [];
  if (!empty($matches[1])) {
    foreach ($matches[1] as $key => $match) {
      $parse = @parse_url($match . $matches[2][$key]);
      if (!empty($parse['host']) && empty($urls[$parse['host']])) {
        $urls[$parse['host']] = $parse['host'];
      }
    }
    $urls = array_values($urls);
  }
  if (!empty($urls)) {
    $data['dns_prefetch'] = $urls;
  }
}
