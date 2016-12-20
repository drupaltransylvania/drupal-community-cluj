<?php

/**
 * @file
 * Definition of Drupal\country\Plugin\Field\FieldFormatter\CountryDefaultFormatter.
 */

namespace Drupal\country\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal;


/**
 * Plugin implementation of the 'country' formatter.
 *
 * @FieldFormatter(
 *   id = "country_default",
 *   module = "country",
 *   label = @Translation("Country"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $countries = \Drupal::service('country.field.manager')->getSelectableCountries($this->fieldDefinition);
    foreach ($items as $delta => $item) {
      if (isset($countries[$item->value])) {
        $elements[$delta] = array('#markup' => $countries[$item->value]);
      }
    }
    return $elements;
  }
}
