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
  const WUNDERLIST_EXPORT_FILE = 'wunderlist_file';
  
  private function layout($template, array $params) {
    $params['board_selector'] = $this->projectPermission->getAllowedProjects($this->userSession->getId());
    $params['values'] = $this->config->getAll();
    $params['errors'] = array();
    $params['config_content_for_layout'] = $this->template->render($template, $params);

    return $this->template->layout('config/layout', $params);
  }
  
  private function handleFile() {
    $uploaded_filename = $_FILES[self::WUNDERLIST_EXPORT_FILE]['tmp_name'];

    if ($this->objectStorage->moveUploadedFile($uploaded_filename, self::WUNDERLIST_EXPORT_FILE.'.json') !== false) {
      $wunderlist_raw_data = $this->objectStorage->get(self::WUNDERLIST_EXPORT_FILE.'.json');
      
      if ($wunderlist_raw_data === false) {
        throw new \Exception(t('Error reading the Wunderlist export file'));
      }

      $wunderlist_json_data = json_decode($wunderlist_raw_data);

      if ($wunderlist_json_data == null) {
        throw new \Exception(t('Error reading the JSON data from the Wunderlist export file').' : '.json_last_error_msg());
      }

      unset($wunderlist_raw_data);
      
      $this->doImport($wunderlist_json_data);
    } else {
      throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
    }
  }
  
  private function doImport($json_data) {
    // TODO
  }

  /**
   * Wunderlist import page
   *
   * @access public
   */
  public function import() {
    if ($this->request->isPost()) {
      try {
        if (!isset($_FILES[self::WUNDERLIST_EXPORT_FILE]) or empty($_FILES[self::WUNDERLIST_EXPORT_FILE]['name'])) {
          throw new \Exception(t('Please select a file'));
        }
        
        if (empty($_FILES[self::WUNDERLIST_EXPORT_FILE]['tmp_name'])) {
          throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
        }
        
        if ($_FILES[self::WUNDERLIST_EXPORT_FILE]['error'] == UPLOAD_ERR_OK and $_FILES[self::WUNDERLIST_EXPORT_FILE]['size'] > 0) {
          $this->handleFile();
        } else {
          throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
        }
      } catch (\Exception $e) {
        ($this->objectStorage->remove(self::WUNDERLIST_EXPORT_FILE));
        $this->session->flashError($e->getMessage());
      }
    }
    
    $this->response->html($this->layout('wunderlist:wunderlist/import', array(
      'title' => t('Settings').' &gt; '.t('Import from Wunderlist'),
      'max_size' => ini_get('upload_max_filesize')
    )));
  }
}