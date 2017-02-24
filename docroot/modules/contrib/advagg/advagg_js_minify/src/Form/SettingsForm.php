<?php

namespace Drupal\advagg_js_minify\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg_js_minify settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The JavaScript asset collection optimizer service.
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
   * A JS asset optimizer.
   *
   * @var \Drupal\advagg_js_minify\Asset\CssOptimizer
   */
  protected $optimizer;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $optimizer
   *   The optimizer for a single JS asset.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AssetCollectionOptimizerInterface $js_collection_optimizer, StateInterface $advagg_files, AssetOptimizerInterface $optimizer) {
    parent::__construct($config_factory);
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->advaggFiles = $advagg_files;
    $this->optimizer = $optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('state.advagg.files'),
      $container->get('asset.js.optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_js_minify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg_js_minify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('advagg_js_minify.settings');
    $form = [];
    if ($this->config('advagg.settings')->get('cache_level') < 0) {
      $form['advagg_devel_msg'] = [
        '#markup' => '<p>' . t('The settings below will not have any effect because AdvAgg is currently in <a href="@devel">development mode</a>. Once the cache settings have been set to normal or aggressive, JS minification will take place.', [
          '@devel' => Url::fromRoute('advagg.settings', [], [
            'fragment' => 'edit-advagg-cache-level',
          ]),
        ]) . '</p>',
      ];
    }

    list($options, $description) = $this->optimizer->getConfiguration();

    $form['minifier'] = [
      '#type' => 'radios',
      '#title' => t('Minification: Select a minifier'),
      '#default_value' => $config->get('minifier'),
      '#options' => $options,
      '#description' => \Drupal\Component\Utility\Xss::filter($description),
    ];
    $form['add_license'] = [
      '#type' => 'checkbox',
      '#title' => t('Add licensing comments'),
      '#default_value' => $config->get('add_license'),
      '#description' => t("If unchecked, the Advanced Aggregation module's licensing comments
      will be omitted from the aggregated files. Omitting the comments will produce somewhat better scores in
      some automated security scans but otherwise should not affect your site. These are included by default in order to better follow the spirit of the GPL by providing the source for javascript files."),
    ];

    $options[-1] = t('Default');
    ksort($options);

    $form['per_file_settings'] = [
      '#type' => 'details',
      '#title' => t('Per File Settings'),
    ];
    $files = $this->advaggFiles->getAll();
    $file_settings = $config->get('file_settings');
    if ($file_settings) {
      $file_settings = array_column($file_settings, 'minifier', 'path');
    }
    foreach ($files as $name => $info) {
      if ($info['fileext'] !== 'js') {
        continue;
      }
      $dir = dirname($name);
      if (!isset($form['per_file_settings'][$dir])) {
        $form['per_file_settings'][$dir] = [
          '#type' => 'details',
          '#title' => $dir,
        ];
      }
      $form_api_filename = str_replace(['/', '.'], ['__', '--'], $name);
      $form['per_file_settings'][$dir]['advagg_js_minify_file_settings_' . $form_api_filename] = [
        '#type' => 'radios',
        '#title' => t('%filename: Select a minifier', ['%filename' => $name]),
        '#default_value' => isset($file_settings[$name]) ? $file_settings[$name] : -1,
        '#options' => $options,
      ];
      if ($form['per_file_settings'][$dir]['advagg_js_minify_file_settings_' . $form_api_filename]['#default_value'] != -1) {
        $form['per_file_settings'][$dir]['#open'] = TRUE;
        $form['per_file_settings']['#open'] = TRUE;
      }
    }

    // No js files are found.
    if (empty($files)) {
      $form['per_file_settings']['#description'] = t('No JS files have been aggregated. You need to enable aggregation. No js files where found in the advagg_files table.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('advagg_js_minify.settings');

    // Extract/combine per file settings.
    if ($file_settings = $config->get('file_settings')) {
      $file_settings = array_column($file_settings, NULL, 'path');
    }
    else {
      $file_settings = [];
    }
    foreach ($form_state->getValues() as $key => $value) {
      // Skip if not a advagg_js_minify_file_settings form item.
      if (strpos($key, 'advagg_js_minify_file_settings_') === FALSE) {
        continue;
      }
      $path = str_replace(['__', '--'], ['/', '.'], substr($key, 31));
      if ($value == -1) {
        unset($file_settings[$path]);
        continue;
      }
      else {
        $file_settings[$path] = [
          'path' => $path,
          'minifier' => $value,
        ];
      }
    }

    // Clear Caches.
    $this->jsCollectionOptimizer->deleteAll();
    Cache::invalidateTags(['library_info', 'advagg_js']);

    // Save settings.
    $config->set('add_license', $form_state->getValue('add_license'))
      ->set('minifier', $form_state->getValue('minifier'))
      ->set('file_settings', array_values($file_settings))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
