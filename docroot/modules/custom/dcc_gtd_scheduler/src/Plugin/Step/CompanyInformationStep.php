<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Location Step step.
 *
 * @Step(
 *   id = "company_information_step",
 *   name = @Translation("Company Information Step"),
 *   form_id= "dcc_gtd_scheduler_form",
 *   step_number = 7,
 * )
 */
class CompanyInformationStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $fields['company'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Company information'),
    );
    $fields['company']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Company name"),
    );
    $fields['company']['logo'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t("Logo"),
    );
    $fields['company']['website'] = array(
      '#type' => 'url',
      '#title' => $this->t("Website link")
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
    $fields['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save',
    );

    return $fields;
  }

}
