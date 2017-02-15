<?php

namespace Drupal\dcc_gtd_scheduler\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_multistep\StepPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Created a form similar to 'Drupal Training Scheduler' one.
 *
 * @package Drupal\dcc_gtd_scheduler\Form
 */
class SchedulerForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

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
   * SchedulerForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   Current user.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter.
   * @param \Drupal\dcc_multistep\StepPluginManagerInterface $stepPluginManager
   *   The step plugin manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity,
    AccountProxyInterface $user,
    DateFormatter $dateFormatter,
    StepPluginManagerInterface $stepPluginManager
  ) {
    $this->entity = $entity;
    $this->user = $user;
    $this->dateFormatter = $dateFormatter;
    $this->stepPluginManager = $stepPluginManager;
    $this->steps = $this->stepPluginManager->getSteps($this->getFormId());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.dcc_multistep.steps')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dcc_gtd_scheduler_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$form_state->get('step')) {
      $form_state->set('step', 1);
    }
    // Switch values and steps.
    if (!empty($form_state->getTriggeringElement()['#value'])) {
      // Save current form_state.
      $values = $this->sanitizeValues($form_state->getValues());
      $form_state->set('values_' . $form_state->get('step'), $values);
      // If next button is pushed, saves the values and increments the step.
      if ($form_state->getTriggeringElement()['#value'] == 'Next') {
        $form_state->set('step', $form_state->get('step') + 1);
      }
      elseif ($form_state->getTriggeringElement()['#value'] == 'Back') {
        $form_state->set('step', $form_state->get('step') - 1);
      }
      // Get current step form_state.
      $form_state->setValues($form_state->get('values_' . $form_state->get('step')) ?: []);
    }
    // Get current step.
    $step = $form_state->get('step');
    $form['container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'ajaxcontainer',
      ),
    );
    $form['container'] = $form['container'] + $this->getStepFields($step, $form_state);
    $form['#after_build'] = ['::hideTextFormat'];
    $form['#attached']['library'][] = 'google_map_field/google-map-apis';
    $form['#attributes']['class'][] = 'contact-message-feedback-form';
    $form['#attributes']['class'][] = 'contact-message-form';
    $form['#attributes']['class'][] = 'contact-form';
    return $form;
  }

  /**
   * Provides the fields for the current step.
   *
   * @param int $step
   *   The current step in the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The form array.
   */
  protected function getStepFields($step, FormStateInterface $form_state) {
    $stepPlugin = $this->steps->offsetGet($step);

    $fields = $stepPlugin->buildStep($form_state, $this);

    return $fields;
  }

  /**
   * Remove unwanted form values.
   *
   * @param array $values
   *   The form values array.
   *
   * @return array
   *   The sanitized array.
   */
  protected function sanitizeValues(array $values) {
    unset($values['form_build_id']);
    unset($values['form_id']);
    unset($values['form_token']);
    unset($values['op']);
    unset($values['next']);
    unset($values['back']);
    unset($values['submit']);
    return $values;
  }

  /**
   * Callback for after build.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The modified form element.
   */
  public function hideTextFormat(array $element, FormStateInterface $form_state) {
    if (!empty($element['container']['friday_scheduler'])) {
      $element['container']['friday_scheduler']['format']['#attributes']['class'][] = 'hidden';
      $element['container']['friday_scheduler']['format']['format']['#access'] = FALSE;
      $element['container']['friday_scheduler']['format']['guidelines']['#access'] = FALSE;
      $element['container']['friday_scheduler']['format']['help']['#access'] = FALSE;
    }
    if (!empty($element['container']['saturday_scheduler'])) {
      $element['container']['saturday_scheduler']['format']['#attributes']['class'][] = 'hidden';
      $element['container']['saturday_scheduler']['format']['format']['#access'] = FALSE;
      $element['container']['saturday_scheduler']['format']['guidelines']['#access'] = FALSE;
      $element['container']['saturday_scheduler']['format']['help']['#access'] = FALSE;
    }
    return $element;
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
    // Only the 'Next' operation has to perform validation.
    if ($form_state->getTriggeringElement()['#value'] != 'Back') {
      parent::validateForm($form, $form_state);

      $step = $form_state->get('step');
      $stepPlugin = $this->steps->offsetGet($step);
      $stepPlugin->validate($form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $node = $this->saveSchedule($this->getValues($form_state));
      drupal_set_message(t('Training has been created!'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
    }
    catch (\Exception $exception) {
      watchdog_exception('Scheduler Creation', $exception);
      drupal_set_message(t('There was an error with the creation, please check the logs'));
    }
  }

  /**
   * Generates an array of values that need to be saved in the database.
   */
  private function getValues(FormStateInterface $form_state) {
    $stepNumber = $this->steps->count();

    $values = [];
    for ($i = 1; $i < $stepNumber; $i++) {
      $values += $form_state->get('values_' . $i);
    }
    $values += $form_state->getValues();

    return $values;
  }

  /**
   * Saves the schedule as a node of type 'drupal_training_schedule'.
   */
  private function saveSchedule($values) {
    $node = $this->entity->getStorage('node')->create($this->buildFieldsForSchedule($values));
    $node->save();
    return $node;
  }

  /**
   * Builds an array with the fields of the entity.
   */
  private function buildFieldsForSchedule($values) {
    $title = $values['title'];

    return array(
      'type' => 'drupal_training_scheduler',
      'title' => $title,
      'uid' => $this->user->id(),
      'status' => 1,
      'field_friday_schedule' => $values['friday_scheduler'],
      'field_location' => array(
        'name' => $values['field_location'][0]['name'],
        'lat' => $values['field_location'][0]['lat'],
        'lon' => $values['field_location'][0]['lon'],
        'zoom' => $values['field_location'][0]['zoom'],
      ),
      'field_number_of_members' => $values['number_of_members'],
      'field_registration_period' => [
        'value' => $values['registration_start_date']->format("Y-m-d\Th:i:s"),
        'end_value' => $values['registration_end_date']->format("Y-m-d\Th:i:s"),
      ],
      'field_saturday_schedule' => $values['saturday_scheduler'],
      'field_training_date' => [
        'value' => $values['training_start_date']->format("Y-m-d\Th:i:s"),
        'end_value' => $values['training_end_date']->format("Y-m-d\Th:i:s"),
      ],
      'field_company_name' => $values['name'],
      'field_logo' => $values['logo'],
      'field_website_link' => $values['website'],
    );
  }

}
