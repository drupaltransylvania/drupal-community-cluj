<?php

namespace Drupal\country\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'country' formatter showing the iso code.
 *
 * @FieldFormatter(
 *   id = "country_iso_code",
 *   module = "country",
 *   label = @Translation("ISO code"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryCodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $item->value);
    }
    return $elements;
  }
}
