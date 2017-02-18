<?php

namespace Drupal\dcc_gtd_registration\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'google_map_field_name_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "google_map_field_name_formatter",
 *   label = @Translation("Google map field name formatter"),
 *   field_types = {
 *     "google_map_field"
 *   }
 * )
 */
class GoogleMapFieldNameFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $element = array(
        '#theme' => GOOGLE_MAP_FIELD_NAME,
        '#name' => $item->name,
      );
      $elements[$delta] = $element;
    }

    return $elements;
  }

}
