<?php

/**
 * @file
 * Contains \Drupal\magnific_popup\Plugin\Field\FieldFormatter\MagnificPopup.
 */

namespace Drupal\magnific_popup\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

/**
 * Magnific Popup field formatter.
 *
 * @FieldFormatter(
 *   id = "magnific_popup",
 *   label = @Translation("Magnific Popup"),
 *   field_types = {
 *    "image"
 *   }
 * )
 */
class MagnificPopup extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thumbnail_image_style' => '',
      'popup_image_style' => '',
      'gallery_type' => 'all_items',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $image_styles = image_style_options(FALSE);

    $form['thumbnail_image_style'] = [
      '#title' => t('Thumbnail Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('thumbnail_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];

    $form['popup_image_style'] = [
      '#title' => t('Popup Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('popup_image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];

    $form['gallery_type'] = [
      '#title' => t('Gallery Type'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('gallery_type'),
      '#options' => $this->getGalleryTypes(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $image_styles = image_style_options(FALSE);
    $thumb_image_style = $this->getSetting('thumbnail_image_style');
    $popup_image_style = $this->getSetting('popup_image_style');
    // Check image styles exist or display 'Original Image'.
    $summary[] = t('Thumbnail image style: @thumb_style. Popup image style: @popup_style', [
      '@thumb_style' => isset($image_styles[$thumb_image_style]) ? $thumb_image_style : 'Original Image',
      '@popup_style' => isset($image_styles[$popup_image_style]) ? $popup_image_style : 'Original Image',
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $thumb_image_style = $this->getSetting('thumbnail_image_style');
    $popup_image_style = $this->getSetting('popup_image_style');
    $gallery_type = $this->getSetting('gallery_type');
    $files = $this->getEntitiesToView($items, $langcode);

    foreach ($files as $delta => $file) {
      $image_uri = $file->getFileUri();
      $popup_image_path = !empty($popup_image_style) ? ImageStyle::load($popup_image_style)->buildUrl($image_uri) : $image_uri;
      // Depending on the outcome of https://www.drupal.org/node/2622586,
      // Either a class will need to be added to the $url object,
      // Or a custom theme function might be needed to do so.
      // For the time being, 'a' is used as the delegate in magnific-popup.js.
      $url = Url::fromUri(file_create_url($popup_image_path));
      $item = $file->_referringItem;
      $item_attributes = $file->_attributes;
      unset($file->_attributes);

      $item_attributes['class'][] = 'mfp-thumbnail';

      if ($gallery_type === 'first_item' && $delta > 0) {
        $elements[$delta] = [
          '#theme' => 'image_formatter',
          '#url' => $url,
          '#attached' => [
            'library' => [
              'magnific_popup/magnific_popup',
            ],
          ],
        ];
      }
      else {
        $elements[$delta] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $thumb_image_style,
          '#url' => $url,
          '#attached' => [
            'library' => [
              'magnific_popup/magnific_popup',
            ],
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $gallery_type = $this->getSetting('gallery_type');
    $elements['#attributes']['class'][] = 'mfp-field';
    $elements['#attributes']['class'][] = 'mfp-' . Html::cleanCssIdentifier($gallery_type);
    return $elements;
  }

  /**
   * Get an array of gallery types.
   *
   * @return array
   *   An array of gallery types for use in display settings.
   */
  protected function getGalleryTypes() {
    // Render cache means 'random image' is only random the first time.
    // Disabled until a better solution is found.
    return [
      'all_items' => t('Gallery: All Items Displayed'),
      'first_item' => t('Gallery: First Item Displayed'),
      // 'random_item' => t('Gallery: Random Item Displayed'),
      'separate_items' => t('No Gallery: Display Each Item Separately'),
    ];
  }
}
