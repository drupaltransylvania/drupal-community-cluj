<?php

namespace Drupal\advagg_mod\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Perform initialization tasks for advagg_mod.
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * A config object for the advagg_mod configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * An editable config object for the advagg configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $advaggConfig;

  /**
   * Constructs the Subscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('advagg.settings');
    $this->advaggConfig = $config_factory->getEditable('advagg.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  /**
   * Synchronize global_counter variable between sites.
   *
   * Only if using unified_multisite_dir.
   */
  public function onEvent() {
    $dir = rtrim($this->config->get('unified_multisite_dir'), '/');
    if (empty($dir) || !file_exists($dir) || !is_dir($dir)) {
      return;
    }

    $counter_filename = $dir . '/_global_counter';
    $local_counter = advagg_get_global_counter();
    if (!file_exists($counter_filename)) {
      file_unmanaged_save_data($local_counter, $counter_filename, FILE_EXISTS_REPLACE);
    }
    else {
      $shared_counter = (int) file_get_contents($counter_filename);

      if ($shared_counter == $local_counter) {
        // Counters are the same, return.
        return;
      }
      elseif ($shared_counter < $local_counter) {
        // Local counter is higher, update saved file and return.
        ile_unmanaged_save_data($local_counter, $counter_filename, FILE_EXISTS_REPLACE);
        return;
      }
      elseif ($shared_counter > $local_counter) {
        // Shared counter is higher, update local copy and return.
        $this->advaggConfig->set('global_counter', $shared_counter)->save();
        return;
      }
    }
  }

}
