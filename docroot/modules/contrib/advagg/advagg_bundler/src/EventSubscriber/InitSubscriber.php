<?php

namespace Drupal\advagg_bundler\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the AdvAgg setting core_groups to FALSE if bundler is enabled.
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  /**
   * Event Response.
   */
  public function onEvent() {
    if (advagg_bundler_enabled()) {
      $GLOBALS['conf']['core_groups'] = FALSE;
    }
  }

}
