<?php

namespace Kanboard\Plugin\Wunderlist;

use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;

class Plugin extends Base {
  public function initialize() {
    $this->applicationAccessMap->add('Wunderlist', '*', Role::APP_ADMIN);
    
    $this->template->hook->attach('template:config:sidebar', 'wunderlist:config/sidebar');
    
    $this->on('app.bootstrap', function($container) {
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
    return '1.0.3';
  }
  
  public function getPluginDescription() {
    return t('Allow you to import Wunderlist tasks and lists by uploading an export file');
  }
  
  public function getPluginHomepage() {
    return 'https://github.com/EpocDotFr/kanboard-wunderlist';
  }
}