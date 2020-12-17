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
    $batch = array(
      'title' => t('Importing CSV ...'),
      'operations' => array(),
      'init_message' => t('Commencing'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message' => t('An error occurred during processing'),
      'finished' => 'csvimport_import_finished',
    ) ;

    if ( $handle = fopen($form_state->getValue('csv_movies_file'), 'r') ) {
      $batch['operations'][] = array('_csvimport_remember_filename', array( $form_state->getValue('csv_movies_file') ) ) ;
      $line_count = 1 ;
      $first = TRUE ;
      $line = fgetcsv($handle, 4096);
      while ( $line = fgetcsv($handle, 4096) ) {
        /**
         * we use base64_encode to ensure we don't overload the batch
         * processor by stuffing complex objects into it
         */
        $batch['operations'][] = array('_csvimport_import_line', array(array_map('base64_encode', $line)));
      }
      fclose($handle);
    } // we caught this in csvimport_form_validate()

    batch_set($batch);
  }


  /**
   * Remember the uploaded CSV filename
   *
   *
   * @TODO is there a better way to pass a value from inception of the
   * batch to the finished function?
   */
  function _csvimport_remember_filename($filename, &$context) {
    $context['results']['uploaded_filename'] = $filename ;
  }

  /**
   * Process a single line.
   */
  function _csvimport_import_line($line, &$context) {
    $context['results']['rows_imported']++;
    $line = $cleaned_line = array_map('base64_decode', $line);

    /**
     * Simply show the import row count.
     */
    $context['message'] = t('Importing row !c', array( '!c' => $context['results']['rows_imported'] ));

    /**
     * Alternatively, our example CSV happens to have the title in the
     * third column, so we can uncomment this line to display "Importing
     * Blahblah" as each row is parsed.
     *
     * You can comment out the line above if you uncomment this one.
     */
    $context['message'] = t('Importing %title', array('%title' => $line[2]));

    /**
     * In order to slow importing and debug better, we can uncomment
     * this line to make each import slightly slower.
     */
    usleep(2500);

    /**
     * If the first two columns in the row are "ROW", "FAILS" then we
     * will add that row to the CSV we'll return to the importing person
     * after the import completes.
     */
    if ( $line[1] == 'ROW' && $line[2] == 'FAILS' ) {
      $context['results']['failed_rows'][] = $line ;
    }
  }

}
