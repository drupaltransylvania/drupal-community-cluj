<?php

/**
 * @file
 * Contains \Drupal\Tests\swiftmailer\Functional\ExampleMailTest.
 */

namespace Drupal\Tests\swiftmailer\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

class ExampleMailTest extends BrowserTestBase {

  public static $modules = ['swiftmailer', 'mailsystem'];

  protected function setUp() {
    parent::setUp();
  }

  public function testForm() {
    $account = $this->createUser(['administer swiftmailer']);
    $this->drupalLogin($account);
    $this->drupalPostForm(Url::fromRoute('swiftmailer.test'), [], 'Send');
    $this->assertSession()->pageTextContains(t('An attempt has been made to send an e-mail to @email.', ['@email' => $account->getEmail()]));
  }

}
