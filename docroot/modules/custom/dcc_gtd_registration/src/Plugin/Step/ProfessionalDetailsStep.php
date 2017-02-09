<?php

namespace Drupal\dcc_gtd_registration\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Professional Details Step.
 *
 * @Step(
 *   id = "professional_details_step",
 *   name = @Translation("Professional Details Step"),
 *   form_id= "dcc_gtd_registration",
 *   step_number = 3,
 * )
 */
class ProfessionalDetailsStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setCurrentValues(FormStateInterface $formState) {
    $formState->set("occupation", $formState->getValue("occupation"));
    $formState->set("organization", $formState->getValue("organization"));
    $formState->set("industry_experience", $formState->getValue("industry_experience"));
  }

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $fields['title'] = array(
      '#title' => $this->t("Professional details"),
      '#type' => 'item',
    );

    $occupation = $form_state->get("occupation");
    $fields['occupation'] = array(
      '#title' => $this->t("Occupation"),
      '#type' => 'textfield',
      '#description' => 'In which domain are you activating right now?(e.g. Student,IT,Banking,Manufactoring etc.)',
      '#required' => TRUE,
      '#default_value' => isset($occupation) ? $occupation : NULL,
    );

    $organization = $form_state->get("organization");
    $fields['organization'] = array(
      '#title' => $this->t('Organization'),
      '#type' => 'textfield',
      '#default_value' => isset($organization) ? $organization : NULL,
    );

    $industry_experience = $form_state->get("industry_experience");
    $fields['industry_experience'] = array(
      '#title' => $this->t('Industry experience'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 99,
      '#field_suffix' => 'years',
      '#default_value' => isset($industry_experience) ? $industry_experience : NULL,
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

  /**
   * {@inheritdoc}
   */
  public function validate(FormStateInterface $formState) {
    if ($formState->getValue("industry_experience") >= $formState->get("age")) {
      $formState->setErrorByName('industry_experience', $this->t("The experience should be smaller than your age! Please modify it!"));
    }
  }

}
