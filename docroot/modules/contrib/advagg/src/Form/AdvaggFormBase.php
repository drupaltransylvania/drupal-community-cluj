<?php

namespace Drupal\advagg\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * View AdvAgg information for this site.
 */
abstract class AdvaggFormBase extends ConfigFormBase {

  /**
   * The AdvAgg file status state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggFiles;

  /**
   * The AdvAgg aggregates state information storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $advaggAggregates;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $advagg_files
   *   The AdvAgg file status state information storage service.
   * @param \Drupal\Core\State\StateInterface $advagg_aggregates
   *   The AdvAgg aggregate state information storage service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $advagg_files, StateInterface $advagg_aggregates, RequestStack $request_stack) {
    parent::__construct($config_factory);
    $this->advaggFiles = $advagg_files;
    $this->advaggAggregates = $advagg_aggregates;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state.advagg.files'),
      $container->get('state.advagg.aggregates'),
      $container->get('request_stack')
    );
  }

  /**
   * Checks if the form was submitted by AJAX.
   *
   * @return bool
   *   TRUE if the form was submitted via AJAX, otherwise FALSE.
   */
  protected function isAjax() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->query->has(FormBuilderInterface::AJAX_FORM_REQUEST)) {
      return TRUE;
    }
    return FALSE;
  }

}
