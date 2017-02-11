<?php

namespace Drupal\dcc_form_alter\Event;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FormAlterEvent.
 *
 * This event is fired in the form alter hook.
 *
 * @package Drupal\dcc_form_alter\Event
 *
 * @see dcc_form_alter_form_alter
 */
class FormAlterEvent extends Event {

  /**
   * The form id.
   *
   * @var string
   */
  private $formId;

  /**
   * The form array.
   *
   * @var array
   */
  private $form;

  /**
   * The form state.
   *
   * @var FormStateInterface
   */
  private $formState;

  /**
   * FormAlterEvent constructor.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param string $formId
   *   The form id.
   */
  public function __construct(array $form, FormStateInterface $formState, $formId) {
    $this->form = $form;
    $this->formState = $formState;
    $this->formId = $formId;
  }

  /**
   * Returns the form array.
   *
   * @return mixed
   *   The form array.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Sets the form array.
   *
   * @param array $form
   *   The form array.
   */
  public function setForm(array $form) {
    $this->form = $form;
  }

  /**
   * Returns the form state.
   *
   * @return FormStateInterface
   *   The form state.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Sets the form state.
   *
   * @param FormStateInterface $formState
   *   The form state.
   */
  public function setFormState(FormStateInterface $formState) {
    $this->formState = $formState;
  }

  /**
   * Returns the form id.
   *
   * @return string
   *   The form id.
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   * Sets the form id.
   *
   * @param string $formId
   *   The form id.
   */
  public function setFormId($formId) {
    $this->formId = $formId;
  }

}
