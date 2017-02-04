<?php

namespace Drupal\dcc_gtd_registration\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\dcc_gtd_registration\RegistrationAccess;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GlobalTrainingRegistrationForm.
 *
 * @package Drupal\dcc_gtd_registration\Form
 */
class GlobalTrainingRegistrationForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManager
   */
  protected $countryManager;

  /**
   * GlobalTrainingRegistrationForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CountryManagerInterface $countryManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->countryManager = $countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('country_manager')
    );
  }

  /**
   * Returns the form Id.
   *
   * @return string
   *   The form id.
   */
  public function getFormId() {
    return 'dcc_gtd_registration';
  }

  /**
   * Creates the form elements regarding the step.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Returns the $form with all the fields created according step.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get("step");
    if (!isset($step)) {
      $form_state->set('step', 1);
      $step = $form_state->get("step");
    }

    // If next button is pushed, saves the values and increments the step.
    if ($form_state->getTriggeringElement()['#value'] == 'Next') {
      if ($step == 1) {
        $form_state->set("first_name", $form_state->getValue("first_name"));
        $form_state->set("last_name", $form_state->getValue("last_name"));
        $form_state->set("email", $form_state->getValue("email"));
        $form_state->set("drupal_user", $form_state->getValue("drupal_user"));
        $form_state->set("phone", $form_state->getValue("phone"));
        $form_state->set("age", $form_state->getValue("age"));
        $form_state->set("gender", $form_state->getValue("gender"));
      }
      if ($step == 2) {
        $form_state->set("country", $form_state->getValue("country"));
        $form_state->set("city", $form_state->getValue("city"));
        $form_state->set("address", $form_state->getValue("address"));
      }
      if ($step == 3) {
        $form_state->set("occupation", $form_state->getValue("occupation"));
        $form_state->set("organization", $form_state->getValue("organization"));
        $form_state->set("industry_experience", $form_state->getValue("industry_experience"));
      }

      $form_state->set('step', $form_state->get('step') + 1);
    }
    // If back button is pushed, decrements the step.
    if ($form_state->getTriggeringElement()['#value'] == 'Back') {
      $form_state->set('step', $form_state->get('step') - 1);
    }

    $form['container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'ajaxcontainer',
      ),
    );

    $step = $form_state->get('step');
    switch ($step) {
      case 1:
        $this->stepOneElements($form, $form_state);
        break;

      case 2:
        $this->stepTwoElements($form, $form_state);
        break;

      case 3:
        $this->stepThreeElements($form, $form_state);
        break;

      case 4:
        $this->stepFourElements($form, $form_state);
        break;

      default:
        break;

    }
    if ($step == 1 || $step == 2 || $step == 3) {
      $form['container']['next'] = array(
        '#type' => 'button',
        '#value' => 'Next',
        '#ajax' => array(
          'callback' => array($this, 'ajax'),
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
      );
    }
    if ($step == 4) {
      $form['container']['register'] = array(
        '#type' => 'submit',
        '#value' => 'Register',
      );
    }
    if ($step == 2 || $step == 3 || $step == 4) {
      $form['container']['back'] = array(
        '#type' => 'button',
        '#value' => 'Back',
        '#ajax' => array(
          'callback' => array($this, 'ajax'),
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
        '#attributes' => ['style' => ['float: left; margin-right: 4px;']],
      );
    }
    $form['#attributes']['class'][] = 'contact-message-feedback-form';
    $form['#attributes']['class'][] = 'contact-message-form';
    $form['#attributes']['class'][] = 'contact-form';
    return $form;
  }

  /**
   * Creates the form elements for the first step.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function stepOneElements(array &$form, FormStateInterface &$form_state) {
    $form['container']['title'] = array(
      '#title' => $this->t("Personal Informations"),
      '#type' => 'item',
    );
    $first_name = $form_state->get("first_name");
    $form['container']['first_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#required' => TRUE,
      '#default_value' => isset($first_name) ? $form_state->get("first_name") : NULL,
    );
    $last_name = $form_state->get("last_name");
    $form['container']['last_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#required' => TRUE,
      '#default_value' => isset($last_name) ? $form_state->get("last_name") : NULL,
    );
    $email = $form_state->get("email");
    $form['container']['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#default_value' => isset($email) ? $form_state->get("email") : NULL,
    );
    $drupal_user = $form_state->get("drupal_user");
    $form['container']['drupal_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Drupal user'),
      '#description' => "If you are registered on Drupal.org please provide us your Drupal username",
      '#default_value' => isset($drupal_user) ? $form_state->get("drupal_user") : NULL,
    );
    $phone = $form_state->get("phone");
    $form['container']['phone'] = array(
      '#type' => 'tel',
      '#title' => $this->t('Phone'),
      '#required' => TRUE,
      '#default_value' => isset($phone) ? $form_state->get("phone") : NULL,
    );
    $age = $form_state->get("age");
    $form['container']['age'] = array(
      '#type' => 'number',
      '#title' => $this->t('Age'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 99,
      '#field_suffix' => 'years',
      '#default_value' => isset($age) ? $form_state->get("age") : NULL,
    );
    $active = array(
      'not_share' => t('Prefer to not share'),
      'male' => t('male'),
      'female' => t('female'),
      'transgender' => t('transgender'),
      'other' => t('other'),
    );
    $gender = $form_state->get("gender");
    $form['container']['gender'] = array(
      '#type' => 'select',
      '#title' => $this->t('Gender'),
      '#options' => $active,
      '#required' => TRUE,
      '#default_value' => isset($gender) ? $form_state->get("gender") : NULL,
    );
  }

  /**
   * Creates the form elements for the second step.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function stepTwoElements(array &$form, FormStateInterface &$form_state) {
    $countries = $this->countryManager->getList();

    $form['container']['title'] = array(
      '#title' => $this->t("Address"),
      '#type' => 'item',
    );
    $country = $form_state->get("country");
    $form['container']['country'] = array(
      '#title' => $this->t("Country"),
      '#type' => 'select',
      '#options' => $countries,
      '#default_value' => isset($country) ? $form_state->get("country") : NULL,
    );
    $city = $form_state->get("city");
    $form['container']['city'] = array(
      '#title' => $this->t('City'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => isset($city) ? $form_state->get("city") : NULL,
    );
    $address = $form_state->get("address");
    $form['container']['address'] = array(
      '#title' => $this->t('Address'),
      '#type' => 'textarea',
      '#default_value' => isset($address) ? $form_state->get("address") : NULL,
    );
  }

  /**
   * Creates the form elements for the third step.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function stepThreeElements(array &$form, FormStateInterface &$form_state) {
    $form['container']['title'] = array(
      '#title' => $this->t("Professional details"),
      '#type' => 'item',
    );
    $occupation = $form_state->get("occupation");
    $form['container']['occupation'] = array(
      '#title' => $this->t("Occupation"),
      '#type' => 'textfield',
      '#description' => 'In which domain are you activating right now?(e.g. Student,IT,Banking,Manufactoring etc.)',
      '#required' => TRUE,
      '#default_value' => isset($occupation) ? $form_state->get("occupation") : NULL,
    );
    $organization = $form_state->get("organization");
    $form['container']['organization'] = array(
      '#title' => $this->t('Organization'),
      '#type' => 'textfield',
      '#default_value' => isset($organization) ? $form_state->get("organization") : NULL,
    );
    $industry_experience = $form_state->get("industry_experience");
    $form['container']['industry_experience'] = array(
      '#title' => $this->t('Industry experience'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 99,
      '#field_suffix' => 'years',
      '#default_value' => isset($industry_experience) ? $form_state->get("industry_experience") : NULL,
    );
  }

  /**
   * Creates the form elements for the fourth step.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  private function stepFourElements(array &$form, FormStateInterface &$form_state) {
    $form['container']['title'] = array(
      '#title' => $this->t("Training details"),
      '#type' => 'item',
    );
    $attend_day = $form_state->get("attend_day");
    $form['container']['attend_day'] = array(
      '#title' => $this->t('On which days will you attend the training ?'),
      '#description' => 'See the schedule!',
      '#type' => 'checkboxes',
      '#options' => array(
        'Friday' => $this->t('Friday'),
        'Saturday' => $this->t('Saturday'),
      ),
      '#required' => TRUE,
      '#default_value' => isset($attend_day) ? $form_state->get("attend_day") : NULL,
    );
    $options = array(0 => t('No'), 1 => t('Yes'));
    $laptop = $form_state->get("laptop");
    $form['container']['laptop'] = array(
      '#title' => $this->t('Will you carry a laptop ?'),
      '#description' => 'To know how many working stations will we need we should know if will you come with your own laptop. In case you will, please pre-configure your environment to be able to install Drupal 8 on it(if you need help check Drupa.org). Note: You will need your laptop for development only on Saturday',
      '#type' => 'radios',
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => isset($laptop) ? $form_state->get("laptop") : NULL,
    );
    $options2 = array(
      'en' => t('English'),
      'ro' => t('Romanian'),
      'nr' => t('Not relevant'),
    );
    $language = $form_state->get("language");
    $form['container']['language'] = array(
      '#title' => $this->t('Preferred langauge'),
      '#description' => 'Please complete with your preferred language for presentations',
      '#required' => TRUE,
      '#type' => 'radios',
      '#options' => $options2,
      '#default_value' => isset($language) ? $form_state->get("language") : NULL,
    );
    $key_expectations = $form_state->get("key_expectations");
    $form['container']['key_expectations'] = array(
      '#title' => $this->t('Key expectations'),
      '#type' => 'textarea',
      '#description' => 'Please tell us about your expectations for the Global Training. Why are you enrolling?',
      '#default_value' => isset($key_expectations) ? $form_state->get("key_expectations") : NULL,
    );
  }

  /**
   * Ajax callback used for replacing the container with the form elements.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function ajax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#ajaxcontainer', $form['container']));
    $response->addCommand(new PrependCommand('#ajaxcontainer', StatusMessages::renderMessages(NULL)));
    $form_state->setRebuild();

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get("step");
    switch ($step) {
      case 1:
        if (!is_numeric($form_state->getValue("phone"))) {
          $form_state->setErrorByName('phone', $this->t("Phone field should contain only numbers! Please modify it!"));
        }
        break;

      case 3:
        if ($form_state->getValue("industry_experience") >= $form_state->get("age")) {
          $form_state->setErrorByName('industry_experience', $this->t("The experience should be smaller than your age! Please modify it!"));
        }
        break;

      case 4:
        $form_state->set("attend_day", $form_state->getValue("attend_day"));
        $form_state->set("laptop", $form_state->getValue("laptop"));
        $form_state->set("language", $form_state->getValue("language"));
        $form_state->set("key_expectations", $form_state->getValue("key_expectations"));

        $data = array(
          'type' => 'global_training_day_registration',
          'title' => $form_state->get("first_name"),
          'uid' => 1,
          'field_first_name' => $form_state->get("first_name"),
          'field_last_name' => $form_state->get("last_name"),
          'field_email' => $form_state->get("email"),
          'field_drupal_user' => $form_state->get("drupal_user"),
          'field_phone' => $form_state->get("phone"),
          'field_age' => $form_state->get("age"),
          'field_gender' => $form_state->get("gender"),
          'field_country' => $form_state->get("country"),
          'field_city' => $form_state->get("city"),
          'field_address' => $form_state->get("address"),
          'field_occupation' => $form_state->get("occupation"),
          'field_organizations' => $form_state->get("organization"),
          'field_industry_experience' => $form_state->get("industry_experience"),
          'field_attend_day' => $form_state->get("attend_day"),
          'field_laptop' => $form_state->get("laptop"),
          'field_preferred_language' => $form_state->get("language"),
          'field_key_expectations' => $form_state->get("key_expectations"),
        );
        $node = $this->entityTypeManager->getStorage('node')->create($data);
        $form_state->set("node", $node);
        break;

      default:
        break;

    }
  }

  /**
   * Saves the node created in validateForm and sets a result message.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $form_state->get("node");
    $session = $this->entityTypeManager->getStorage('node')->load(RegistrationAccess::getCurrentSessionNid());
    if ($session instanceof Node) {
      $node->set('field_training_session', $session->id());
    }
    if ($node->save()) {
      drupal_set_message("Your registration have been submited with success!");
    }
    else {
      drupal_set_message("Something went wrong, please try again!");
    }

  }

}
