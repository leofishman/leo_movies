<?php

namespace Drupal\leo_movies\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Load csv movies files to import.
 */
class ImportForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leo_movies_import_movies';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $form = array(
      '#attributes' => array('enctype' => 'multipart/form-data'),
    );

    $validators = array(
      'file_validate_extensions' => array('csv'),
    );

    $form['csv_movies_file'] = array(
      '#type' => 'managed_file',
      '#name' => 'csv_movies_file',
      '#title' => t('File *'),
      '#size' => 20,
      '#description' => t('CSV format only'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://movies_files/',
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fid = reset($form_state->getValue('csv_movies_file'));
    $file = File::load($fid);
    $size= $file->getSize();
    // Check that file has at least 20 characters (movies id,year,title)
    if ($size < 20) {
      $form_state->setErrorByName('csv_movies_file', $this->t('File empty'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ksm($form_state->getValue('csv_movies_file'));
  }

}
