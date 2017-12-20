<?php

namespace Kanboard\Plugin\Wunderlist;

use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;

class Plugin extends Base {
  public function initialize() {
    $this->applicationAccessMap->add('Wunderlist', '*', Role::APP_ADMIN);

    $this->template->hook->attach('template:config:sidebar', 'wunderlist:config/sidebar');
  }

  public function onStartup() {
      Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
  }

  public function getPluginName() {
    return 'Wunderlist';
  }

  public function getPluginAuthor() {
    return 'Maxime "Epoc" G.';
  }

  public function getPluginVersion() {
    return '1.0.5';
  }

  public function getPluginDescription() {
    return t('Allow you to import Wunderlist tasks and lists by uploading an export file');
  }

  public function getPluginHomepage() {
    return 'https://github.com/EpocDotFr/kanboard-wunderlist';
  }

  public function getCompatibleVersion() {
    return '>=1.0.48';
  }
}
