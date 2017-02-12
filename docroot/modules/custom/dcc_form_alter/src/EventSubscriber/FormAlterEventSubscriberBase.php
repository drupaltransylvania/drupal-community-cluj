<?php

namespace Drupal\dcc_form_alter\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dcc_form_alter\Event\FormAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FormAlterEventSubscriberBase.
 *
 * @package Drupal\dcc_bottom_widgets\EventSubscriber
 */
abstract class FormAlterEventSubscriberBase implements EventSubscriberInterface {

  /**
   * The form alter event that is dispatched in the form alter hook.
   *
   * @var FormAlterEvent
   */
  protected $event;

  /**
   * The form id.
   *
   * @var string
   */
  protected $formId;

  /**
   * The form array.
   *
   * @var array
   */
  protected $form;

  /**
   * The form state.
   *
   * @var FormStateInterface
   */
  protected $formState;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FormAlterEvent::FORM_ALTER_EVENT][] = ['onFormAlter'];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onFormAlter(FormAlterEvent $event) {
    $this->init($event);

    // If the form altering service definition contains the form_id, then we
    // need to perform this check so that we only alter that specific form.
    // This check will also pass, if the developer defines as simple event
    // subscriber service, in which case the form_id check will have to be done
    // in the alterForm method of that service.
    if ($event->getFormId() == $this->formId) {
      $this->form = $this->alterForm($event->getForm());
      $this->updateFormData($event);
    }
  }

  /**
   * Alters the form array.
   *
   * @param array $form
   *   The form array.
   *
   * @return array
   *   The altered form array.
   */
  protected abstract function alterForm(array $form);

  /**
   * Sets the form id.
   *
   * @param string $formId
   *   The form id.
   */
  public function setFormId($formId) {
    $this->formId = $formId;
  }

  /**
   * Initializes the properties that can be used by the child class.
   *
   * @param FormAlterEvent $event
   *   The form alter event.
   */
  protected function init(FormAlterEvent $event) {
    $this->event = $event;
    // The form id could be set from the service definition via the setFormId
    // method.
    if (!$this->formId) {
      $this->formId = $event->getFormId();
    }
    $this->formState = $event->getFormState();
    $this->form = $event->getForm();
  }

  /**
   * Updates the event object with the altered form.
   *
   * @param FormAlterEvent $event
   *   The form alter event.
   */
  protected function updateFormData(FormAlterEvent $event) {
    $event->setForm($this->form);
    $event->setFormState($this->formState);
  }

}
