<?php

namespace Drupal\dcc_gtd_registration\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'sponsored_by_image_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "sponsored_by_image_field_formatter",
 *   label = @Translation("Sponsored by image field formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SponsoredByImageFieldFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['image_link']['#options']['field_website_link'] = $this->t('Website Link');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $uri = NULL;
    $imageLinkSetting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($imageLinkSetting == 'field_website_link') {
      $entity = $items->getEntity();
      $uri = $entity->get($imageLinkSetting)->uri;
    }

    foreach ($items as $delta => $item) {
      $elements[$delta]['#theme_wrappers'] = [
        SPONSORED_BY => [
          '#link' => $uri,
        ],
      ];
    }

    return $elements;
  }

}
