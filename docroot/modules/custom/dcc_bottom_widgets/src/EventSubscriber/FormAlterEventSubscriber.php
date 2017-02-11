<?php

namespace Drupal\dcc_bottom_widgets\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_form_alter\EventSubscriber\FormAlterEventSubscriberBase;

/**
 * Class FormAlterEventSubscriber.
 *
 * @package Drupal\dcc_bottom_widgets\EventSubscriber
 */
class FormAlterEventSubscriber extends FormAlterEventSubscriberBase {

  /**
   * The config ids of the bottom widget blocks.
   *
   * @var array
   */
  private $blockConfigNames = [
    'bottomwidgetsfirstblock',
    'bottomwidgetsfourthblock',
    'bottomwidgetssecondblock',
    'bottomwidgetsthirdblock',
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterForm(array $form) {
    if ($this->formId == 'entity_subqueue_homepage_bottom_widgets_edit_form') {
      $form['actions']['submit']['#submit'][] = [$this, 'submit'];
    }

    return $form;
  }

  /**
   * Submit handler for the altered form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function submit(array $form, FormStateInterface $formState) {
    Cache::invalidateTags($this->getCacheTags());
  }

  /**
   * Builds the cache tag name corresponsding to the bottom widget blocks.
   *
   * @param string $configName
   *   The configuration name of the block.
   *
   * @return string
   *   The cache tag.
   */
  private function buildTagName($configName) {
    return 'config:block.block.' . $configName;
  }

  /**
   * Builds an array of cache tags to be invalidated.
   *
   * @return array|\string[]
   *   The cache tags.
   */
  private function getCacheTags() {
    $tags = [];

    foreach ($this->blockConfigNames as $configName) {
      $blockCacheTag = [
        $this->buildTagName($configName),
      ];
      $tags = Cache::mergeTags($tags, $blockCacheTag);
    }

    return $tags;
  }

}
