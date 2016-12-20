<?php

namespace Drupal\google_map_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Google Map' field type.
 *
 * @FieldType(
 *   id = "google_map_field",
 *   label = @Translation("Google Map field"),
 *   description = @Translation("This field stores Google Map fields in the database."),
 *   default_widget = "google_map_field_default",
 *   default_formatter = "google_map_field_default"
 * )
 */
class GoogleMapFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return array(
      'columns' => array(
        'name' => array(
          'type' => 'varchar',
          'length' => 128,
          'not null' => FALSE,
        ),
        'lat' => array(
          'type' => 'float',
          'size' => 'big',
          'default' => 0.0,
          'not null' => FALSE,
        ),
        'lon' => array(
          'type' => 'float',
          'size' => 'big',
          'default' => 0.0,
          'not null' => FALSE,
        ),
        'zoom' => array(
          'type' => 'int',
          'length' => 10,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('lat')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Name'));

    $properties['lat'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Latitude'));

    $properties['lon'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Longitude'));

    $properties['zoom'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Map Zoom'));

    return $properties;
  }

}
