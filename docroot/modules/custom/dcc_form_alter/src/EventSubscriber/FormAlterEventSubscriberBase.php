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
    $events['form_alter'][] = ['onFormAlter'];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onFormAlter(FormAlterEvent $event) {
    $this->init($event);
    $this->form = $this->alterForm($event->getForm());
    $this->updateFormData($event);
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
   * Initializes the properties that can be used by the child class.
   *
   * @param FormAlterEvent $event
   *   The form alter event.
   */
  protected function init(FormAlterEvent $event) {
    $this->event = $event;
    $this->formId = $event->getFormId();
    $this->formState = $event->getFormState();
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
