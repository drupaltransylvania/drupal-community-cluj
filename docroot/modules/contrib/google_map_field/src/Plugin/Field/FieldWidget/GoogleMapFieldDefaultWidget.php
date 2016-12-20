<?php

namespace Drupal\google_map_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'google_map_field_default' widget.
 *
 * @FieldWidget(
 *   id = "google_map_field_default",
 *   label = @Translation("Google Map Field default"),
 *   field_types = {
 *     "google_map_field"
 *   }
 * )
 */
class GoogleMapFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element += array(
      '#type' => 'fieldset',
      '#title' => $this->t('Map'),
    );
    $element['#attached']['library'][] = 'google_map_field/google-map-field-widget-renderer';
    $element['#attached']['library'][] = 'google_map_field/google-map-apis';

    $element['intro'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('<strong>Use the "Set Map Marker" button below to drop a marker on a map...</strong>'),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    );

    $element['name'] = array(
      '#title' => $this->t('Map Name'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->name) ? $items[$delta]->name : NULL,
      '#prefix' => '<div class="google-map-field-widget left">',
      '#attributes' => array(
        'data-name-delta' => $delta,
      ),
    );

    $element['lat'] = array(
      '#title' => $this->t('Latitude'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->lat) ? $items[$delta]->lat : NULL,
      '#attributes' => array(
        'data-lat-delta' => $delta,
        'class' => array(
          'google-map-field-watch-change',
        ),
      ),
    );

    $element['lon'] = array(
      '#title' => $this->t('Longitude'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->lon) ? $items[$delta]->lon : NULL,
      '#attributes' => array(
        'data-lon-delta' => $delta,
        'class' => array(
          'google-map-field-watch-change',
        ),
      ),
    );

    $element['zoom'] = array(
      '#title' => $this->t('Map Zoom'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->zoom) ? $items[$delta]->zoom : NULL,
      '#attributes' => array(
        'data-zoom-delta' => $delta,
        'class' => array(
          'google-map-field-watch-change',
        ),
      ),
    );

    $element['open_map'] = array(
      '#type' => 'button',
      '#value' => $this->t('Set Map Marker'),
      '#attributes' => array(
        'data-delta' => $delta,
        'id' => 'map_setter_' . $delta,
      ),
    );

    $element['clear_fields'] = array(
      '#type' => 'button',
      '#value' => $this->t('Clear Fields'),
      '#attributes' => array(
        'data-delta' => $delta,
        'id' => 'clear_fields_' . $delta,
        'class' => array(
          'google-map-field-clear',
        ),
      ),
      '#suffix' => '</div>',
    );

    $element['preview'] = array(
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => '<div class="google-map-field-preview" data-delta="' . $delta . '"></div>',
      '#prefix' => '<div class="google-map-field-widget right">',
      '#suffix' => '</div>',
    );

    return $element;
  }

}
