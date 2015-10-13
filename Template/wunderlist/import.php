<div class="page-header">
    <h2><?= t('Import from Wunderlist') ?></h2>
</div>

<form action="<?= $this->url->href('file', 'save') ?>" method="post" enctype="multipart/form-data">
  <?= $this->form->csrf() ?>
  <label><?= t('Please choose the Wunderlist export file (*.json).') ?></label>
  <input type="file" name="files[]" multiple />
  <div class="form-help"><?= t('Maximum size: ') ?><?= is_integer($max_size) ? $this->text->bytes($max_size) : $max_size ?></div>
  <div class="form-actions">
    <input type="submit" value="<?= t('Import') ?>" class="btn btn-blue"/>
  </div>
</form>