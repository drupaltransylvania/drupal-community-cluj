<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Training Date step.
 *
 * @Step(
 *   id = "member_number_limit_step",
 *   name = @Translation("Member Number Limit Step"),
 *   form_id = "dcc_gtd_scheduler_form",
 *   step_number = 3,
 * )
 */
class MemberNumberLimitStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $fields['members'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Members'),
    );
    $fields['members']['number_of_members'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of members'),
      '#default_value' => $form_state->getValue('number_of_members') ?: NULL,
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
