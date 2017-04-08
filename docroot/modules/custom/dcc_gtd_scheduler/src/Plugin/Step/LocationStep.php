<?php

namespace Drupal\dcc_gtd_scheduler\Plugin\Step;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dcc_multistep\StepPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Location Step step.
 *
 * @Step(
 *   id = "location_step",
 *   name = @Translation("Location Step"),
 *   form_id= "dcc_gtd_scheduler_form",
 *   step_number = 6,
 * )
 */
class LocationStep extends StepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildStep(FormStateInterface $form_state, FormInterface $form) {
    // We can't inject these with the container, because it gets serialized
    // between requests. Serialization of an object that depends on the
    // the database connection is not possible.
    $entity = \Drupal::entityTypeManager()->getStorage('node')->create(array(
      'type' => 'drupal_training_scheduler'
    ));

    $entity_form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')
      ->load('node.drupal_training_scheduler.default');

    $widget = $entity_form_display->getRenderer('field_location');

    $items = $entity->get('field_location');

    $items->setValue($form_state->getValue('field_location') ?: []);

    $form_widget['#parents'] = [];
    $fields['field_location'] = $widget->form($items, $form_widget, $form_state);
    $fields['field_location']['#access'] = $items->access('edit');

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
      '#attributes' => ['style' => ['float: left; margin-right: 4px;']],
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

}
