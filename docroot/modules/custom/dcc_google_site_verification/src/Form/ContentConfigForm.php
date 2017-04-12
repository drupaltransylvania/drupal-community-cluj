<?php

namespace Drupal\dcc_google_verification_content\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentConfigForm extends ConfigFormBase {

  const GOOGLE_VERIFICATION_CONTENT = 'google_verification_content';

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
    $data = $this->configFactory->get(self::GOOGLE_VERIFICATION_CONTENT . '.settings')->getRawData();

    $form['site_verification_content'] = array(
      '#title' => $this->t('Google Site Verification Content'),
      '#type' => 'textfield',
      '#name' => 'site_verification_content',
      '#require' => TRUE,
      '#default_value' => $data['site_verification_content'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::GOOGLE_VERIFICATION_CONTENT . '.settings');
    $config->set('site_verification_content', $form_state->getValue('site_verification_content'));

    $config->save();
    $message = $this->t('The key has been succesfully introduced into configurations');
    drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(){
    return 'google_verification_content_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array(self::GOOGLE_VERIFICATION_CONTENT . '.settings');
  }
}
