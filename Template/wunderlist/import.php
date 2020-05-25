<div class="page-header">
    <h2><?= t('Import from Wunderlist') ?></h2>
</div>
<?php if (extension_loaded('zip')): ?>
<form action="<?= $this->url->href('wunderlist', 'import', array('plugin' => 'wunderlist')) ?>" method="post" enctype="multipart/form-data" class="listing">
  <?= $this->form->csrf() ?>
  <label><?= t('Please choose the Wunderlist export file (zip):') ?></label>
  <input type="file" name="wunderlist_file" accept="application/zip" />
  <div class="form-help">
    <?= t('Maximum size: ') ?><?= is_integer($max_size) ? $this->text->bytes($max_size) : $max_size ?><br/><br/>
    <i><?= t('Requirments:')?><br/><?= t('Filesize: Double uploaded file')?><br/><?= t('Execution time long enough for uploaded file. Current: %s seconds', ini_get('max_execution_time'))?></i>
  </div>
  <div class="form-actions">
    <input type="submit" value="<?= t('Import') ?>" class="btn btn-blue"/>
  </div>
</form>
<?php else: ?>
<div><?= t('PHP_ZIP extension is required'); ?></div>
<?php endif; ?>