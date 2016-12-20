<?php

namespace Drupal\google_map_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'google_map_field' formatter.
 *
 * @FieldFormatter(
 *   id = "google_map_field_default",
 *   label = @Translation("Google Map Field default"),
 *   field_types = {
 *     "google_map_field"
 *   }
 * )
 */
class GoogleMapFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $element = array(
        '#theme' => 'google_map_field',
        '#name' => $item->name,
        '#lat' => $item->lat,
        '#lon' => $item->lon,
        '#zoom' => $item->zoom,
      );
      $element['#attached']['library'][] = 'google_map_field/google-map-field-renderer';
      $element['#attached']['library'][] = 'google_map_field/google-map-apis';
      $elements[$delta] = $element;
    }

    return $elements;
  }

}
