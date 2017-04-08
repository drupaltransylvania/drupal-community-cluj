<?php

namespace Drupal\dcc_gtd_registration\Plugin\Step;

use Drupal\captcha\Entity\CaptchaPoint;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\dcc_gtd_registration\Service\CaptchaManager;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Training Details Step.
 *
 * @Step(
 *   id = "training_details_step",
 *   name = @Translation("Training Details Step"),
 *   form_id= "dcc_gtd_registration",
 *   step_number = 4,
 * )
 */
class TrainingDetailsStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setCurrentValues(FormStateInterface $formState) {
    $formState->set("attend_day", $formState->getValue("attend_day"));
    $formState->set("language", $formState->getValue("language"));
    $formState->set("key_expectations", $formState->getValue("key_expectations"));
  }

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $fields['title'] = array(
      '#title' => $this->t("Training details"),
      '#type' => 'item',
    );
    $attend_day = $form_state->get("attend_day");
    $fields['attend_day'] = array(
      '#title' => $this->t('On which days will you attend the training ?'),
      '#description' => 'See the schedule!',
      '#type' => 'checkboxes',
      '#options' => array(
        'friday' => $this->t('Friday'),
        'saturday' => $this->t('Saturday'),
      ),
      '#required' => TRUE,
      '#default_value' => isset($attend_day) ? $form_state->get("attend_day") : NULL,
    );
    $fields['laptop'] = array(
      '#title' => $this->t('For Saturday, please bring your laptop. You do not need to set up a development environment on it, a web browser and ability to connect to a Wifi will be enough.'),
      '#type' => 'item',
    );
    $options2 = array(
      'en' => t('English'),
      'ro' => t('Romanian'),
      'nr' => t('Not relevant'),
    );
    $language = $form_state->get("language");
    $fields['language'] = array(
      '#title' => $this->t('Preferred langauge'),
      '#description' => 'Please complete with your preferred language for presentations',
      '#required' => TRUE,
      '#type' => 'radios',
      '#options' => $options2,
      '#default_value' => isset($language) ? $form_state->get("language") : NULL,
    );
    $key_expectations = $form_state->get("key_expectations");
    $fields['key_expectations'] = array(
      '#title' => $this->t('Key expectations'),
      '#type' => 'textarea',
      '#description' => 'Please tell us about your expectations for the Global Training. Why are you enrolling?',
      '#default_value' => isset($key_expectations) ? $form_state->get("key_expectations") : NULL,
      '#suffix' => '<div id="recaptcha1"></div>'
    );

    // Adds the captcha to the form.
    // We can't use the intended functionality of the contrib captcha module,
    // because that adds the captcha to the form when it is loaded first.
    $fields += $this->addCaptcha();

    $fields['register'] = array(
      '#type' => 'submit',
      '#value' => 'Register',
      '#attributes' => ['class' => ['next-btn']],
    );

    $fields['back'] = array(
      '#type' => 'button',
      '#value' => 'Back',
      '#ajax' => array(
        'callback' => array($form, 'ajax'),
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
      '#attributes' => ['class' => ['back-btn']],
    );

    // We need to add recaptcha js when the form is rebuilt on the last step,
    // because it's an asynchronous and deferred javascript.
    // This means that the javascript will asynchronously, after the DOM is
    // ready. This means that we can't use the contrib module as intended,
    // because the module loads javascript in the head, on the first page load.
    // So at the last step the recaptcha javascript will have already run,
    // therefore the recaptcha is not rendered.
    // Because we attach the javascript with a library, we can't load the js by
    // language here. That is resolved in the implementation of hook_js_alter.
    $fields['#attached'] = [
      'library' => [
        'dcc_gtd_registration/dcc_gtd_registration.render_captcha'
      ],
    ];

    return $fields;
  }

  /**
   * Adds captcha to the form.
   *
   * @return array
   *   Returns the form element.
   */
  private function addCaptcha() {
    /** @var CaptchaManager $captchaManager */
    $captchaManager = \Drupal::service('dcc_gtd_registration.captcha');

    return $captchaManager->createCaptcha();
  }

}
