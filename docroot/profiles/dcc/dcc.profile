<?php

/**
 * @file
 * Enables modules and site configuration for a dcc site installation.
 */

use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Pre-populate the site configuration fields.
 */
function dcc_form_install_configure_form_alter(&$form, $form_state) {
  // Add a placeholder as example that one can choose an arbitrary site name.
  $form['site_information']['site_name']['#attributes']['placeholder'] = t('My site');
  $form['#submit'][] = 'dcc_form_install_configure_submit';
}

/**
 * Submission handler to sync the contact.form.feedback recipient.
 */
function dcc_form_install_configure_submit($form, FormStateInterface $form_state) {
  $site_mail = $form_state->getValue('site_mail');
  ContactForm::load('feedback')
    ->setRecipients([$site_mail])
    ->trustData()
    ->save();
}

/**
 * Implements hook_install_tasks_alter().
 */
function dcc_install_tasks_alter(&$tasks, $install_state) {
  // Unset install_configure_form to add it later.
  $configure_site = $tasks['install_configure_form'];
  unset($tasks['install_configure_form']);

  // Import configurations.
  $tasks['dcc_sync_configurations'] = [
    'display_name' => t('Import configurations'),
    'type' => 'batch',
  ];

  // Installs configuration dependent modules.
  $tasks['dcc_install_modules'] = [
    'display_name' => t('Install configuration dependent modules'),
    'type' => 'batch',
  ];

  // Add install_configure_form to the end.
  $tasks['install_configure_form'] = $configure_site;
}

/**
 * Synchronize configurations.
 */
function dcc_sync_configurations() {
  // Creates StorageComparer.
  $storage_comparer = new StorageComparer(
    Drupal::service('config.storage.sync'),
    Drupal::service('config.storage'),
    Drupal::service('config.manager')
  );
  // Creates ConfigImporter.
  $config_importer = new ConfigImporter(
    $storage_comparer,
    Drupal::service('event_dispatcher'),
    Drupal::service('config.manager'),
    Drupal::service('lock.persistent'),
    Drupal::service('config.typed'),
    Drupal::service('module_handler'),
    Drupal::service('module_installer'),
    Drupal::service('theme_handler'),
    Drupal::service('string_translation')
  );
  // Constructs the batch opperation.
  try {
    $sync_steps = $config_importer->initialize();
    $batch = array(
      'operations' => array(),
      'finished' => array('Drupal\config\Form\ConfigSync', 'finishBatch'),
      'title' => t('Synchronizing configuration'),
      'init_message' => t('Starting configuration synchronization.'),
      'progress_message' => t('Completed @current step of @total.'),
      'error_message' => t('Configuration synchronization has encountered an error.'),
      'file' => drupal_get_path('module', 'config') . '/config.admin.inc',
    );
    foreach ($sync_steps as $sync_step) {
      $batch['operations'][] = array(
        array(
          'Drupal\config\Form\ConfigSync',
          'processBatch'
        ),
        array($config_importer, $sync_step)
      );
    }

    return $batch;
  }
  catch (ConfigImporterException $e) {
  }
}

/**
 * Install configuration dependent modules.
 */
function dcc_install_modules(&$install_state) {
  if (!empty($install_state['profile_info']['config_dependents'])) {
    $modules = $install_state['profile_info']['config_dependents'];
  }
  // If there are no modules define then return an empty array.
  if (empty($modules)) {
    return array();
  }
  $files = system_rebuild_module_data();

  // Always install required modules first. Respect the dependencies between
  // the modules.
  $required = array();
  $non_required = array();

  // Add modules that other modules depend on.
  foreach ($modules as $module) {
    if ($files[$module]->requires) {
      $modules = array_merge($modules, array_keys($files[$module]->requires));
    }
  }
  $modules = array_unique($modules);
  foreach ($modules as $module) {
    if (!empty($files[$module]->info['required'])) {
      $required[$module] = $files[$module]->sort;
    }
    else {
      $non_required[$module] = $files[$module]->sort;
    }
  }
  arsort($required);
  arsort($non_required);

  $operations = array();
  foreach ($required + $non_required as $module => $weight) {
    $operations[] = array(
      '_install_module_batch',
      array($module, $files[$module]->info['name'])
    );
  }
  $batch = array(
    'operations' => $operations,
    'title' => t('Installing @drupal', array('@drupal' => drupal_install_profile_distribution_name())),
    'error_message' => t('The installation has encountered an error.'),
  );
  return $batch;
}
