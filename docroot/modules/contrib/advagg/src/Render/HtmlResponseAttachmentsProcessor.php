<?php

namespace Drupal\advagg\Render;

use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor as CoreHtmlResponseAttachmentsProcessor;

/**
 * Processes attachments of HTML responses.
 *
 * This class is used by the rendering service to process the #attached part of
 * the render array, for HTML responses.
 *
 * To render attachments to HTML for testing without a controller, use the
 * 'bare_html_page_renderer' service to generate a
 * Drupal\Core\Render\HtmlResponse object. Then use its getContent(),
 * getStatusCode(), and/or the headers property to access the result.
 *
 * @see template_preprocess_html()
 * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface
 * @see \Drupal\Core\Render\BareHtmlPageRenderer
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
class HtmlResponseAttachmentsProcessor extends CoreHtmlResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * {@inheritdoc}
   */
  protected function processAssetLibraries(AttachedAssetsInterface $assets, array $placeholders) {
    $variables = ['prefetch' => []];
    $render_type = 'html';

    // Print styles - if present.
    if (isset($placeholders['styles'])) {
      // Optimize CSS if necessary, but only during normal site operation.
      $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
      $variables['styles'] = $this->cssCollectionRenderer->render($this->assetResolver->getCssAssets($assets, $optimize_css));
      $variables['prefetch'] = isset($variables['styles']['prefetch']) ? $variables['styles']['prefetch'] : [];
      unset($variables['styles']['prefetch']);

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'styles';
      $this->moduleHandler->alter('advagg_asset_render', $variables['styles'], $render_type, $asset_type);
    }

    // Print scripts - if any are present.
    if (isset($placeholders['scripts']) || isset($placeholders['scripts_bottom'])) {
      // Optimize JS if necessary, but only during normal site operation.
      $optimize_js = !defined('MAINTENANCE_MODE') && !\Drupal::state()->get('system.maintenance_mode') && $this->config->get('js.preprocess');
      list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, $optimize_js);
      $variables['scripts'] = $this->jsCollectionRenderer->render($js_assets_header);
      $variables['prefetch'] += isset($variables['scripts']['prefetch']) ? $variables['scripts']['prefetch'] : [];
      unset($variables['scripts']['prefetch']);

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'scripts';
      $this->moduleHandler->alter('advagg_asset_render', $variables['scripts'], $render_type, $asset_type);

      $variables['scripts_bottom'] = $this->jsCollectionRenderer->render($js_assets_footer);
      $variables['prefetch'] += isset($variables['scripts_bottom']['prefetch']) ? $variables['scripts_bottom']['prefetch'] : [];
      unset($variables['scripts_bottom']['prefetch']);

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'scripts_bottom';
      $this->moduleHandler->alter('advagg_asset_render', $variables['scripts_bottom'], $render_type, $asset_type);
    }

    // Merge prefetch and styles or scripts.
    $prefetch = array_unique($variables['prefetch'], SORT_REGULAR);
    // Allow other modules to alter the assets before rendering.
    // Call hook_advagg_asset_render_alter().
    $asset_type = 'prefetch';
    $this->moduleHandler->alter('advagg_asset_render', $prefetch, $render_type, $asset_type);
    if (isset($placeholders['styles'])) {
      $variables['styles'] = array_merge($prefetch, $variables['styles']);
    }
    else {
      $variables['scripts'] = array_merge($prefetch, $variables['scripts']);
    }
    unset($variables['prefetch']);
    return $variables;
  }

}
