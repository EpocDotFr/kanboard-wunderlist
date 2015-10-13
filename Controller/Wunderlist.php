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
    $this->response->html($this->layout('wunderlist:wunderlist/import', array(
      'title' => t('Settings').' &gt; '.t('Import from Wunderlist'),
      'max_size' => ini_get('upload_max_filesize')
    )));
  }
}