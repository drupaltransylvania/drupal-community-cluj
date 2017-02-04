<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Saturday Scheduler Step step.
 *
 * @Step(
 *   id = "saturday_scheduler_step",
 *   name = @Translation("Saturday Scheduler Step"),
 *   form_id= "dcc_gtd_scheduler_form",
 *   step_number = 5,
 * )
 */
class SaturdaySchedulerStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $saturday = $form_state->getValue('saturday_scheduler');
    $fields['saturday_scheduler'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Saturday Scheduler'),
      '#default_value' => $saturday['value'] ?: NULL,
      '#format' => $saturday['format'] ?: 'basic_html',
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
