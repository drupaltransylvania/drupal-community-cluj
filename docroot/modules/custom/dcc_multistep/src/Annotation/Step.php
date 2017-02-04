<?php

namespace Drupal\dcc_multistep\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a step for multi-step forms.
 *
 * @see \Drupal\dcc_multistep\StepPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Step extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the validator.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The form to which the step belongs to.
   *
   * @var string
   */
  public $form_id;

}
