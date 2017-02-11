<?php

namespace Drupal\dcc_form_alter\DependencyInjection\Compiler;

use Drupal\dcc_form_alter\Exception\MalformedServiceDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class FormAlterPass.
 *
 * A form alter event subscriber can be a simple event subscriber service, and
 * it will be able to handle all the necessary functionality.
 * But such a service will alter all forms, therefore the developer will have
 * to be careful and check the form id that is provided by the form alter hook.
 * This method is not preferred, because the service will inevitably transform
 * into a cluttered mess.
 *
 * Defining the service with the form_alter tag and a form_id attribute to set
 * the form id is the preferred way, and thus there will be a separate
 * subscriber for every form.
 *
 * @code
 * example_service:
 *   class: ...
 *   tags:
 *     { name: 'form_alter', form_id: 'example_form_id' }
 * @endcode
 *
 * @package Drupal\dcc_form_alter\DependencyInjection\Compiler
 */
class FormAlterPass implements CompilerPassInterface {

  const FORM_ALTER_TAG = 'form_alter';
  const FORM_ID_TAG = 'form_id';

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    // If the developer chooses to define the service with the form_alter tag,
    // then there are a few additional requirements for the service definition.
    foreach ($container->findTaggedServiceIds(self::FORM_ALTER_TAG) as $service_id => $attributes) {
      $definition = $container->getDefinition($service_id);

      $formId = $this->getFormIdFromDefinition($definition);
      if (!$formId) {
        $formAlterTag = self::FORM_ALTER_TAG;
        $formIdAttribute = self::FORM_ID_TAG;
        $message = "The $service_id service needs to add the $formIdAttribute attribute to the $formAlterTag tag.";
        throw new MalformedServiceDefinition($message);
      }
      $definition->addTag('event_subscriber');
      $definition->addMethodCall('setFormId', [$formId]);
    }
  }

  /**
   * Parses the tags of the service definition to find the form id.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   *   The service definition.
   *
   * @return null|string
   *   The form id or NULL.
   */
  private function getFormIdFromDefinition(Definition $definition) {
    $tag = $definition->getTag(self::FORM_ALTER_TAG);
    $formId = NULL;
    foreach ($tag as $tagAttributes) {
      if (array_key_exists(self::FORM_ID_TAG, $tagAttributes)) {
        $formId = $tagAttributes[self::FORM_ID_TAG];
      }
    }

    return $formId;
  }

}
