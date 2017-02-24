<?php

namespace Drupal\advagg\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\State\StateInterface;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg settings for this site.
 */
class OperationsForm extends ConfigFormBase {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The CSS asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The JavaScript asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * A state information store for the AdvAgg generated aggregates.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggAggregates;

  /**
   * A state information store for the AdvAgg scanned files.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;


  /**
   * Constructs the OperationsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The Date formatter service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   A state information store for the AdvAgg scanned files.
   * @param \Drupal\Core\State\StateInterface $advagg_aggregates
   *   A state information store for the AdvAgg generated aggregates.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PrivateKey $private_key, DateFormatterInterface $date_formatter, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer, StateInterface $advagg_files, StateInterface $advagg_aggregates) {
    parent::__construct($config_factory);
    $this->privateKey = $private_key;
    $this->dateFormatter = $date_formatter;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->advaggFiles = $advagg_files;
    $this->advaggAggregates = $advagg_aggregates;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('private_key'),
      $container->get('date.formatter'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('state.advagg.files'),
      $container->get('state.advagg.aggregates')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_operations';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    // Explain what can be done on this page.
    $form['tip'] = [
      '#markup' => '<p>' . t('This is a collection of commands to control the cache and to manage testing of this module. In general this page is useful when troubleshooting some aggregation issues. For normal operations, you do not need to do anything on this page below the Smart Cache Flush. There are no configuration options here.') . '</p>',
    ];
    $form['wrapper'] = [
      '#prefix' => "<div id='operations-wrapper'>",
      '#suffix' => "</div>",
    ];

    // Buttons to do stuff.
    // AdvAgg smart cache flushing.
    $form['smart_flush'] = [
      '#type' => 'fieldset',
      '#title' => t('Smart Cache Flush'),
      '#description' => t('Scan all files referenced in aggregated files. If any of them have changed, clear that cache so the changes will go out.'),
    ];
    $form['smart_flush']['advagg_flush'] = [
      '#type' => 'submit',
      '#value' => t('Flush AdvAgg Cache'),
      '#submit' => ['::advaggFlushCache'],
      '#ajax' => [
        'callback' => '::tasksAjax',
        'wrapper' => 'operations-wrapper',
      ],
    ];

    // Set/Remove Bypass Cookie.
    $form['bypass'] = [
      '#type' => 'fieldset',
      '#title' => t('Aggregation Bypass Cookie'),
      '#description' => t('This will set or remove a cookie that disables aggregation for a set period of time.'),
    ];
    $bypass_length = array_combine([
      60 * 60 * 6,
      60 * 60 * 12,
      60 * 60 * 24,
      60 * 60 * 24 * 2,
      60 * 60 * 24 * 7,
      60 * 60 * 24 * 30,
      60 * 60 * 24 * 365,
    ], [
      60 * 60 * 6,
      60 * 60 * 12,
      60 * 60 * 24,
      60 * 60 * 24 * 2,
      60 * 60 * 24 * 7,
      60 * 60 * 24 * 30,
      60 * 60 * 24 * 365,
    ]);
    $form['bypass']['timespan'] = [
      '#type' => 'select',
      '#title' => 'Bypass length',
      '#options' => $bypass_length,
    ];
    $form['bypass']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Toggle The "aggregation bypass cookie" For This Browser'),
      '#attributes' => [
        'onclick' => 'javascript:return advagg_toggle_cookie()',
      ],
      '#submit' => ['::toggleBypassCookie'],
    ];
    // Add in aggregation bypass cookie javascript.
    $form['#attached']['drupalSettings']['advagg'] = [
      'key' => Crypt::hashBase64($this->privateKey->get()),
    ];
    $form['#attached']['library'][] = 'advagg/admin.operations';

    // Tasks run by cron.
    $form['cron'] = [
      '#type' => 'fieldset',
      '#title' => t('Cron Maintenance Tasks'),
      'description' => [
        '#markup' => t('The following 3 operations are ran on cron but you can run them manually here.'),
      ],
    ];
    $form['cron']['wrapper'] = [
      '#prefix' => "<div id='cron-wrapper'>",
      '#suffix' => "</div>",
    ];
    $form['cron']['smart_file_flush'] = [
      '#type' => 'details',
      '#title' => t('Clear All Stale Files'),
      '#description' => t('Remove all stale files. Scan all files in the advagg_css/js directories and remove the ones that have not been accessed in the last 30 days.'),
    ];
    $form['cron']['smart_file_flush']['advagg_flush_stale_files'] = [
      '#type' => 'submit',
      '#value' => t('Remove All Stale Files'),
      '#submit' => ['::clearStaleAggregates'],
      '#ajax' => [
        'callback' => '::cronTasksAjax',
        'wrapper' => 'cron-wrapper',
      ],
    ];
    $form['cron']['remove_missing_files'] = [
      '#type' => 'details',
      '#title' => t('Clear Missing Files From Database'),
      '#description' => t('Scan for missing files and remove the associated entries from the database.'),
    ];
    $form['cron']['remove_missing_files']['advagg_remove_missing_files_from_db'] = [
      '#type' => 'submit',
      '#value' => t('Clear Missing Files From Database'),
      '#submit' => ['::clearMissingFiles'],
      '#ajax' => [
        'callback' => '::cronTasksAjax',
        'wrapper' => 'cron-wrapper',
      ],
    ];
    $form['cron']['remove_old_aggregates'] = [
      '#type' => 'details',
      '#title' => t('Delete Unused Aggregates From Database'),
      '#description' => t('Delete aggregates that have not been accessed in the last 6 weeks.'),
    ];
    $form['cron']['remove_old_aggregates']['advagg_remove_old_unused_aggregates'] = [
      '#type' => 'submit',
      '#value' => t('Delete Unused Aggregates From Database'),
      '#submit' => ['::clearOldUnusedAggregates'],
      '#ajax' => [
        'callback' => '::cronTasksAjax',
        'wrapper' => 'cron-wrapper',
      ],
    ];

    // Hide drastic measures as they should not be done unless really needed.
    $form['drastic_measures'] = [
      '#type' => 'details',
      '#title' => t('Drastic Measures'),
      '#description' => t('The options below should normally never need to be done.'),
    ];
    $form['drastic_measures']['wrapper'] = [
      '#prefix' => "<div id='drastic-measures-wrapper'>",
      '#suffix' => "</div>",
    ];
    $form['drastic_measures']['dumb_cache_flush'] = [
      '#type' => 'details',
      '#title' => t('Clear All Caches'),
      '#description' => t('Remove all entries from the advagg cache and file information stores. Useful if you suspect a cache is not getting cleared.'),
    ];
    $form['drastic_measures']['dumb_cache_flush']['advagg_flush_all_caches'] = [
      '#type' => 'submit',
      '#value' => t('Clear All Caches & File Information'),
      '#submit' => ['::clearAll'],
      '#ajax' => [
        'callback' => '::drasticTasksAjax',
        'wrapper' => 'drastic-measures-wrapper',
      ],
    ];
    $form['drastic_measures']['dumb_file_flush'] = [
      '#type' => 'details',
      '#title' => t('Clear All Files'),
      '#description' => t('Remove all generated files. Useful if you think some of the generated files got corrupted and thus need to be deleted.'),
    ];
    $form['drastic_measures']['dumb_file_flush']['advagg_flush_all_files'] = [
      '#type' => 'submit',
      '#value' => t('Remove All Generated Files'),
      '#submit' => ['::clearAggregates'],
      '#ajax' => [
        'callback' => '::drasticTasksAjax',
        'wrapper' => 'drastic-measures-wrapper',
      ],
    ];
    $form['drastic_measures']['force_change'] = [
      '#type' => 'details',
      '#title' => t('Force new aggregates'),
      '#description' => t('Force the creation of all new aggregates by incrementing a global counter. Current value of counter: %value. This is useful if a CDN has cached an aggregate incorrectly as it will force new ones to be used even if nothing else has changed.', [
        '%value' => advagg_get_global_counter(),
      ]),
    ];
    $form['drastic_measures']['force_change']['increment_global_counter'] = [
      '#type' => 'submit',
      '#value' => t('Increment Global Counter'),
      '#submit' => ['::incrementCounter'],
      '#ajax' => [
        'callback' => '::drasticTasksAjax',
        'wrapper' => 'drastic-measures-wrapper',
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Perform a smart flush.
   */
  public function flushCache() {
    // TODO
    $flushed = TRUE;
    if ($flushed) {
      Cache::invalidateTags(['library_info', 'advagg_css', 'advagg_js']);
    }

    if ($this->config->get('cache_level') >= 0) {
      // Display a simple message if not in Development mode.
      drupal_set_message(t('Advagg Caches Updated'));
    }
    else {
      //if (empty($flushed)) {
      //  drupal_set_message(t('No changes found. Nothing was cleared.'));
      //  return;
      //}
    }
  }

  /**
   * Report results via Ajax.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  public function tasksAjax(array &$form) {
    return $form['wrapper'];
  }

  /**
   * Clear out all advagg cache bins and clear out all advagg aggregated files.
   */
  public function clearAggregates() {
    // Clear out the cache.
    Cache::invalidateTags(['library_info', 'advagg_css', 'advagg_js']);

    $css_files = $this->cssCollectionOptimizer->deleteAllReal();
    $js_files = $this->jsCollectionOptimizer->deleteAllReal();

    // Report back the results.
    drupal_set_message(t('All AdvAgg aggregates have been deleted. %css_count CSS files and %js_count JS files have been removed.', [
      '%css_count' => count($css_files),
      '%js_count' => count($js_files),
    ]));
  }

  /**
   * Clear ALL saved information and aggregates.
   */
  public function clearAll() {
    $this->clearAggregates();
    $this->advaggAggregates->deleteAll();
    $this->advaggFiles->deleteAll();
    drupal_set_message(t('All AdvAgg cached information and aggregates deleted.'));
  }

  /**
   * Clear out all stale advagg aggregated files.
   */
  public function clearStaleAggregates() {
    // Run the command.
    $css_count = count($this->cssCollectionOptimizer->deleteStale());
    $js_count = count($this->jsCollectionOptimizer->deleteStale());

    // Report back the results.
    if ($css_count || $js_count) {
      drupal_set_message(t('All stale aggregates have been deleted. %css_count CSS files and %js_count JS files have been removed.', [
        '%css_count' => $css_count,
        '%js_count' => $js_count,
      ]));
    }
    else {
      drupal_set_message(t('No stale aggregates found. Nothing was deleted.'));
    }
  }

  /**
   * Clear out all advagg cache bins and increment the counter.
   */
  public function incrementCounter() {
    // Clear out the cache and delete aggregates.
    $this->clearAggregates();

    // Increment counter.
    $new_value = $this->config('advagg.settings')->get('global_counter') + 1;
    $this->config('advagg.settings')
      ->set('global_counter', $new_value)
      ->save();
    drupal_set_message(t('Global counter is now set to %new_value', [
      '%new_value' => $new_value,
    ]));
  }

  /**
   * Report results from the drastic measure tasks via Ajax.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  public function drasticTasksAjax(array &$form) {
    return $form['drastic_measures']['wrapper'];
  }

  /**
   * Scan for missing files and remove the associated entries in the database.
   */
  public function clearMissingFiles() {
    $deleted = $this->advaggFiles->clearMissingFiles();
    $deleted_aggregates = $this->advaggAggregates->clearMissingFiles();
    if (empty($deleted)) {
      drupal_set_message(t('No missing files found or they could not be safely cleared out of the database.'));
    }
    else {
      drupal_set_message(t('Some missing files were found and could be safely cleared out of the database. <pre> @raw </pre>', [
        '@raw' => print_r($deleted, TRUE),
      ]));
    }
  }

  /**
   * Report results from the cron tasks via Ajax.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  public function cronTasksAjax(array &$form) {
    return $form['cron']['wrapper'];
  }

  /**
   * Delete aggregates that have not been accessed in the last 6 weeks.
   */
  public function clearOldUnusedAggregates() {
    // Remove unused aggregates.
    $count = count($this->cssCollectionOptimizer->deleteOld());
    $count += count($this->jsCollectionOptimizer->deleteOld());
    if (empty($count)) {
      drupal_set_message(t('No old and unused aggregates found. Nothing was deleted.'));
    }
    else {
      drupal_set_message(t('Some old and unused aggregates were found. A total of %count database entries were removed.', [
        '%count' => $count,
      ]));
    }
  }

  /**
   * Set or remove the AdvAggDisabled cookie.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function toggleBypassCookie(array &$form, FormStateInterface $form_state) {
    $cookie_name = 'AdvAggDisabled';
    $key = Crypt::hashBase64($this->privateKey->get());

    // If the cookie does exist then remove it.
    if (!empty($_COOKIE[$cookie_name]) && $_COOKIE[$cookie_name] == $key) {
      setcookie($cookie_name, '', -1, $GLOBALS['base_path'], '.' . $_SERVER['HTTP_HOST']);
      unset($_COOKIE[$cookie_name]);
      drupal_set_message(t('AdvAgg Bypass Cookie Removed.'));
    }
    // If the cookie does not exist then set it.
    else {
      setcookie($cookie_name, $key, REQUEST_TIME + $form_state->getValue('timespan'), $GLOBALS['base_path'], '.' . $_SERVER['HTTP_HOST']);
      $_COOKIE[$cookie_name] = $key;
      drupal_set_message(t('AdvAgg Bypass Cookie Set for %time.', [
        '%time' => $this->dateFormatter->formatInterval($form_state->getValue('timespan')),
      ]));
    }
  }

}
