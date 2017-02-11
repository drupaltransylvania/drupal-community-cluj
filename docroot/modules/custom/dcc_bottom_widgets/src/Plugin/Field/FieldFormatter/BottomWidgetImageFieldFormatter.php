<?php

namespace Drupal\dcc_bottom_widgets\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

/**
 * Plugin implementation of the 'bottom_widget_image_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "bottom_widget_image_field_formatter",
 *   label = @Translation("Bottom widget image field formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class BottomWidgetImageFieldFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $elements[$delta]['#theme_wrappers'] = ['dcc_bottom_widget_image_wrapper'];
    }

    return $elements;
  }

}
