<?php

namespace Drupal\dcc_gtd_registration\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Address Information Step step.
 *
 * @Step(
 *   id = "address_information_step",
 *   name = @Translation("Address Information Step"),
 *   form_id= "dcc_gtd_registration",
 *   step_number = 2,
 * )
 */
class AddressInformationStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setCurrentValues(FormStateInterface $formState) {
    $formState->set("country", $formState->getValue("country"));
    $formState->set("city", $formState->getValue("city"));
    $formState->set("address", $formState->getValue("address"));
  }

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $countries = \Drupal::service('country_manager')->getList();

    $fields['title'] = array(
      '#title' => $this->t("Address"),
      '#type' => 'item',
    );
    $country = $form_state->get("country");
    $fields['country'] = array(
      '#title' => $this->t("Country"),
      '#type' => 'select',
      '#options' => $countries,
      '#default_value' => isset($country) ? $form_state->get("country") : NULL,
    );
    $city = $form_state->get("city");
    $fields['city'] = array(
      '#title' => $this->t('City'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($city) ? $form_state->get("city") : NULL,
    );
    $address = $form_state->get("address");
    $fields['address'] = array(
      '#title' => $this->t('Address'),
      '#type' => 'textarea',
      '#default_value' => isset($address) ? $form_state->get("address") : NULL,
    );

    $fields['next'] = array(
      '#type' => 'button',
      '#value' => 'Next',
      '#ajax' => array(
        'callback' => array($form, 'ajax'),
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => NULL,
        ),
      ),
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

    return $fields;
  }

}
