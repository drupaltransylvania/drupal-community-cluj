<?php

namespace Drupal\country\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by country ISO2.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("country_item")
 */
class CountryItem extends InOperator {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   Array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   EntityManager that is stored internally and used to load nodes.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Allowed country items');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['country_target_bundle'] = ['default' => 'global'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $this->getAvailableBundleInfo();
    $form['country_target_bundle'] = [
      '#type' => 'radios',
      '#title' => $this->t('Target entity bundle to filter by'),
      '#options' => $options,
      '#default_value' => $this->options['country_target_bundle'],
      '#weight' => -1,
    ];

    return $form;
  }

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Gets the field storage of the used field.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected function getFieldStorageDefinition() {
    $definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->definition['entity_type']);

    $definition = NULL;
    // @todo Unify 'entity field'/'field_name' instead of converting back and
    //   forth. https://www.drupal.org/node/2410779
    if (isset($this->definition['field_name'])) {
      $definition = $definitions[$this->definition['field_name']];
    }
    elseif (isset($this->definition['entity field'])) {
      $definition = $definitions[$this->definition['entity field']];
    }
    return $definition;
  }

  /**
   * Gets the field of the used field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected function getFieldDefinition() {
    $definitions = $this->entityFieldManager->getFieldDefinitions($this->definition['entity_type'], $this->options['country_target_bundle']);

    $definition = NULL;
    // @todo Unify 'entity field'/'field_name' instead of converting back and
    //   forth. https://www.drupal.org/node/2410779
    if (isset($this->definition['field_name'])) {
      $definition = $definitions[$this->definition['field_name']];
    }
    elseif (isset($this->definition['entity field'])) {
      $definition = $definitions[$this->definition['entity field']];
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!is_null($this->valueOptions)) {
      return $this->valueOptions;
    }

    $countries = $this->options['country_target_bundle'] == 'global'
      ? \Drupal::service('country_manager')->getList()
      : \Drupal::service('country.field.manager')->getSelectableCountries($this->getFieldDefinition());
    $this->valueOptions = $countries;

    return $this->valueOptions;
  }

  /**
   * Get all available bundles which used country entity field.
   */
  protected function getAvailableBundleInfo() {
    $bundles = $this->getFieldStorageDefinition()->getBundles();
    $options = ['global' => $this->t('Global')];
    if ($bundles) {
      $entityBundles = $this->entityTypeBundleInfo->getBundleInfo($this->definition['entity_type']);
      foreach ($bundles as $bundle_id) {
        $options[$bundle_id] = $entityBundles[$bundle_id]['label'];
      }
    }

    return $options;
  }
}
