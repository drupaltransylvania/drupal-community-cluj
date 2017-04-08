<?php

/**
 * @file
 * Contains \Drupal\country\Controller\CountryAutocompleteController.
 */

namespace Drupal\country\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal;


/**
 * Returns autocomplete responses for countries.
 */
class CountryAutocompleteController {

  /**
   * Returns response for the country name autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   * @param string $entity_type
   *   The type of entity that owns the field.
   * @param string $bundle
   *   The name of the bundle that owns the field.
   * @param $field_name
   *   The name of the field.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for countries.
   */
  public function autocomplete(Request $request, $entity_type, $bundle, $field_name) {
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      // Get field config
      $field_definition = FieldConfig::loadByName($entity_type, $bundle, $field_name);
      $countries = \Drupal::service('country.field.manager')->getSelectableCountries($field_definition);
      foreach ($countries as $iso2 => $country) {
        if (strpos(Unicode::strtolower($country), Unicode::strtolower($string)) !== FALSE) {
          $matches[] = array('value' => $country, 'label' => $country);
        }
      }
    }
    return new JsonResponse($matches);
  }
}
