<?php

namespace Kanboard\Plugin\Wunderlist;

use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;

class Plugin extends Base {
  public function initialize() {
    $this->acl->extend('admin_acl', array('wunderlist' => '*'));
    
    $this->template->hook->attach('template:config:sidebar', 'wunderlist:config/sidebar');
    
    $this->on('session.bootstrap', function($container) {
      Translator::load($container['config']->getCurrentLanguage(), __DIR__.'/Locale');
    });
  }
  
  public function getPluginName() {
    return 'Wunderlist';
  }
  
  public function getPluginAuthor() {
    return 'Maxime "Epoc" G.';
  }
  
  public function getPluginVersion() {
    return 'master';
  }
  
  public function getPluginDescription() {
    return t('Allow you to import Wunderlist tasks and lists by uploading an export file');
  }
  
  public function getPluginHomepage() {
    return 'https://github.com/EpocDotFr/kanboard-wunderlist';
  }
}