<?php

namespace Drupal\advagg_validator\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configure form for CSSHint validation of CSS files.
 */
class CssLintForm extends BaseValidatorForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advagg_validator_csslint';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::generateForm('css');
    if (file_exists(DRUPAL_ROOT . '/.csslintrc')) {
      $json_string = file_get_contents(DRUPAL_ROOT . '/.csslintrc');
      if (!empty($json_string)) {
        $json_data = json_decode($json_string, TRUE);
      }
    }
    $form['#attached']['library'][] = 'advagg_validator/csslint';
    if (!empty($json_data)) {
      $form['#attached']['drupalSettings']['csslint'] = [
        'rules' => $json_data,
      ];
    }
    $form = parent::buildForm($form, $form_state);
    unset($form['actions']);
    return $form;
  }

}
