<?php

namespace Plugin\Wunderlist\Controller;

use Controller\Base;

/**
 * Wunderlist plugin controller
 */
class Wunderlist extends Base {
  /**
   * Wunderlist plugin index page
   *
   * Does nothing but redirects to the import action
   *
   * @access public
   */
  public function index() {
      $project = $this->getProject();
      $this->response->html($this->projectLayout('budget:budget/index', array(
          'daily_budget' => $this->budget->getDailyBudgetBreakdown($project['id']),
          'project' => $project,
          'title' => t('Budget')
      ), 'budget:budget/sidebar'));
  }
  
  /**
   * Wunderlist import page
   *
   * @access public
   */
  public function import() {
    
  }
}