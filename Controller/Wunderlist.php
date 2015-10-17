<?php

namespace Plugin\Wunderlist\Controller;

use Controller\Base;

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

  /**
   * Wunderlist import page
   *
   * @access public
   */
  public function import() {
    if ($this->request->isPost()) {
      $form_name = 'wunderlist_file';
      
      try {
        print_r($_FILES);
        
        if (!isset($_FILES[$form_name]) or empty($_FILES[$form_name]['name'])) {
          throw new \Exception(t('Please select a file'));
        }
        
        if (empty($_FILES[$form_name]['tmp_name'])) {
          throw new \Exception(t('An error occured while uploading the file'));
        }
        
        if ($_FILES[$form_name]['error'] == UPLOAD_ERR_OK and $_FILES[$form_name]['size'] > 0) {
          $original_filename = $_FILES[$form_name]['name'];
          $uploaded_filename = $_FILES[$form_name]['tmp_name'];

          $this->objectStorage->moveUploadedFile($uploaded_filename, 'tmp-wunderlist-export.json');
        } else {
          throw new \Exception(t('An error occured while uploading the file'));
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