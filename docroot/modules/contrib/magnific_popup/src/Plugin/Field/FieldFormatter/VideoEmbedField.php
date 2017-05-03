<?php

/**
 * @file
 * Contains \Drupal\magnific_popup\Plugin\Field\FieldFormatter\VideoEmbedField.
 */

namespace Drupal\magnific_popup\Plugin\Field\FieldFormatter;

use Drupal\video_embed_field\Plugin\Field\FieldFormatter\Colorbox;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Magnific Popup FieldFormatter for Video Embed Field.
 *
 * @FieldFormatter(
 *   id = "video_embed_field_magnific_popup",
 *   label = @Translation("Magnific Popup"),
 *   field_types = {
 *     "video_embed_field"
 *   }
 * )
 */
class VideoEmbedField extends Colorbox {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = [
      'gallery_type' => 'all_items',
    ];

    return $default_settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = [
      'gallery_type' => [
        '#title' => t('Gallery Type'),
        '#type' => 'select',
        '#default_value' => $this->getSetting('gallery_type'),
        '#options' => $this->getGalleryTypes(),
      ],
    ];

    return $form + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Thumbnail that opens a popup.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $gallery_type = $this->getSetting('gallery_type');
    $elements['#attributes']['class'][] = 'mfp-field';
    $elements['#attributes']['class'][] = 'mfp-video-embed-' . Html::cleanCssIdentifier($gallery_type);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $gallery_type = $this->getSetting('gallery_type');
    $thumbnails = $this->thumbnailFormatter->viewElements($items, $langcode);
    $videos = $this->videoFormatter->viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      if ($gallery_type === 'first_item' && $delta > 0) {
        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => [
            'data-mfp-video-embed' => (string) $this->renderer->renderRoot($videos[$delta]),
            'class' => ['mfp-video-embed-popup'],
          ],
          '#attached' => [
            'library' => ['magnific_popup/magnific_popup', 'magnific_popup/video_embed_field'],
          ],
        ];
      }
      else {
        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => [
            'data-mfp-video-embed' => (string) $this->renderer->renderRoot($videos[$delta]),
            'class' => ['mfp-video-embed-popup'],
          ],
          '#attached' => [
            'library' => ['magnific_popup/magnific_popup', 'magnific_popup/video_embed_field'],
          ],
          'children' => $thumbnails[$delta],
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::moduleHandler()->moduleExists('video_embed_field');
  }

  /**
   * Get an array of gallery types.
   *
   * @return array
   *   An array of gallery types for use in display settings.
   */
  protected function getGalleryTypes() {
    return [
      'all_items' => t('Gallery: All Items Displayed'),
      'first_item' => t('Gallery: First Item Displayed'),
      'separate_items' => t('No Gallery: Display Each Item Separately'),
    ];
  }

}
