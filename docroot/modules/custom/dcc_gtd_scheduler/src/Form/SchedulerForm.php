<?php

namespace Drupal\dcc_gtd_scheduler\Form;

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
  public function __construct(
    EntityTypeManagerInterface $entity,
    AccountProxyInterface $user,
    DateFormatter $date_formatter
  ) {
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

    $form['training'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Training date'),
    );

    // @todo: add date formatter for all the datetime fields.
    $form['training']['training_start_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
    );

    $form['training']['training_end_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('End Date'),
      '#required' => TRUE,
    );

    $form['registration'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Registration date'),
    );

    $form['registration']['registration_start_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
    );

    $form['registration']['registration_end_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('End Date'),
      '#required' => TRUE,
    );

    $form['number_of_members'] = array(
      '#type' => 'number',
      '#title' => $this->t('Number of members'),
    );

    $form['friday_scheduler'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Friday Scheduler'),
    );

    $form['saturday_scheduler'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Saturday Scheduler'),
    );

    $form['location'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Location'),
    );
    $form['location']['map'] = array(
      '#type' => 'item',
      '#markup' => '<div class="google-map-field-preview"></div>',
      '#attached' => array(
        'library' => array(
          'google_map_field/google-map-field-widget-renderer',
          'google_map_field/google-map-apis',
          'dcc_gtd_scheduler/dcc-gtd-scheduler-google-map',
        ),
      ),
    );

    $form['location']['lat'] = array(
      '#type' => 'hidden',
      '#title' => $this->t('Lat'),
      '#attributes' => array('id' => array('dcc-lat')),
    );

    $form['location']['lng'] = array(
      '#type' => 'hidden',
      '#title' => $this->t('Long'),
      '#attributes' => array('id' => array('dcc-lng')),

    );

    $form['location']['address'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Processed datetime in order to use the date and time elements.
    $tra_processed = Datetime::processDatetime($form['training'], $form_state, $form);
    $reg_processed = Datetime::processDatetime($form['registration'], $form_state, $form);

    $title = $tra_processed['training_start_date']['date']['#value'] . ' - ' . $tra_processed['training_start_date']['date']['#value'];

    $this->entity->getStorage('node')->create(array(
      'type' => 'drupal_training_scheduler',
      'title' => $title,
      'uid' => $this->user->id(),
      'status' => 1,
      'field_friday_schedule' => $form_state->getValue('friday_scheduler'),
      'field_location' => array(
        'name' => $form_state->getValue('address'),
        'lat' => $form_state->getValue('lat'),
        'lon' => $form_state->getValue('lng'),
        'zoom' => 10,
      ),
      'field_number_of_members' => $form_state->getValue('number_of_members'),
      'field_registration_period' => array(
        'value' => $reg_processed['registration_start_date']['date']['#value'] . 'T' . $reg_processed['registration_start_date']['time']['#value'],
        'end_value' => $reg_processed['registration_end_date']['date']['#value'] . 'T' . $reg_processed['registration_end_date']['time']['#value'],
      ),
      'field_saturday_schedule' => $form_state->getValue('saturday_scheduler'),
      'field_training_date' => array(
        'value' => $tra_processed['training_start_date']['date']['#value'] . 'T' . $tra_processed['training_start_date']['time']['#value'],
        'end_value' => $tra_processed['training_start_date']['date']['#value'] . 'T' . $tra_processed['training_start_date']['time']['#value'],
      ),
    ))->save();

  }

}
