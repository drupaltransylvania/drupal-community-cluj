<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Registration step.
 *
 * @Step(
 *   id = "registration_step",
 *   name = @Translation("Registration Step"),
 *   form_id = "dcc_gtd_scheduler_form",
 *   step_number = 2
 * )
 */
class RegistrationStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $fields['registration'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Registration date'),
    );
    $fields['registration']['registration_start_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('registration_start_date') ?: NULL,
    );
    $fields['registration']['registration_end_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('End Date'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('registration_end_date') ?: NULL,
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
      '#attributes' => ['style' => ['float: left; margin-right: 4px;']],
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
    );

    return $fields;
  }

}
