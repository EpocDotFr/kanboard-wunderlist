<li <?= $this->app->getRouterAction() === 'import' && $this->app->getRouterController() === 'wunderlist' ? 'class="active"' : '' ?>>
    <?= $this->url->link(t('Import from Wunderlist'), 'wunderlist', 'import') ?>
</li>