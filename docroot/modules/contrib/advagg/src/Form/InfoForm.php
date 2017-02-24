<?php

namespace Drupal\advagg\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * View AdvAgg information for this site.
 */
class InfoForm extends ConfigFormBase {
  /**
   * The theme registry service.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * The AdvAgg file status state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;

  /**
   * The AdvAgg aggregates state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggAggregates;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\Translator\TranslatorInterface
   */
  protected $translation;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry service.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   * @param \Drupal\Core\State\StateInterface $advagg_aggregates
   *   The AdvAgg aggregate state information storage service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The Date formatter service.
   * @param \Drupal\Core\StringTranslation\Translator\TranslatorInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Registry $theme_registry, StateInterface $advagg_files, StateInterface $advagg_aggregates, RequestStack $request_stack, DateFormatterInterface $date_formatter, TranslatorInterface $string_translation) {
    parent::__construct($config_factory);

    $this->themeRegistry = $theme_registry;
    $this->advaggFiles = $advagg_files;
    $this->advaggAggregates = $advagg_aggregates;
    $this->requestStack = $request_stack;
    $this->dateFormatter = $date_formatter;
    $this->translation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme.registry'),
      $container->get('state.advagg.files'),
      $container->get('state.advagg.aggregates'),
      $container->get('request_stack'),
      $container->get('date.formatter'),
      $container->get('string_translation')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_info';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['tip'] = [
      '#markup' => '<p>' . t('This page provides debugging information. There are no configuration options here.') . '</p>',
    ];

    // Get all hooks and variables.
    $core_hooks = $this->themeRegistry->get();
    $advagg_hooks = advagg_hooks_implemented();

    // Output html preprocess functions hooks.
    $form['theme_info'] = [
      '#type' => 'details',
      '#title' => t('Hook Theme Info'),
    ];
    $data = implode("\n", $core_hooks['html']['preprocess functions']);
    $form['theme_info']['advagg_theme_info'] = [
      '#markup' => '<p>preprocess functions on html.</p><pre>' . $data . '</pre>',
    ];

    $file_data = $this->advaggFiles->getAll();

    // Get all parent css and js files.
    $types = ['css', 'js'];
    foreach ($types as $type) {
      $form[$type] = [
        '#type' => 'details',
        '#title' => t('@type files', ['@type' => Unicode::strtoupper($type)]),
      ];
    }
    foreach ($file_data as $name => $info) {
      if (!in_array($info['fileext'], $types)) {
        continue;
      }
      $form[$info['fileext']][$info['filename_hash']] = [
        '#markup' => '<details><summary>' . $this->translation->formatPlural($info['changes'], 'changed 1 time - %file<br />', 'changed %changes times - %file<br />', [
          '%changes' => $info['changes'],
          '%file' => $name,
        ]) . '</summary><div class="details-wrapper"><pre>' . print_r($info, TRUE) . '</pre></div></details>',
      ];
    }

    // Display as module -> hook instead of hook -> module.
    ksort($advagg_hooks);
    $module_hooks = [];
    foreach ($advagg_hooks as $hook => $values) {
      if (!empty($values)) {
        foreach ($values as $module_name) {
          if (!isset($module_hooks[$module_name])) {
            $module_hooks[$module_name] = [];
          }
          $module_hooks[$module_name][] = $hook;
        }
      }
      else {
        $module_hooks['not in use'][] = $hook;
      }
    }
    ksort($module_hooks);

    $form['modules_implementing_advagg'] = [
      '#type' => 'details',
      '#title' => t('Modules implementing aggregate hooks'),
    ];
    $form['hooks_implemented'] = [
      '#type' => 'details',
      '#title' => t('AdvAgg CSS/JS hooks implemented by modules'),
    ];

    // Output all advagg hooks implemented.
    foreach ($module_hooks as $hook => $values) {
      if (empty($values)) {
        $form['modules_implementing_advagg'][$hook] = [
          '#markup' => '<div><strong>' . $hook . ':</strong> 0</div>',
        ];
      }
      else {
        $form['modules_implementing_advagg'][$hook] = [
          '#markup' => '<div><strong>' . $hook . ':</strong> ' . count($values) . $this->formatList($values) . '</div>',
        ];
      }
    }

    // Output all advagg hooks implemented.
    foreach ($advagg_hooks as $hook => $values) {
      if (empty($values)) {
        $form['hooks_implemented'][$hook] = [
          '#markup' => '<div><strong>' . $hook . ':</strong> 0</div>',
        ];
      }
      else {
        $form['hooks_implemented'][$hook] = [
          '#markup' => '<div><strong>' . $hook . ':</strong> ' . count($values) . $this->formatList($values) . '</div>',
        ];
      }
    }

    // Output what is used inside of advagg_get_current_hooks_hash().
    $form['hooks_variables_hash'] = [
      '#type' => 'details',
      '#title' => t('Hooks And Variables Used In Hash'),
    ];
    $form['hooks_variables_hash']['description'] = [
      '#markup' => t('Current Value: %value. Below is the listing of variables and hooks used to generate the 3rd hash of an aggregates filename.', ['%value' => advagg_get_current_hooks_hash()]),
    ];
    $form['hooks_variables_hash']['output'] = [
      // @ignore production_php
      '#markup' => '<pre>' . print_r(advagg_current_hooks_hash_array(), TRUE) . '</pre>',
    ];
    // Get info about a file.
    $form['get_info_about_agg'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Get detailed info about an aggregate file'),
    ];
    $form['get_info_about_agg']['filename'] = [
      '#type' => 'textfield',
      '#size' => 170,
      '#maxlength' => 256,
      '#default_value' => '',
      '#title' => t('Filename'),
    ];
    $form['get_info_about_agg']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Lookup Details'),
      '#submit' => ['::getFileInfoSubmit'],
      '#validate' => ['::getFileInfoValidate'],
      '#ajax' => [
        'callback' => '::getFileInfoAjax',
        'wrapper' => 'advagg-file-info-ajax',
        'effect' => 'fade',
      ],
    ];
    $form['get_info_about_agg']['tip'] = [
      '#markup' => '<p>' . t('Takes input like "@css_file" or a full aggregate name like "@advagg_js"', [
        '@css_file' => $this->advaggFiles->getRandomKey(),
        '@advagg_js' => $this->advaggAggregates->getRandom()['uid'],
      ]) . '</p>',
    ];
    $form['get_info_about_agg']['wrapper'] = [
      '#prefix' => "<div id='advagg-file-info-ajax'>",
      '#suffix' => "</div>",
    ];
    $form = parent::buildForm($form, $form_state);
    unset($form['actions']);
    return $form;
  }

  /**
   * Format an indented list from array.
   *
   * @param array $list
   *   The array to convert to a string.
   * @param int $depth
   *   (optional) Depth multiplier for indentation.
   *
   * @return string
   *   The imploded and spaced array.
   */
  private function formatList(array $list, $depth = 1) {
    $spacer = '<br />' . str_repeat('&nbsp;', 2 * $depth);
    $output = $spacer . Xss::filter(implode($spacer, $list), ['br']);
    return $output;
  }

  /**
   * Display file info in a drupal message.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function getFileInfoSubmit(array &$form, FormStateInterface $form_state) {
    $info = $this->getFileInfo($form_state->getValue('filename'));
    $output = '<pre>' . print_r($info, TRUE) . '</pre>';
    if (!$this->isAjax()) {
      drupal_set_message($output);
    }
  }

  /**
   * Display file info via ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function getFileInfoAjax(array &$form, FormStateInterface $form_state) {
    $element = $form['get_info_about_agg']['wrapper'];
    if ($form_state->hasAnyErrors()) {
      return $element;
    }
    $info = $this->getFileInfo($form_state->getValue('filename'));
    if (empty($info)) {
      $form_state->setErrorByName('filename', t('Please input a valid aggregate filename.'));
      return $element;
    }
    else {
      $element['#markup'] = '<pre>' . print_r($info, TRUE) . '</pre>';
      return $element;
    }
  }

  /**
   * Verify that the filename is correct.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function getFileInfoValidate(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('filename'))) {
      $form_state->setErrorByName('filename', t('Please input an aggregate filename.'));
    }
  }

  /**
   * Get detailed info about the given filename.
   *
   * @param string $filename
   *   Name of file to lookup.
   *
   * @return array
   *   Returns an array of detailed info about this file.
   */
  private function getFileInfo($filename) {
    // Strip quotes and trim.
    $filename = trim(str_replace(['"', "'"], '', $filename));
    if (substr_compare($filename, 'css_', 0) > 0 || substr_compare($filename, 'js_', 0) > 0) {
      $results = array_column($this->advaggAggregates->getAll(), NULL, 'uid');
      if (isset($results[$filename])) {
        return $results[$filename];
      }
      else {
        return "Aggregate name unrecognized, confirm spelling, otherwise likely a very old aggregate that has been expunged.";
      }
    }
    elseif ($data = $this->advaggFiles->get($filename)) {
      $data['File modification date'] = $this->dateFormatter->format($data['mtime'], 'html_datetime');
      $data['Information last update'] = $this->dateFormatter->format($data['updated'], 'html_datetime');
      return $data;
    }
    else {
      return "File not found and AdvAgg has no record of it. Confirm spelling of the path.";
    }
  }

  /**
   * Checks if the form was submitted by AJAX.
   *
   * @return bool
   *   TRUE if the form was submitted via AJAX, otherwise FALSE.
   */
  private function isAjax() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->query->has(FormBuilderInterface::AJAX_FORM_REQUEST)) {
      return TRUE;
    }
    return FALSE;
  }

}
