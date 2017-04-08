<?php

namespace Drupal\dcc_gtd_registration\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;

/**
 * Plugin implementation of the 'dcc_registration_date_range_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "dcc_registration_date_range_formatter",
 *   label = @Translation("Dcc registration date range formatter"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DccRegistrationDateRangeFormatter extends DateRangeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $elements[$delta]['#theme_wrappers'] = [DATE_RANGE_WRAPPER];
    }

    return $elements;
  }

}
