<?php

namespace Drupal\advagg_css_minify\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg_css_minify settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The CSS asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The AdvAgg file status state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;

  /**
   * A CSS asset optimizer.
   *
   * @var \Drupal\advagg_css_minify\Asset\CssOptimizer
   */
  protected $optimizer;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $optimizer
   *   The optimizer for a single CSS asset.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AssetCollectionOptimizerInterface $css_collection_optimizer, StateInterface $advagg_files, AssetOptimizerInterface $optimizer) {
    parent::__construct($config_factory);
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->advaggFiles = $advagg_files;
    $this->optimizer = $optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('state.advagg.files'),
      $container->get('asset.css.optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_css_minify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg_css_minify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advagg_css_minify.settings');
    $form = [];
    if ($this->config('advagg.settings')->get('cache_level') < 0) {
      $form['advagg_devel_msg'] = [
        '#markup' => '<p>' . t('The settings below will not have any effect because AdvAgg is currently in <a href="@devel">development mode</a>. Once the cache settings have been set to normal or higher, CSS minification will take place.', [
          '@devel' => Url::fromRoute('advagg.settings', [], [
            'fragment' => 'edit-advagg-cache-level',
          ])->toString(),
        ]) . '</p>',
      ];
    }

    list($options, $description) = $this->optimizer->getConfiguration();

    $form['minifier'] = [
      '#type' => 'radios',
      '#title' => t('Minification: Select a default minifier'),
      '#default_value' => $config->get('minifier'),
      '#options' => $options,
      '#description' => Xss::filter($description),
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
      if ($info['fileext'] !== 'css') {
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
      $form['per_file_settings'][$dir]['advagg_css_minify_file_settings_' . $form_api_filename] = [
        '#type' => 'radios',
        '#title' => t('%filename: Select a minifier', ['%filename' => $name]),
        '#default_value' => isset($file_settings[$name]) ? $file_settings[$name] : -1,
        '#options' => $options,
      ];
      if ($form['per_file_settings'][$dir]['advagg_css_minify_file_settings_' . $form_api_filename]['#default_value'] != -1) {
        $form['per_file_settings'][$dir]['#open'] = TRUE;
        $form['per_file_settings']['#open'] = TRUE;
      }
    }

    // No css files are found.
    if (empty($files)) {
      $form['per_file_settings']['#description'] = t('No CSS files have been aggregated. You need to enable aggregation. No css files where found in the advagg_files table.');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('advagg_css_minify.settings');

    // Extract/combine per file settings.
    if ($file_settings = $config->get('file_settings')) {
      $file_settings = array_column($file_settings, NULL, 'path');
    }
    else {
      $file_settings = [];
    }
    foreach ($form_state->getValues() as $key => $value) {
      // Skip if not advagg_css_minify_file_settings.
      if (strpos($key, 'advagg_css_minify_file_settings_') === FALSE) {
        continue;
      }
      $path = str_replace(['__', '--'], ['/', '.'], substr($key, 32));
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

    // Clear relevant caches.
    $this->cssCollectionOptimizer->deleteAll();
    Cache::invalidateTags(['library_info', 'advagg_css']);

    $config = $this->config('advagg_css_minify.settings')
      ->set('minifier', $form_state->getValue('minifier'))
      ->set('file_settings', array_values($file_settings))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
