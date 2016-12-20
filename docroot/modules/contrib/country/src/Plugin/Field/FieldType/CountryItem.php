<?php

/**
 * @file
 * Contains \Drupal\country\Plugin\Field\FieldType\CountryItem.
 */

namespace Drupal\country\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'country' field type.
 *
 * @FieldType(
 *   id = "country",
 *   label = @Translation("Country"),
 *   description = @Translation("Stores the ISO-2 name of a country."),
 *   default_widget = "country_default",
 *   default_formatter = "country_default"
 * )
 */

class CountryItem extends FieldItemBase {

  const COUNTRY_ISO_MAXLENGTH = 2;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Country'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'char',
          'length' => static::COUNTRY_ISO_MAXLENGTH,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $constraints[] = $constraint_manager->create('ComplexData', array(
      'value' => array(
        'Length' => array(
          'max' => static::COUNTRY_ISO_MAXLENGTH,
          'maxMessage' => t('%name: the country iso-2 code may not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@max' => static::COUNTRY_ISO_MAXLENGTH)),
        )
      ),
    ));

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'selectable_countries' => array(),
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
        'selectable_countries' => array(),
      ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $settings = $this->getSettings();
    // Add selectable_countries element.
    static::defaultCountriesForm($element, $settings);
    $element['selectable_countries']['#description'] = t("If no countries are selected, all of them will be available for this field and will override the field's default selectable countries.");

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();
    // We need the field-level 'selectable_countries' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();
    static::defaultCountriesForm($element, $settings);
    $element['selectable_countries']['#description'] = t('If no countries are selected, all of them will be available for this field.');

    return $element;
  }

  /**
   * Builds the selectable_countries element.
   *
   * @param array $element
   *   The form associative array passed by reference.
   * @param array $settings
   *   The field settings array.
   */
  protected function defaultCountriesForm(array &$element, array $settings) {
    $element['selectable_countries'] = array(
      '#type' => 'select',
      '#title' => t('Selectable countries'),
      '#default_value' => $settings['selectable_countries'],
      '#options' => \Drupal::service('country_manager')->getList(),
      '#description' => t('Select all countries you want to make available for this field.'),
      '#multiple' => TRUE,
      '#size' => 10,
    );
  }
}
