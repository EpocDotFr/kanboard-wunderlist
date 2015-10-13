<?php if ($this->user->isAdmin()): ?>
<li <?= $this->app->getRouterController() === 'wunderlist' && $this->app->getRouterAction() === 'import' ? 'class="active"' : '' ?>>
    <?= $this->url->link(t('Import from Wunderlist'), 'wunderlist', 'import', array('plugin' => 'wunderlist')) ?>
</li>
<?php endif ?>