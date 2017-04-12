<?php

namespace Drupal\advagg\State;

use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Provides AdvAgg with a file status state system using a key value store.
 */
class Files extends State implements StateInterface {

  /**
   * A config object for the advagg configuration.
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
   * Save location for split files.
   *
   * @var string
   */
  protected $partsPath;

  /**
   * An asset dumper.
   *
   * @var \Drupal\Core\Asset\AssetDumper
   */
  protected $dumper;

  /**
   * Constructs the State object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Asset\AssetDumperInterface $asset_dumper
   *   The dumper for optimized CSS assets.
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, AssetDumperInterface $asset_dumper) {
    $this->keyValueStore = $key_value_factory->get('advagg_files');
    $this->config = $config_factory->get('advagg.settings');
    $this->moduleHandler = $module_handler;
    $this->dumper = $asset_dumper;
    $this->partsPath = $this->dumper->preparePath('css') . 'parts/';
    file_prepare_directory($this->partsPath, FILE_CREATE_DIRECTORY);
  }

  /**
   * Given a filename calculate various hashes and gather meta data.
   *
   * @param string $file
   *   A filename/path.
   *
   * @return array
   *   $data which contains
   *
   * @code
   *   'filesize' => filesize($file),
   *   'mtime' => @filemtime($file),
   *   'filename_hash' => Crypt::hashBase64($file),
   *   'content_hash' => Crypt::hashBase64($file_contents),
   *   'linecount' => $linecount,
   *   'data' => $file,
   *   'fileext' => $ext,
   *   ...
   * @endcode
   */
  public function scanFile($file, $cached = NULL, $file_contents = NULL) {
    // Clear PHP's internal file status cache.
    clearstatcache(TRUE, $file);

    if (!$file_contents) {
      $file_contents = (string) @file_get_contents($file);
    }
    $content_hash = Crypt::hashBase64($file_contents);
    if (isset($cached) && $content_hash != $cached['content_hash']) {
      $changes = $cached['changes'] + 1;
    }
    else {
      $changes = 0;
    }
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext !== 'css' && $ext !== 'js') {
      if ($ext === 'less') {
        $ext = 'css';
      }
    }

    if ($ext === 'css') {
      // Get the number of selectors.
      // http://stackoverflow.com/a/12567381/125684
      $linecount = preg_match_all('/\{.+?\}|,/s', $file_contents);
    }
    else {
      // Get the number of lines.
      $linecount = substr_count($file_contents, "\n");
    }

    // Build meta data array.
    $data = [
      'filesize' => (int) @filesize($file),
      'mtime' => @filemtime($file),
      'filename_hash' => Crypt::hashBase64($file),
      'content_hash' => $content_hash,
      'linecount' => $linecount,
      'data' => $file,
      'fileext' => $ext,
      'updated' => REQUEST_TIME,
      'contents' => $file_contents,
      'changes' => $changes,
    ];

    if ($ext === 'css' && $linecount > $this->config->get('css.ie.selector_limit')) {
      $this->splitCssFile($data);
    }

    // Run hook so other modules can modify the data.
    // Call hook_advagg_scan_file_alter().
    $this->moduleHandler->alter('advagg_scan_file', $file, $data, $cached);
    unset($data['contents']);
    $this->set($file, $data);
    $this->cache[$file] = $data;
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys, $refresh_data = NULL) {
    $values = [];
    $load = [];
    $cache_level = $this->config->get('cache_level');
    $cache_time = advagg_get_cache_time($cache_level);

    foreach ($keys as $key) {
      // Check if we have a value in the cache.
      if (isset($this->cache[$key])) {
        $values[$key] = $this->cache[$key];
      }
      // Load the value if we don't have an explicit NULL value.
      elseif (!array_key_exists($key, $this->cache)) {
        $load[] = $key;
      }
    }

    if ($load) {
      $loaded_values = $this->keyValueStore->getMultiple($load);
      foreach ($load as $key) {
        // If we find a value, add it to the temporary cache.
        if (isset($loaded_values[$key])) {
          if ($refresh_data === FALSE) {
            $values[$key] = $loaded_values[$key];
            $this->cache[$key] = $loaded_values[$key];
            continue;
          }
          $file_contents = (string) @file_get_contents($key);
          if (!$refresh_data && $cache_level != -1 && !empty($loaded_values[$key]['updated'])) {
            // If data last updated too long ago check for changes.
            // Ensure the file exists.
            if (!file_exists($key)) {
              $this->delete($key);
              $values[$key] = NULL;
              $this->cache[$key] = NULL;
              continue;
            }
            // If cache is Normal, check file for changes.
            if ($cache_level == 1 || REQUEST_TIME - $loaded_values[$key]['updated'] < $cache_time) {
              $content_hash = Crypt::hashBase64($file_contents);
              if ($content_hash == $loaded_values[$key]['content_hash']) {
                $values[$key] = $loaded_values[$key];
                $this->cache[$key] = $loaded_values[$key];
                continue;
              }
            }
          }

          // If file exists but is changed rescan.
          $values[$key] = $this->scanFile($key, $loaded_values[$key], $file_contents);
          continue;
        }

        if (file_exists($key)) {
          // File has never been scanned, scan it.
          $values[$key] = $this->scanFile($key);
        }
      }
    }

    return $values;
  }

  /**
   * Split up a CSS string by @media queries.
   *
   * @param string $css
   *   String of CSS.
   *
   * @return array
   *   array of css with only media queries.
   *
   * @see http://stackoverflow.com/questions/14145620/regular-expression-for-media-queries-in-css
   */
  private function parseMediaBlocks($css) {
    $media_blocks = [];
    $start = 0;
    $last_start = 0;

    // Using the string as an array throughout this function.
    // http://php.net/types.string#language.types.string.substr
    while (($start = strpos($css, "@media", $start)) !== FALSE) {
      // Stack to manage brackets.
      $s = [];

      // Get the first opening bracket.
      $i = strpos($css, "{", $start);

      // If $i is false, then there is probably a css syntax error.
      if ($i === FALSE) {
        continue;
      }

      // Push bracket onto stack.
      array_push($s, $css[$i]);
      // Move past first bracket.
      ++$i;

      // Find the closing bracket for the @media statement. But ensure we don't
      // overflow if there's an error.
      while (!empty($s) && isset($css[$i])) {
        // If the character is an opening bracket, push it onto the stack,
        // otherwise pop the stack.
        if ($css[$i] === "{") {
          array_push($s, "{");
        }
        elseif ($css[$i] === "}") {
          array_pop($s);
        }
        ++$i;
      }

      // Get CSS before @media and store it.
      if ($last_start != $start) {
        $insert = trim(substr($css, $last_start, $start - $last_start));
        if (!empty($insert)) {
          $media_blocks[] = $insert;
        }
      }
      // Cut @media block out of the css and store.
      $media_blocks[] = trim(substr($css, $start, $i - $start));
      // Set the new $start to the end of the block.
      $start = $i;
      $last_start = $start;
    }

    // Add in any remaining css rules after the last @media statement.
    if (strlen($css) > $last_start) {
      $insert = trim(substr($css, $last_start));
      if (!empty($insert)) {
        $media_blocks[] = $insert;
      }
    }

    return $media_blocks;
  }

  /**
   * Given a file info array it will split the file up.
   *
   * @param array $file_info
   *   File info array.
   *
   * @return array
   *   Array with file and split data.
   */
  private function splitCssFile(array &$file_info) {
    // Get the CSS file and break up by media queries.
    if (!isset($file_info['contents'])) {
      $file_info['contents'] = file_get_contents($file_info['data']);
    }
    $media_blocks = $this->parseMediaBlocks($file_info['contents']);

    // Get 98% of the css.ie.selector_limit; usually 4013.
    $selector_split_value = (int) max(floor($this->config->get('css.ie.selector_limit') * 0.98), 100);
    $part_selector_count = 0;
    $major_chunks = [];
    $counter = 0;

    // Group media queries together.
    foreach ($media_blocks as $media_block) {
      // Get the number of selectors.
      // http://stackoverflow.com/a/12567381/125684
      $selector_count = preg_match_all('/\{.+?\}|,/s', $media_block);
      $part_selector_count += $selector_count;

      if ($part_selector_count > $selector_split_value) {
        if (isset($major_chunks[$counter])) {
          ++$counter;
          $major_chunks[$counter] = $media_block;
        }
        else {
          $major_chunks[$counter] = $media_block;
        }
        ++$counter;
        $part_selector_count = 0;
      }
      else {
        if (isset($major_chunks[$counter])) {
          $major_chunks[$counter] .= "\n" . $media_block;
        }
        else {
          $major_chunks[$counter] = $media_block;
        }
      }
    }

    $file_info['parts'] = [];
    $overall_split = 0;
    $split_at = $selector_split_value;
    $chunk_split_value = (int) $this->config->get('css.ie.selector_limit') - $selector_split_value - 1;
    foreach ($major_chunks as $chunk_key => $chunks) {
      // Get the number of selectors.
      $selector_count = preg_match_all('/\{.+?\}|,/s', $chunks);

      // Pass through if selector count is low.
      if ($selector_count < $selector_split_value) {
        $overall_split += $selector_count;
        $subfile = $this->createSubfile($chunks, $overall_split, $file_info);
        if (!$subfile) {
          // Somthing broke; do not create a subfile.
          \Drupal::logger('advagg')->notice('Spliting up a CSS file failed. File info: <code>@info</code>', ['@info' => var_export($file_info, TRUE)]);
          return [];
        }
        $file_info['parts'][] = [
          'path' => $subfile,
          'selectors' => $selector_count,
        ];
        continue;
      }

      $media_query = '';
      if (strpos($chunks, '@media') !== FALSE) {
        $media_query_pos = strpos($chunks, '{');
        $media_query = substr($chunks, 0, $media_query_pos);
        $chunks = substr($chunks, $media_query_pos + 1);
      }

      // Split CSS into selector chunks.
      $split = preg_split('/(\{.+?\}|,)/si', $chunks, -1, PREG_SPLIT_DELIM_CAPTURE);

      // Setup and handle media queries.
      $new_css_chunk = [0 => ''];
      $selector_chunk_counter = 0;
      $counter = 0;
      if (!empty($media_query)) {
        $new_css_chunk[0] = $media_query . '{';
        $new_css_chunk[1] = '';
        ++$selector_chunk_counter;
        ++$counter;
      }
      // Have the key value be the running selector count and put split array
      // semi back together.
      foreach ($split as $value) {
        $new_css_chunk[$counter] .= $value;
        if (strpos($value, '}') === FALSE) {
          ++$selector_chunk_counter;
        }
        else {
          if ($counter + 1 < $selector_chunk_counter) {
            $selector_chunk_counter += ($counter - $selector_chunk_counter + 1) / 2;
          }
          $counter = $selector_chunk_counter;
          if (!isset($new_css_chunk[$counter])) {
            $new_css_chunk[$counter] = '';
          }
        }
      }

      // Group selectors.
      while (!empty($new_css_chunk)) {
        // Find where to split the array.
        $string_to_write = '';
        while (array_key_exists($split_at, $new_css_chunk) === FALSE) {
          --$split_at;
        }

        // Combine parts of the css so that it can be saved to disk.
        foreach ($new_css_chunk as $key => $value) {
          if ($key !== $split_at) {
            // Move this css row to the $string_to_write variable.
            $string_to_write .= $value;
            unset($new_css_chunk[$key]);
          }
          // We are at the split point.
          else {
            // Get the number of selectors in this chunk.
            $chunk_selector_count = preg_match_all('/\{.+?\}|,/s', $new_css_chunk[$key]);
            if ($chunk_selector_count < $chunk_split_value) {
              // The number of selectors at this point is below the threshold;
              // move this chunk to the write var and break out of the loop.
              $string_to_write .= $value;
              unset($new_css_chunk[$key]);
              $overall_split = $split_at;
              $split_at += $selector_split_value;
            }
            else {
              // The number of selectors with this chunk included is over the
              // threshold; do not move it. Change split position so the next
              // iteration of the while loop ends at the correct spot. Because
              // we skip unset here, this chunk will start the next part file.
              $overall_split = $split_at;
              $split_at += $selector_split_value - $chunk_selector_count;
            }
            break;
          }
        }

        // Handle media queries.
        if (!empty($media_query)) {
          // See if brackets need a new line.
          if (strpos($string_to_write, "\n") === 0) {
            $open_bracket = '{';
          }
          else {
            $open_bracket = "{\n";
          }
          if (strrpos($string_to_write, "\n") === strlen($string_to_write)) {
            $close_bracket = '}';
          }
          else {
            $close_bracket = "\n}";
          }

          // Fix syntax around media queries.
          if ($first) {
            $string_to_write .= $close_bracket;
          }
          elseif (empty($new_css_chunk)) {
            $string_to_write = $media_query . $open_bracket . $string_to_write;
          }
          else {
            $string_to_write = $media_query . $open_bracket . $string_to_write . $close_bracket;
          }
        }
        // Write the data.
        $subfile = $this->createSubfile($string_to_write, $overall_split, $file_info);
        if (!$subfile) {
          // Somthing broke; did not create a subfile.
          \Drupal::logger('advagg')->notice('Spliting up a CSS file failed. File info: <code>@info</code>', ['@info' => var_export($file_info, TRUE)]);
          return;
        }
        $sub_matches = [];
        $sub_selector_count = preg_match_all('/\{.+?\}|,/s', $string_to_write, $matches);
        $file_info['parts'][] = [
          'path' => $subfile,
          'selectors' => $sub_selector_count,
        ];
      }
    }
  }

  /**
   * Write CSS parts to disk; used when CSS selectors in one file is > 4096.
   *
   * @param string $css
   *   CSS data to write to disk.
   * @param int $overall_split
   *   Running count of what selector we are from the original file.
   * @param array $file_info
   *   File info array.
   *
   * @return string
   *   Saved path; FALSE on failure.
   */
  private function createSubfile($css, $overall_split, array &$file_info) {
    // Get the path from $file_info['data'].
    $file = advagg_get_relative_path($file_info['data']);
    if (!file_exists($file) || is_dir($file)) {
      return FALSE;
    }

    // Write the current chunk of the CSS into a file.
    $path = $this->partsPath . $file . $overall_split . '.css';
    $directory = dirname($path);
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    file_unmanaged_save_data($css, $path, FILE_EXISTS_REPLACE);
    if (!file_exists($path)) {
      return FALSE;
    }

    return $path;
  }

}
