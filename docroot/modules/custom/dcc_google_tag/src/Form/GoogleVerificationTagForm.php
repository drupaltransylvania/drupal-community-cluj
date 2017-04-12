<?php

namespace Drupal\dcc_google_tag\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GoogleVerificationTagForm extends ConfigFormBase {

  const GOOGLE_TAG = 'dcc_google_tag';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * ContentConfigForm constructor
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(\Drupal\Core\Config\ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $key = $this->configFactory->get(self::GOOGLE_TAG . '.settings')->get('dcc_google_tag');

    $form['dcc_google_tag'] = array(
      '#title' => $this->t('Google Verification Tag Hash'),
      '#type' => 'textfield',
      '#name' => 'dcc_google_tag',
      '#require' => TRUE,
      '#default_value' => $key,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::GOOGLE_TAG . '.settings');
    $config->set('dcc_google_tag', $form_state->getValue('dcc_google_tag'));

    $config->save();
    $message = $this->t('The key has been succesfully introduced into configurations');
    drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(){
    return 'dcc_google_tag_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array(self::GOOGLE_TAG . '.settings');
  }
}
