<?php

namespace Drupal\dcc_gtd_registration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class CaptchaManager.
 *
 * @package Drupal\dcc_gtd_registration\Service
 */
class CaptchaManager {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * CaptchaManager constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The current user service.
   */
  public function __construct(
    AccountProxyInterface $accountProxy
  ) {
    $this->accountProxy = $accountProxy;
    module_load_include('inc', 'captcha');
  }

  /**
   * Creates the captcha form element.
   *
   * @return array
   *   Returns a render array for a form with the captcha element.
   */
  public function createCaptcha() {
    $form = [];
    if (!$this->accountProxy->hasPermission('skip CAPTCHA')) {
      // Build CAPTCHA form element.
      $captcha_element = [
        '#type' => 'captcha',
        '#captcha_type' => 'recaptcha/reCAPTCHA',
      ];

      _captcha_insert_captcha_element($form, [], $captcha_element);
    }

    return $form;
  }

}
