<?php

/**
 * @file
 * Definition of Drupal\country\Plugin\Field\FieldWidget\CountryAutocompleteWidget.
 */

namespace Drupal\country\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal;

/**
 * Plugin implementation of the 'country_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "country_autocomplete",
 *   label = @Translation("Country autocomplete"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => '60',
      'autocomplete_route_name' => 'country.autocomplete',
      'placeholder' => t('Start typing a country name ...'),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $countries = \Drupal::service('country.field.manager')->getSelectableCountries($this->fieldDefinition);
    $element['value'] = $element + array(
      '#type' => 'textfield',
      '#default_value' =>  (isset($items[$delta]->value) && isset($countries[$items[$delta]->value])) ? $countries[$items[$delta]->value] : '',
      '#autocomplete_route_name' => $this->getSetting('autocomplete_route_name'),
      '#autocomplete_route_parameters' => array(
        'entity_type' => $this->fieldDefinition->get('entity_type'),
        'bundle' => $this->fieldDefinition->get('bundle'),
        'field_name' => $this->fieldDefinition->get('field_name'),
      ),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => 255,
      '#selectable_countries' => $countries,
      '#element_validate' => array(array($this, 'validateElement')),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['size'] = array(
      '#type' => 'number',
      '#title' => t('Size'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 20,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Size: @size', array('@size' => $this->getSetting('size')));
    $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $this->getSetting('placeholder')));
    return $summary;
  }

  /**
   * Form element validate handler for country autocomplete.
   */
  public static function validateElement($element, FormStateInterface $form_state) {
    if ($country = $element['#value']) {
      $countries = $element['#selectable_countries'];
      $iso2 = array_search($country, $countries);
      if (!empty($iso2)) {
        $form_state->setValueForElement($element, $iso2);
      }
      else {
        $form_state->setError($element, t('An unexpected country has been entered.'));
      }
    }
  }
}
