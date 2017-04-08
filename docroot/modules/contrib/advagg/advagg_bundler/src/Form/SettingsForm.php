<?php

namespace Drupal\advagg_bundler\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure advagg_bundler settings for this site.
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
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer) {
    parent::__construct($config_factory);

    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_bundler_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['advagg_bundler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advagg_bundler.settings');
    $form = [];
    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => t('Bundler is Active'),
      '#default_value' => $config->get('active'),
      '#description' => t('If not checked, the bundler will not split up aggregates.'),
    ];

    $options = [
      0 => 0,
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
      11 => 11,
      12 => 12,
      13 => 13,
      14 => 14,
      15 => 15,
    ];
    $form['css'] = [
      '#type' => 'fieldset',
      '#title' => t('CSS Bundling options.'),
    ];
    $form['css']['max_css'] = [
      '#type' => 'select',
      '#title' => t('Target Number Of CSS Bundles Per Page'),
      '#default_value' => $config->get('max_css'),
      '#options' => $options,
      '#description' => t('If 0 is selected then the bundler is disabled'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['css']['css_logic'] = [
      '#type' => 'radios',
      '#title' => t('Grouping logic'),
      '#default_value' => $config->get('css_logic'),
      '#options' => [
        0 => t('File count'),
        1 => t('File size'),
      ],
      '#description' => t('If file count is selected then each bundle will try to have a similar number of original files aggregated inside of it. If file size is selected then each bundle will try to have a similar file size.'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['js'] = [
      '#type' => 'fieldset',
      '#title' => t('JavaScript Bundling options.'),
    ];
    $form['js']['max_js'] = [
      '#type' => 'select',
      '#title' => t('Target Number Of JS Bundles Per Page'),
      '#default_value' => $config->get('max_js'),
      '#options' => $options,
      '#description' => t('If 0 is selected then the bundler is disabled'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['js']['js_logic'] = [
      '#type' => 'radios',
      '#title' => t('Grouping logic'),
      '#default_value' => $config->get('js_logic'),
      '#options' => [
        0 => t('File count'),
        1 => t('File size'),
      ],
      '#description' => t('If file count is selected then each bundle will try to have a similar number of original files aggregated inside of it. If file size is selected then each bundle will try to have a similar file size.'),
      '#states' => [
        'disabled' => [
          '#edit-active' => ['checked' => FALSE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('advagg_bundler.settings')
      ->set('active', $form_state->getValue('active'))
      ->set('max_css', $form_state->getValue('max_css'))
      ->set('css_logic', $form_state->getValue('css_logic'))
      ->set('max_js', $form_state->getValue('max_js'))
      ->set('js_logic', $form_state->getValue('js_logic'))
      ->save();
    parent::submitForm($form, $form_state);

    // Clear relevant caches.
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    Cache::invalidateTags([
      'library_info',
      'advagg_css',
      'advagg_js',
    ]);
  }

}
