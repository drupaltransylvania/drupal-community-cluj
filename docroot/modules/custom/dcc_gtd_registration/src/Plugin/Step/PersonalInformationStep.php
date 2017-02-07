<?php

namespace Drupal\dcc_gtd_registration\Plugin\Step;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginBase;

/**
 * Provides Personal Information step.
 *
 * @Step(
 *   id = "personal_information_step",
 *   name = @Translation("Personal Information Step"),
 *   form_id= "dcc_gtd_registration",
 *   step_number = 1,
 * )
 */
class PersonalInformationStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setCurrentValues(FormStateInterface $formState) {
    $formState->set("first_name", $formState->getValue("first_name"));
    $formState->set("last_name", $formState->getValue("last_name"));
    $formState->set("email", $formState->getValue("email"));
    $formState->set("drupal_user", $formState->getValue("drupal_user"));
    $formState->set("phone", $formState->getValue("phone"));
    $formState->set("age", $formState->getValue("age"));
    $formState->set("gender", $formState->getValue("gender"));
  }

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    $fields['title'] = array(
      '#title' => $this->t("Personal Informations"),
      '#type' => 'item',
    );

    $first_name = $form_state->get("first_name");
    $fields['first_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#required' => TRUE,
      '#default_value' => isset($first_name) ? $first_name : NULL,
    );

    $last_name = $form_state->get("last_name");
    $fields['last_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#required' => TRUE,
      '#default_value' => isset($last_name) ? $last_name : NULL,
    );

    $email = $form_state->get("email");
    $fields['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#default_value' => isset($email) ? $email : NULL,
    );

    $drupalUser = $form_state->get("drupal_user");
    $fields['drupal_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Drupal user'),
      '#description' => "If you are registered on Drupal.org please provide us your Drupal username",
      '#default_value' => isset($drupalUser) ? $drupalUser : NULL,
    );

    $phone = $form_state->get("phone");
    $fields['phone'] = array(
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#required' => TRUE,
      '#default_value' => isset($phone) ? $phone : NULL,
    );

    $age = $form_state->get("age");
    $fields['age'] = array(
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 99,
      '#field_suffix' => 'years',
      '#default_value' => isset($age) ? $age : NULL,
    );

    $active = array(
      'not_share' => t('Prefer to not share'),
      'male' => t('male'),
      'female' => t('female'),
      'transgender' => t('transgender'),
      'other' => t('other'),
    );
    $gender = $form_state->get("gender");
    $fields['gender'] = array(
      '#type' => 'select',
      '#title' => $this->t('Gender'),
      '#options' => $active,
      '#required' => TRUE,
      '#default_value' => isset($gender) ? $gender : NULL,
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
    if (!is_numeric($formState->getValue("phone"))) {
      $formState->setErrorByName('phone', $this->t("Phone field should contain only numbers! Please modify it!"));
    }
  }

}
