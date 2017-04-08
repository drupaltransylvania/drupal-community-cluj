<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Friday Scheduler Step step.
 *
 * @Step(
 *   id = "friday_scheduler_step",
 *   name = @Translation("Friday Scheduler Step"),
 *   form_id= "dcc_gtd_scheduler_form",
 *   step_number = 4,
 * )
 */
class FridaySchedulerStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $friday = $form_state->getValue('friday_scheduler');
    $fields['friday_scheduler'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Friday Scheduler'),
      '#default_value' => $friday['value'] ?: NULL,
      '#format' => $friday['format'] ?: 'basic_html',
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
