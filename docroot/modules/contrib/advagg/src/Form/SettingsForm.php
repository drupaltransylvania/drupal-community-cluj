<?php

namespace Drupal\advagg\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg settings for this site.
 */
class SettingsForm extends ConfigFormBase {

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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The Date formatter service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer, DateFormatterInterface $date_formatter, StateInterface $state, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->dateFormatter = $date_formatter;
    $this->state = $state;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('date.formatter'),
      $container->get('state'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg.settings', 'system.performance'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advagg.settings');
    $form = [];
    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => t('Global Options'),
    ];
    $form['global']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable advanced aggregation'),
      '#default_value' => $config->get('enabled'),
      '#description' => t('Uncheck this box to completely disable AdvAgg functionality.'),
    ];
    $form['global']['core_groups'] = [
      '#type' => 'checkbox',
      '#title' => t('Use cores grouping logic'),
      '#default_value' => $config->get('css.combine_media') || $config->get('css.ie.limit_selectors') ? FALSE : $config->get('core_groups'),
      '#description' => t('Will group files just like core does.'),
      '#states' => [
        'enabled' => [
          '#edit-css-combine-media' => ['checked' => FALSE],
          '#edit-css-ie-limit-selectors' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['global']['dns_prefetch'] = [
      '#type' => 'checkbox',
      '#title' => t('Use DNS Prefetch for external CSS/JS.'),
      '#default_value' => $config->get('dns_prefetch'),
      '#description' => t('Start the DNS lookup for external CSS and JavaScript files as soon as possible.'),
    ];
    $options = [
      - 1 => t('Development'),
      1 => t('Normal'),
      3 => t('High'),
      5 => t('Aggressive'),
    ];

    $form['global']['cache_level'] = [
      '#type' => 'radios',
      '#title' => t('AdvAgg Cache Settings'),
      '#default_value' => $config->get('cache_level'),
      '#options' => $options,
      '#description' => t("No performance data yet but most use cases will probably want to use the Normal cache mode.", [
        '@information' => Url::fromRoute('advagg.info')->toString(),
      ]),
    ];

    $form['global']['dev_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="cache_level"]' => ['value' => '-1'],
        ],
      ],
    ];

    // Show msg about advagg css minify.
    if ($this->moduleHandler->moduleExists('advagg_css_minify') && $this->config('advagg_css_minify.settings')->get('advagg_css_minifier') > 0) {
      $form['global']['dev_container']['advagg_css_compress_msg'] = [
        '#markup' => '<p>' . t('The <a href="@css">AdvAgg CSS Minify module</a> is disabled when in development mode.', ['@css' => Url::fromRoute('advagg_css_minify.settings')->toString()]) . '</p>',
      ];

    }

    // Show msg about advagg js minify.
    if ($this->moduleHandler->moduleExists('advagg_js_minify') && $this->config('advagg_js_minify.settings')->get('advagg_js_minifier')) {
      $form['global']['dev_container']['advagg_js_minify_msg'] = [
        '#markup' => '<p>' . t('The <a href="@js">AdvAgg JS Minify module</a> is disabled when in development mode.', ['@js' => Url::fromRoute('advagg_js_minify.settings')->toString()]) . '</p>',
      ];
    }

    $form['global']['cron'] = [
      '#type' => 'details',
      '#title' => t('Cron Options'),
      '#description' => t('Unless you have a good reason to adjust these values you should leave them alone.'),
    ];

    // @ignore sniffer_squiz_commenting_poststatementcomment_found:27
    $short_times = array_combine([
      60 * 15, // 15 min.
      60 * 30, // 30 min.
      60 * 45, // 45 min.
      60 * 60, // 1 hour.
      60 * 60 * 2, // 2 hours.
      60 * 60 * 4, // 4 hours.
      60 * 60 * 6, // 6 hours.
      60 * 60 * 8, // 8 hours.
      60 * 60 * 10, // 10 hours.
      60 * 60 * 12, // 12 hours.
      60 * 60 * 18, // 18 hours.
      60 * 60 * 24, // 1 day.
      60 * 60 * 24 * 2, // 2 days.
    ], [
      60 * 15, // 15 min.
      60 * 30, // 30 min.
      60 * 45, // 45 min.
      60 * 60, // 1 hour.
      60 * 60 * 2, // 2 hours.
      60 * 60 * 4, // 4 hours.
      60 * 60 * 6, // 6 hours.
      60 * 60 * 8, // 8 hours.
      60 * 60 * 10, // 10 hours.
      60 * 60 * 12, // 12 hours.
      60 * 60 * 18, // 18 hours.
      60 * 60 * 24, // 1 day.
      60 * 60 * 24 * 2, // 2 days.
    ]);
    $long_times = array_combine([
      60 * 60 * 24 * 2, // 2 days.
      60 * 60 * 24 * 3, // 3 days.
      60 * 60 * 24 * 4, // 4 days.
      60 * 60 * 24 * 5, // 5 days.
      60 * 60 * 24 * 6, // 6 days.
      60 * 60 * 24 * 7, // 1 week.
      60 * 60 * 24 * 7 * 2, // 2 weeks.
      60 * 60 * 24 * 7 * 3, // 3 weeks.
      60 * 60 * 24 * 30, // 1 month.
      60 * 60 * 24 * 45, // 1 month 2 weeks.
      60 * 60 * 24 * 60, // 2 months.
    ], [
      60 * 60 * 24 * 2, // 2 days.
      60 * 60 * 24 * 3, // 3 days.
      60 * 60 * 24 * 4, // 4 days.
      60 * 60 * 24 * 5, // 5 days.
      60 * 60 * 24 * 6, // 6 days.
      60 * 60 * 24 * 7, // 1 week.
      60 * 60 * 24 * 7 * 2, // 2 weeks.
      60 * 60 * 24 * 7 * 3, // 3 weeks.
      60 * 60 * 24 * 30, // 1 month.
      60 * 60 * 24 * 45, // 1 month 2 weeks.
      60 * 60 * 24 * 60, // 2 months.
    ]);
    $last_ran = $this->state->get('advagg.cron_timestamp', NULL);
    if ($last_ran) {
      $last_ran = t('@time ago', ['@time' => $this->dateFormatter->formatInterval(REQUEST_TIME - $last_ran)]);
    }
    else {
      $last_ran = t('never');
    }
    $form['global']['cron']['cron_frequency'] = [
      '#type' => 'select',
      '#options' => $short_times,
      '#title' => 'Minimum amount of time between advagg_cron() runs.',
      '#default_value' => $config->get('cron_frequency'),
      '#description' => t('The default value for this is %value. The last time advagg_cron was ran is %time.', [
        '%value' => $this->dateFormatter->formatInterval($config->get('cron_frequency')),
        '%time' => $last_ran,
      ]),
    ];

    $form['global']['cron']['stale_file_threshold'] = [
      '#type' => 'select',
      '#options' => $long_times,
      '#title' => 'Delete aggregates modified more than a set time ago.',
      '#default_value' => $this->config('system.performance')->get('stale_file_threshold'),
      '#description' => t('The default value for this is %value.', [
        '%value' => $this->dateFormatter->formatInterval($this->config('system.performance')->getOriginal('stale_file_threshold')),
      ]),
    ];

    $form['global']['obscure'] = [
      '#type' => 'details',
      '#title' => t('Obscure Options'),
      '#description' => t('Some of the more obscure AdvAgg settings. Odds are you do not need to change anything in here.'),
    ];
    $form['global']['obscure']['include_base_url'] = [
      '#type' => 'checkbox',
      '#title' => t('Include the base_url variable in the hooks hash array.'),
      '#default_value' => $config->get('include_base_url'),
      '#description' => t('If you would like a unique set of aggregates for every permutation of the base_url (current value: %value) then enable this setting. <a href="@issue">Read more</a>.', [
        '%value' => $GLOBALS['base_url'],
        '@issue' => 'https://www.drupal.org/node/2353811',
      ]),
    ];
    $form['global']['obscure']['path_convert_absolute_to_protocol_relative'] = [
      '#type' => 'checkbox',
      '#title' => t('Convert absolute paths to be protocol relative paths.'),
      '#default_value' => $config->get('path.convert.absolute_to_protocol_relative'),
      '#description' => t('If the src to a CSS/JS file points starts with http:// or https://, convert it to use a protocol relative path //. Will also convert url() references inside of css files.'),
      '#states' => [
        'enabled' => [
          '#edit-path-convert-force-https' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['global']['obscure']['path_convert_force_https'] = [
      '#type' => 'checkbox',
      '#title' => t('Convert http:// to https://.'),
      '#default_value' => $config->get('path.convert.force_https'),
      '#description' => t('If the src to a CSS/JS file starts with http:// convert it https://. Will also convert url() references inside of css files.'),
      '#states' => [
        'enabled' => [
          '#edit-path-convert-absolut-to-protocol-relative' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['css'] = [
      '#type' => 'details',
      '#title' => t('CSS Options'),
      '#open' => TRUE,
    ];
    $form['css']['css_combine_media'] = [
      '#type' => 'checkbox',
      '#title' => t('Combine CSS files by using media queries'),
      '#default_value' => $config->get('css.combine_media'),
      '#description' => t('Will combine more CSS files together because different CSS media types can be used in the same file by using media queries. Use cores grouping logic needs to be unchecked in order for this to work. Also noted is that due to an issue with IE9, compatibility mode is forced off if this is enabled.'),
      '#states' => [
        'disabled' => [
          '#edit-core-groups' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['css']['css_ie_limit_selectors'] = [
      '#type' => 'checkbox',
      '#title' => t('Prevent more than %limit CSS selectors in an aggregated CSS file', ['%limit' => $config->get('css.ie.selector_limit')]),
      '#default_value' => $config->get('css.ie.limit_selectors'),
      '#description' => t('Internet Explorer before version 10; IE9, IE8, IE7, and IE6 all have 4095 as the limit for the maximum number of css selectors that can be in a file. Enabling this will prevent CSS aggregates from being created that exceed this limit. <a href="@link">More info</a>. Use cores grouping logic needs to be unchecked in order for this to work.', ['@link' => 'http://blogs.msdn.com/b/ieinternals/archive/2011/05/14/10164546.aspx']),
      '#states' => [
        'disabled' => [
          '#edit-core-groups' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['css']['css_ie_selector_limit'] = [
      '#type' => 'textfield',
      '#title' => t('The selector count the IE CSS limiter should use'),
      '#default_value' => $config->get('css.ie.selector_limit'),
      '#description' => t('Internet Explorer before version 10; IE9, IE8, IE7, and IE6 all have 4095 as the limit for the maximum number of css selectors that can be in a file. Use this field to modify the value used; 4095 sometimes may be still be too many with media queries.'),
      '#states' => [
        'visible' => [
          '#edit-css-ie-limit-selectors' => ['checked' => TRUE],
        ],
        'disabled' => [
          '#edit-css-ie-limit-selectors' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['css']['css_fix_type'] = [
      '#type' => 'checkbox',
      '#title' => t('Fix improperly set type'),
      '#default_value' => $config->get('css.fix_type'),
      '#description' => t('If type is external but does not start with http, https, or // change it to be type file. If type is file but it starts with http, https, or // change type to be external. Note that if this is causing issues, odds are you have a double slash when there should be a single; see <a href="@link">this issue</a>', [
        '@link' => 'https://www.drupal.org/node/2336217',
      ]),
    ];

    $form['js'] = [
      '#type' => 'details',
      '#title' => t('JS Options'),
      '#open' => TRUE,
    ];
    $form['js']['js_fix_type'] = [
      '#type' => 'checkbox',
      '#title' => t('Fix improperly set type'),
      '#default_value' => $config->get('js_fix_type'),
      '#description' => t('If type is external but does not start with http, https, or // change it to be type file. If type is file but it starts with http, https, or // change type to be external. Note that if this is causing issues, odds are you have a double slash when there should be a single; see <a href="@link">this issue</a>', [
        '@link' => 'https://www.drupal.org/node/2336217',
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advagg.settings')
      ->set('css.fix_type', $form_state->getValue('css_fix_type'))
      ->set('css.ie.limit_selectors', $form_state->getValue('css_ie_limit_selectors'))
      ->set('css.ie.selector_limit', $form_state->getValue('css_ie_selector_limit'))
      ->set('css.combine_media', $form_state->getValue('css_combine_media'))
      ->set('path.convert.force_https', $form_state->getValue('path_convert_force_https'))
      ->set('path.convert.absolute_to_protocol_relative', $form_state->getValue('path_convert_absolute_to_protocol_relative'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('core_groups', $form_state->getValue('core_groups'))
      ->set('dns_prefetch', $form_state->getValue('dns_prefetch'))
      ->set('cache_level', $form_state->getValue('cache_level'))
      ->set('cron_frequency', $form_state->getValue('cron_frequency'))
      ->set('include_base_url', $form_state->getValue('include_base_url'))
      ->set('js_fix_type', $form_state->getValue('js_fix_type'))
      ->save();
    $this->config('system.performance')
      ->set('stale_file_threshold', $form_state->getValue('stale_file_threshold'))
      ->save();

    // Clear relevant caches.
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    Cache::invalidateTags(['library_info', 'advagg_css', 'advagg_js']);

    parent::submitForm($form, $form_state);
  }

}
