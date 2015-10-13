<?php

namespace Plugin\Wunderlist;

use Core\Translator;
use Core\Plugin\Base;

class Plugin extends Base {
  public function initialize() {
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
    return t('Allow you to import Wunderlist tasks and lists');
  }
  
  public function getPluginHomepage() {
    return 'https://github.com/EpocDotFr/kanboard-wunderlist';
  }
}