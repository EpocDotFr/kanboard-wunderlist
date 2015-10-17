<?php

namespace Plugin\Wunderlist\Controller;

use Controller\Base;

if (!function_exists('json_last_error_msg')) {
  function json_last_error_msg() {
    static $errors = array(
      JSON_ERROR_NONE             => null,
      JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
      JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
      JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
      JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
      JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );
    $error = json_last_error();
    return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
  }
}

/**
 * Wunderlist plugin controller
 */
class Wunderlist extends Base {
  /**
   * Common layout for config views
   *
   * @access private
   * @param  string    $template   Template name
   * @param  array     $params     Template parameters
   * @return string
   */
  private function layout($template, array $params) {
    $params['board_selector'] = $this->projectPermission->getAllowedProjects($this->userSession->getId());
    $params['values'] = $this->config->getAll();
    $params['errors'] = array();
    $params['config_content_for_layout'] = $this->template->render($template, $params);

    return $this->template->layout('config/layout', $params);
  }
  
  private function doImport($json_data) {
    
  }

  /**
   * Wunderlist import page
   *
   * @access public
   */
  public function import() {
    if ($this->request->isPost()) {
      $form_name = 'wunderlist_file';
      
      try {
        if (!isset($_FILES[$form_name]) or empty($_FILES[$form_name]['name'])) {
          throw new \Exception(t('Please select a file'));
        }
        
        if (empty($_FILES[$form_name]['tmp_name'])) {
          throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
        }
        
        if ($_FILES[$form_name]['error'] == UPLOAD_ERR_OK and $_FILES[$form_name]['size'] > 0) {
          $original_filename = $_FILES[$form_name]['name'];
          $uploaded_filename = $_FILES[$form_name]['tmp_name'];

          if ($this->objectStorage->moveUploadedFile($uploaded_filename, 'tmp-wunderlist-export.json') !== false) {
            $wunderlist_raw_data = $this->objectStorage->get('tmp-wunderlist-export.json');
            
            if ($wunderlist_raw_data === false) {
              throw new \Exception(t('Error reading the Wunderlist export file'));
            }

            $wunderlist_json_data = json_decode($wunderlist_raw_data);

            if ($wunderlist_json_data == null) {
              throw new \Exception(t('Error reading the JSON data from the Wunderlist export file'));
            }

            unset($wunderlist_raw_data);
            
            
          }
        } else {
          throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
        }
      } catch (\Exception $e) {
        $this->session->flashError($e->getMessage());
      }
    }
    
    $this->response->html($this->layout('wunderlist:wunderlist/import', array(
      'title' => t('Settings').' &gt; '.t('Import from Wunderlist'),
      'max_size' => ini_get('upload_max_filesize')
    )));
  }
}