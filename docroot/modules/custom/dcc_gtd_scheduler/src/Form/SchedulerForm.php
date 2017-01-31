<?php

namespace Drupal\dcc_gtd_scheduler\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * @var \Drupal\Core\Datetime\DateFormatter;
   */
  protected $date_formatter;

  /**
   * SchedulerForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   Current user.
   */
  public function __construct(EntityTypeManagerInterface $entity, AccountProxyInterface $user, DateFormatter $date_formatter) {
    $this->entity = $entity;
    $this->user = $user;
    $this->date_formatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('date.formatter')
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
      //If next button is pushed, saves the values and increments the step.
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

    return $form;
  }

  protected function getStepFields($step , FormStateInterface $form_state) {
    $fields = [];
    switch ($step) {
      case 1:
        // @todo: add date formatter for all the datetime fields.
        $fields['training'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Training date'),
        );
        $fields['training']['training_start_date'] = array(
          '#type' => 'datetime',
          '#title' => $this->t('Start Date'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue('training_start_date') ?: NULL,
        );

        $fields['training']['training_end_date'] = array(
          '#type' => 'datetime',
          '#title' => $this->t('End Date'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue('training_end_date') ?: NULL,
        );
        break;
      case 2:
        $fields['registration'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Registration date'),
        );

        $fields['registration']['registration_start_date'] = array(
          '#type' => 'datetime',
          '#title' => $this->t('Start Date'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue('registration_start_date') ?: NULL,
        );

        $fields['registration']['registration_end_date'] = array(
          '#type' => 'datetime',
          '#title' => $this->t('End Date'),
          '#required' => TRUE,
          '#default_value' => $form_state->getValue('registration_end_date') ?: NULL,
        );
        break;
      case 3:
        $fields['members'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Members'),
        );

        $fields['members']['number_of_members'] = array(
          '#type' => 'number',
          '#title' => $this->t('Number of members'),
          '#default_value' => $form_state->getValue('number_of_members') ?: NULL,
        );
        break;
      case 4:
        $friday = $form_state->getValue('friday_scheduler');
        $fields['friday_scheduler'] = array(
          '#type' => 'text_format',
          '#title' => $this->t('Friday Scheduler'),
          '#default_value' => $friday['value'] ?: NULL,
          '#format' => $friday['format'] ?: 'basic_html',
        );
        break;
      case 5:
        $saturday = $form_state->getValue('saturday_scheduler');
        $fields['saturday_scheduler'] = array(
          '#type' => 'text_format',
          '#title' => $this->t('Saturday Scheduler'),
          '#default_value' => $saturday['value'] ?: NULL,
          '#format' => $saturday['format'] ?: 'basic_html',
        );
        break;
      case 6:
        $entity = $this->entity->getStorage('node')->create(array(
          'type' => 'drupal_training_scheduler'
        ));
        $entity_form_display = $this->entity->getStorage('entity_form_display')
          ->load('node.drupal_training_scheduler.default');
        $widget = $entity_form_display->getRenderer('field_location');
        $items = $entity->get('field_location');
        $items->setValue($form_state->getValue('field_location') ?: []);
        $form['#parents'] = [];

        $fields['field_location'] = $widget->form($items, $form, $form_state);
        $fields['field_location']['#access'] = $items->access('edit');
        break;
    }

    if ($step != 6) {
      $fields['next'] = array(
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
    if ($step == 6) {
      $fields['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Save',
      );
    }
    if ($step != 1) {
      $fields['back'] = array(
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
      );
    }
    return $fields;
  }

  /**
   * Remove unwanted form values.
   *
   * @param array $values
   * @return array
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
    parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub

    // Validate date intervals.
    if ($form_state->getTriggeringElement()['#value'] != 'Back') {
      if (!empty($form_state->getValue('training_end_date')) &&
        strtotime($form_state->getValue('training_start_date')) > strtotime($form_state->getValue('training_end_date'))      ) {
        $form_state->setErrorByName('training_end_date', t('End date bigger than start date.'));
      }
      if (!empty($form_state->getValue('registration_end_date'))) {
        if (strtotime($form_state->getValue('registration_start_date')) > strtotime($form_state->getValue('registration_end_date'))) {
          $form_state->setErrorByName('registration_end_date', t('End date bigger than start date.'));
        }
        $training_date = $form_state->get('values_' . ($form_state->get('step') - 1))['training_start_date'];
        if (strtotime($form_state->getValue('registration_end_date')) > strtotime($training_date)) {
          $form_state->setErrorByName('registration_end_date', t('Registration should end before training starts on %date.', ['%date' => $training_date]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get values.
    $values = [];
    for($i = 1; $i < 6; $i++) {
      $values += $form_state->get('values_' . $i);
    }
    $values += $form_state->getValues();

    $title = $values['training_start_date']->format("Y-m-d") . ' - ' . $values['training_end_date']->format("Y-m-d");
    $this->entity->getStorage('node')->create(array(
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
    ))->save();

    drupal_set_message(t('Training has been created!'));

  }
}
