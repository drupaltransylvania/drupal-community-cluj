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
use Drupal\dcc_multistep\StepPluginManagerInterface;
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
   * Step plugin manager service.
   *
   * @var \Drupal\dcc_multistep\StepPluginManagerInterface
   */
  protected $stepPluginManager;

  /**
   * An array of step plugin instances.
   *
   * @var \ArrayObject
   */
  protected $steps;

  /**
   * GlobalTrainingRegistrationForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country manager.
   * @param \Drupal\dcc_multistep\StepPluginManagerInterface $stepPluginManager
   *   Steps plugin manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CountryManagerInterface $countryManager,
    StepPluginManagerInterface $stepPluginManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->countryManager = $countryManager;
    $this->stepPluginManager = $stepPluginManager;
    $this->steps = $this->stepPluginManager->getSteps($this->getFormId());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('country_manager'),
      $container->get('plugin.manager.dcc_multistep.steps')
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

    // Sets the value of the current step.
    $stepPlugin = $this->steps->offsetGet($step);
    $stepPlugin->setCurrentValues($form_state);

    // If next button is pushed, increments the step.
    if ($form_state->getTriggeringElement()['#value'] == 'Next') {
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

    // This is where the form elements are being added. Each step plugin
    // provides a form builder for this task.
    $step = $form_state->get('step');
    $stepPlugin = $this->steps->offsetGet($step);

    $fields = $stepPlugin->buildStep($form_state, $this);

    $form['container'] = $form['container'] + $fields;

    $form['#attributes']['class'][] = 'contact-message-feedback-form';
    $form['#attributes']['class'][] = 'contact-message-form';
    $form['#attributes']['class'][] = 'contact-form';

    return $form;
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
    /* @var \Drupal\dcc_multistep\StepPluginInspectionInterface */
    $stepPlugin = $this->steps->offsetGet($step);
    $stepPlugin->validate($form_state);
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
    try {
      $this->saveRegistration($form_state);
      drupal_set_message(t('Your registration have been submited with success!'));
    }
    catch (\Exception $exception) {
      watchdog_exception('Scheduler Creation', $exception);
      drupal_set_message(t('There was an error with the creation, please check the logs'));
    }
  }

  /**
   * Saves the registration node.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  private function saveRegistration(FormStateInterface $form_state) {
    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->create($this->buildFieldsForRegistration($form_state));

    $session = $this->entityTypeManager->getStorage('node')->load(RegistrationAccess::getCurrentSessionNid());
    if ($session instanceof Node) {
      $node->set('field_training_session', $session->id());
    }

    $node->save();
  }

  /**
   * Builds the data structure with fields, for registration node save.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   An array of fields with form state values.
   */
  private function buildFieldsForRegistration(FormStateInterface $form_state) {
    return array(
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
      'field_organization' => $form_state->get("organization"),
      'field_industry_experience' => $form_state->get("industry_experience"),
      'field_attend_day' => $form_state->getValue('attend_day'),
      'field_laptop' => $form_state->getValue("laptop"),
      'field_preferred_language' => $form_state->getValue("language"),
      'field_key_expectations' => $form_state->getValue("key_expectations"),
    );
  }

}
