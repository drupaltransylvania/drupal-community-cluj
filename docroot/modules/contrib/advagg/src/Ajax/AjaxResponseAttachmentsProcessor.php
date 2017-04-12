<?php

namespace Drupal\advagg\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor as CoreAjaxResponseAttachmentsProcessor;
use Drupal\Core\Ajax\AddCssCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 */
class AjaxResponseAttachmentsProcessor extends CoreAjaxResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * {@inheritdoc}
   */
  protected function buildAttachmentsCommands(AjaxResponse $response, Request $request) {
    $ajax_page_state = $request->request->get('ajax_page_state') ?: $request->query->get('ajax_page_state');

    // Aggregate CSS/JS if necessary, but only during normal site operation.
    $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
    $optimize_js = !defined('MAINTENANCE_MODE') && $this->config->get('js.preprocess');

    $attachments = $response->getAttachments();

    // Resolve the attached libraries into asset collections.
    $assets = new AttachedAssets();
    $assets->setLibraries(isset($attachments['library']) ? $attachments['library'] : [])
      ->setAlreadyLoadedLibraries(isset($ajax_page_state['libraries']) ? explode(',', $ajax_page_state['libraries']) : [])
      ->setSettings(isset($attachments['drupalSettings']) ? $attachments['drupalSettings'] : []);
    $css_assets = $this->assetResolver->getCssAssets($assets, $optimize_css);
    list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, $optimize_js);

    // First, AttachedAssets::setLibraries() ensures duplicate libraries are
    // removed: it converts it to a set of libraries if necessary. Second,
    // AssetResolver::getJsSettings() ensures $assets contains the final set of
    // JavaScript settings. AttachmentsResponseProcessorInterface also mandates
    // that the response it processes contains the final attachment values, so
    // update both the 'library' and 'drupalSettings' attachments accordingly.
    $attachments['library'] = $assets->getLibraries();
    $attachments['drupalSettings'] = $assets->getSettings();
    $response->setAttachments($attachments);
    $alter_type = 'ajax';

    // Render the HTML to load these files, and add AJAX commands to insert this
    // HTML in the page. Settings are handled separately, afterwards.
    $settings = [];
    if (isset($js_assets_header['drupalSettings'])) {
      $settings = $js_assets_header['drupalSettings']['data'];
      unset($js_assets_header['drupalSettings']);
    }
    if (isset($js_assets_footer['drupalSettings'])) {
      $settings = $js_assets_footer['drupalSettings']['data'];
      unset($js_assets_footer['drupalSettings']);
    }

    // Prepend commands to add the assets, preserving their relative order.
    $resource_commands = [];
    $prefetch = [];
    if ($css_assets) {
      $css_render_array = $this->cssCollectionRenderer->render($css_assets);
      if (isset($css_render_array['prefetch'])) {
        $prefetch = array_merge($prefetch, $css_render_array['prefetch']);
        unset($css_render_array['prefetch']);
      }

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'css';
      $this->moduleHandler->alter('advagg_asset_render', $css_render_array, $alter_type, $asset_type);
      $resource_commands[] = new AddCssCommand($this->renderer->renderPlain($css_render_array));
    }
    if ($js_assets_header) {
      $js_header_render_array = $this->jsCollectionRenderer->render($js_assets_header);
      if (isset($js_header_render_array['prefetch'])) {
        $prefetch = array_merge($prefetch, $js_header_render_array['prefetch']);
        unset($js_header_render_array['prefetch']);
      }

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'js_header';
      $this->moduleHandler->alter('advagg_asset_render', $js_header_render_array, $alter_type, $asset_type);
      $resource_commands[] = new PrependCommand('head', $this->renderer->renderPlain($js_header_render_array));
    }
    if ($js_assets_footer) {
      $js_footer_render_array = $this->jsCollectionRenderer->render($js_assets_footer);
      if (isset($js_footer_render_array['prefetch'])) {
        $prefetch = array_merge($prefetch, $js_footer_render_array['prefetch']);
        unset($js_footer_render_array['prefetch']);
      }

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'js_footer';
      $this->moduleHandler->alter('advagg_asset_render', $js_footer_render_array, $alter_type, $asset_type);
      $resource_commands[] = new AppendCommand('body', $this->renderer->renderPlain($js_footer_render_array));
    }
    if ($prefetch) {
      $prefetch = array_unique($prefetch, SORT_REGULAR);

      // Allow other modules to alter the assets before rendering.
      // Call hook_advagg_asset_render_alter().
      $asset_type = 'prefetch';
      $this->moduleHandler->alter('advagg_asset_render', $prefetch, $alter_type, $asset_type);
      $resource_commands[] = new PrependCommand('head', $this->renderer->renderPlain($prefetch));
    }
    foreach (array_reverse($resource_commands) as $resource_command) {
      $response->addCommand($resource_command, TRUE);
    }

    // Prepend a command to merge changes and additions to drupalSettings.
    if (!empty($settings)) {
      // During Ajax requests basic path-specific settings are excluded from
      // new drupalSettings values. The original page where this request comes
      // from already has the right values. An Ajax request would update them
      // with values for the Ajax request and incorrectly override the page's
      // values.
      // @see system_js_settings_alter()
      unset($settings['path']);
      $response->addCommand(new SettingsCommand($settings, TRUE), TRUE);
    }

    $commands = $response->getCommands();
    $this->moduleHandler->alter('ajax_render', $commands);

    return $commands;
  }

}
