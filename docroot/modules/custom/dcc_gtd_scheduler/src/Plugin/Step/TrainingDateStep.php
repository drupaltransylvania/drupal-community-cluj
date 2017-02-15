<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Training Date step.
 *
 * @Step(
 *   id = "training_date_step",
 *   name = @Translation("Training Date Step"),
 *   form_id= "dcc_gtd_scheduler_form",
 *   step_number = 1,
 * )
 */
class TrainingDateStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    // @todo: add date formatter for all the datetime fields.
    $fields['training'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Training date'),
    );
    $fields['training']['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Training title'),
      '#default_value' => $form_state->getValue('title') ?:NULL,
    );
    $fields['training']['training_start_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('training_start_date') ?: NULL,
      '#attributes' => [
        'class' => ['dcc-training-start'],
      ],
    );
    $fields['training']['training_end_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('End Date'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('training_end_date') ?: NULL,
      '#attributes' => [
        'class' => ['dcc-training-end'],
      ],
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

  /**
   * {@inheritdoc}
   */
  public function validate(FormStateInterface $formState) {
    if (!empty($formState->getValue('training_end_date')) &&
      strtotime($formState->getValue('training_start_date')) > strtotime($formState->getValue('training_end_date'))) {
      $formState->setErrorByName('training_end_date', t('End date bigger than start date.'));
    }
  }

}
