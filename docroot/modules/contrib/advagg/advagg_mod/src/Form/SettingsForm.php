<?php

namespace Drupal\advagg_mod\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg_mod settings for this site.
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
   * The module handler service.
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    parent::__construct($config_factory);

    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_mod_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg_mod.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advagg_mod.settings');
    $form = [];
    $form['js'] = [
      '#type' => 'details',
      '#title' => t('JS'),
      '#open' => TRUE,
    ];
    $form['js']['js_preprocess'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable preprocess on all JS'),
      '#default_value' => $config->get('js_preprocess'),
      '#description' => t('Force all JavaScript to have the preprocess attribute be set to TRUE. All JavaScript files will be aggregated if enabled.'),
    ];
    /*$form['js']['js_get_external_dns'] = [
      '#type' => 'checkbox',
      '#title' => t('DNS prefetch src attributes found in the HTML content'),
      '#default_value' => $config->get('js_get_external_dns'),
      '#description' => t('Scan any JavaScript that was added to the content part of the page incorrectly for a src attribute; if found prefetch that DNS entry.'),
    ];*/

    // Optimize JavaScript Ordering.
    $form['js']['adjust_sort'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Optimize JavaScript Ordering'),
      '#description' => t('The settings in here might change the order in which the JavaScript is loaded. It will move the scripts around so that more optimal aggregates are built. In most cases enabling these checkboxes will cause no negative side effects.'),
    ];
    $form['js']['adjust_sort']['js_adjust_sort_external'] = [
      '#type' => 'checkbox',
      '#title' => t('Move all external scripts to the top of the execution order'),
      '#default_value' => $config->get('js_adjust_sort_external'),
      '#description' => t('This will group all external JavaScript files to be above all other JavaScript.'),
    ];
    $form['js']['adjust_sort']['js_adjust_sort_browsers'] = [
      '#type' => 'checkbox',
      '#title' => t('Move all browser conditional JavaScript to the bottom of the group'),
      '#default_value' => $config->get('js_adjust_sort_browsers'),
      '#description' => t('This will group all browser conditional JavaScript to be in the lowest group of that conditional rule.'),
    ];

    // Adjust javascript location and execution.
    $form['js']['placement'] = [
      '#type' => 'details',
      '#title' => t('Adjust javascript location and execution'),
      '#description' => t('Most of the time adjusting the settings are safe but in some rare cases adjusting these can cause serious JavaScript issues with your site.'),
    ];
    $form['js']['placement']['js_footer'] = [
      '#type' => 'radios',
      '#title' => t('Move JS to the footer'),
      '#default_value' => $config->get('js_footer'),
      '#options' => [
        0 => t('Disabled'),
        3 => t('All but core'),
        2 => t('All'),
      ],
      '#description' => t("If you have JavaScript inline in the body of your document, such as if you are displaying ads, you may need to keep Drupal JS Libraries in the head instead of moving them to the footer. This will keep all JS added with the JS_LIBRARY group in the head while still moving all other JavaScript to the footer."),
    ];
    $form['js']['placement']['js_defer'] = [
      '#type' => 'radios',
      '#title' => t('Deferred JavaScript Execution: Add The defer Tag To All Script Tags'),
      '#default_value' => $config->get('js_defer'),
      '#options' => [
        0 => t('Disabled'),
        2 => t('All but external scripts'),
        1 => t('All (might break things)'),
      ],
      '#description' => t('This will delay the script execution until the HTML parser has finished. This will have a similar effect to moving all JavaScript to the footer. This might break javascript (especially inline); only use after extensive testing! <a href="@link">More Info</a>', [
        '@link' => 'http://peter.sh/experiments/asynchronous-and-deferred-javascript-execution-explained/',
      ]),
    ];
    $form['js']['placement']['js_async_in_header'] = [
      '#type' => 'checkbox',
      '#title' => t('Asynchronous JavaScript Execution: Group together in the header'),
      '#default_value' => $config->get('js_async_in_header'),
      '#description' => t('This will move all async JavaScript code to the header in the same group.'),
    ];
    $form['js']['placement']['expert'] = [
      '#type' => 'details',
      '#title' => t('Experimental Settings'),
      '#open' => $config->get('js_async'),
    ];
    $form['js']['placement']['expert']['js_async'] = [
      '#type' => 'checkbox',
      '#title' => t('Here there be dragons! Asynchronous JavaScript Execution: Add The async Tag To All Script Tags'),
      '#default_value' => $config->get('js_async'),
      '#description' => t('This will cause the script to be downloaded in the background and executed out of order. This will break most javascript as most code is not async safe; only use after extensive testing! <a href="@link">More Info</a>', [
        '@link' => 'http://peter.sh/experiments/asynchronous-and-deferred-javascript-execution-explained/',
      ]),
    ];

    $form['css'] = [
      '#type' => 'details',
      '#title' => t('CSS'),
      '#open' => TRUE,
    ];
    $form['css']['css_preprocess'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable preprocess on all CSS'),
      '#default_value' => $config->get('css_preprocess'),
      '#description' => t('Force all CSS to have the preprocess attribute be set to TRUE. All CSS files will be aggregated if enabled.'),
    ];
    $form['css']['css_preprocess']['#description'] .= $this->moduleHandler->moduleExists('advagg_bundler') ? ' ' . t('You might want to increase the <a href="@link">CSS Bundles Per Page</a> if this is checked.', ['@link' => Url::fromRoute('advagg_bundler.settings')->toString()]) : '';

    // Only test the translate option if
    // the locale function is defined OR
    // the locale_custom_strings variable is not empty.
    $lang = isset($this->languageManager->getCurrentLanguage()->language) ? $this->languageManager()->getCurrentLanguage->language : 'en';
    $locale_custom_strings = Settings::get('locale_custom_strings_' . $lang, []);
    if (function_exists('locale') || !empty($locale_custom_strings)) {
      $form['css']['css_translate'] = [
        '#type' => 'checkbox',
        '#title' => t('Translate CSS content strings'),
        '#default_value' => $config->get('css_translate'),
        '#description' => t('Run strings inside of quotes of the content attribute through the <a href="@t">t() function</a>. An alternative to this can be found in this <a href="@post">blog post</a>.', [
          '@t' => 'https://api.drupal.org/api/drupal/includes!bootstrap.inc/function/t/7',
          '@post' => 'http://fourkitchens.com/blog/2013/08/15/multilingual-css-generated-content-drupal',
        ]),
      ];
    }
    // Optimize CSS Ordering.
    $form['css']['adjust_sort'] = [
      '#type' => 'details',
      '#title' => t('Optimize CSS Ordering'),
      '#description' => t('The settings in here might change the order in which the CSS is loaded. It will move the CSS around so that more optimal aggregates are built. In most cases enabling these checkboxes will cause no negative side effects.'),
    ];
    $form['css']['adjust_sort']['css_adjust_sort_external'] = [
      '#type' => 'checkbox',
      '#title' => t('Move all external CSS to the top of the execution order'),
      '#default_value' => $config->get('css_adjust_sort_external'),
      '#description' => t('This will group all external CSS files to be above all other CSS.'),
    ];
    $form['css']['adjust_sort']['css_adjust_sort_browsers'] = [
      '#type' => 'checkbox',
      '#title' => t('Move all browser conditional CSS to the bottom of the group'),
      '#default_value' => $config->get('css_adjust_sort_browsers'),
      '#description' => t('This will group all browser conditional CSS to be in the lowest group of that conditional rule.'),
    ];
    // Adjust CSS location and execution.
    $form['css']['placement'] = [
      '#type' => 'details',
      '#title' => t('Adjust CSS location and execution'),
    ];
    $form['css']['placement']['expert'] = [
      '#type' => 'details',
      '#title' => t('Experimental Settings'),
      '#open' => $config->get('css_defer'),
    ];
    $form['css']['placement']['expert']['css_defer'] = [
      '#type' => 'radios',
      '#title' => t('Deferred CSS Execution: Use JS to load CSS'),
      '#default_value' => $config->get('css_defer'),
      '#options' => [
        0 => t('Disabled'),
        3 => t('In head'),
        7 => t('In footer'),
      ],
      '#description' => t('This will try to optimize CSS delivery by using JavaScript to load the CSS. This might break CSS on different browsers and will cause a flash of unstyled content (FOUC). Only enable after extensive testing! <a href="@link">More Info</a>', [
        '@link' => 'http://stackoverflow.com/questions/19374843/css-delivery-optimization-how-to-defer-css-loading',
      ]),
    ];
    $form['css']['placement']['expert']['css_defer_admin'] = [
      '#type' => 'checkbox',
      '#title' => t('Use JS to load CSS in the admin theme'),
      '#default_value' => $config->get('css_defer_admin'),
      '#description' => t('This will optimize CSS delivery with JavaScript when viewing the admin theme'),
      '#states' => [
        'disabled' => [
          ':input[name="css_defer"]' => ['value' => '0'],
        ],
      ],
    ];
    $form['css']['placement']['expert']['css_defer_js_code'] = [
      '#type' => 'radios',
      '#title' => t('How to include the JS loading code'),
      '#default_value' => $config->get('css_defer_js_code'),
      '#options' => [
        0 => t('Inline javascript loader library (If enabled this is recommended)'),
        2 => t('Local file included in aggregate'),
        4 => t('Externally load the latest from github'),
      ],
      '#description' => t('The <a href="@link">loadCSS</a> library can be included in various ways.', [
        '@link' => 'https://github.com/filamentgroup/loadCSS',
      ]),
      '#states' => [
        'disabled' => [
          ':input[name="css_defer"]' => ['value' => '0'],
        ],
      ],
    ];

    $form['unified_multisite'] = [
      '#type' => 'details',
      '#title' => t('Unified Multisite'),
      '#description' => t('For most people this is a setting that should not be used.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['unified_multisite']['unified_multisite_dir'] = [
      '#type' => 'textfield',
      '#title' => t('Shared Directory'),
      '#default_value' => $config->get('unified_multisite_dir'),
      '#size' => 60,
      '#maxlength' => 128,
      '#description' => t('This allows you to have a shared directory for all CSS/JS aggregates if this install of drupal is used as a <a href="@multisite">multisite</a>. If this servers multisites share a similar CSS/JS structure then a lot of resources can be saved by not rebuilding the same aggregates in each site of the multisite. Make sure that you use the same directory and advagg settings in each multisite for this setting to work efficiently. Current <a href="@info">hash value</a> of settings on this site: %value. If this value is different across the servers multisites then this will not save server resources as a different file will be built due to AdvAgg having different settings. Note that $base_path is used in the hash value so in some multisite cases it will be impossible to use this setting.', [
        '@multisite' => 'https://drupal.org/documentation/install/multi-site',
        '@info' => Url::fromRoute('advagg.info')->toString(),
        '%value' => advagg_get_current_hooks_hash(),
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advagg_mod.settings')
      ->set('css_adjust_sort_browsers', $form_state->getValue('css_adjust_sort_browsers'))
      ->set('css_adjust_sort_external', $form_state->getValue('css_adjust_sort_external'))
      ->set('css_defer', $form_state->getValue('css_defer'))
      ->set('css_defer_admin', $form_state->getValue('css_defer_admin'))
      ->set('css_defer_js_code', $form_state->getValue('css_defer_js_code'))
      ->set('css_preprocess', $form_state->getValue('css_preprocess'))
      ->set('css_translate', $form_state->getValue('css_translate'))
      ->set('ga_inline_to_file', $form_state->getValue('ga_inline_to_file'))
      ->set('js_adjust_sort_browsers', $form_state->getValue('js_adjust_sort_browsers'))
      ->set('js_adjust_sort_external', $form_state->getValue('js_adjust_sort_external'))
      ->set('js_async', $form_state->getValue('js_async'))
      ->set('js_async_in_header', $form_state->getValue('js_async_in_header'))
      ->set('js_defer', $form_state->getValue('js_defer'))
      ->set('js_footer', $form_state->getValue('js_footer'))
      ->set('js_get_external_dns', $form_state->getValue('js_get_external_dns'))
      ->set('js_preprocess', $form_state->getValue('js_preprocess'))
      ->set('prefetch', $form_state->getValue('prefetch'))
      ->set('unified_multisite_dir', $form_state->getValue('unified_multisite_dir'))
      ->save();

    // Clear relevant caches.
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    Cache::invalidateTags(['library_info', 'advagg_css', 'advagg_js']);

    parent::submitForm($form, $form_state);
  }

}
