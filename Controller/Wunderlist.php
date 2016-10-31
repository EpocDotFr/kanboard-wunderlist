<?php

namespace Kanboard\Plugin\Wunderlist\Controller;

use Kanboard\Controller\BaseController;

if (!function_exists('json_last_error_msg')) {
  function json_last_error_msg() {
    static $errors = array(
      JSON_ERROR_NONE             => null,
      JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
      JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
      JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
      JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
      JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );
    $error = json_last_error();
    return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
  }
}

/**
 * Wunderlist plugin controller
 */
class Wunderlist extends BaseController {
  const WUNDERLIST_EXPORT_FILE = 'wunderlist_file';
  
  private function handleFile() {
    $uploaded_filename = $_FILES[self::WUNDERLIST_EXPORT_FILE]['tmp_name'];

    if ($this->objectStorage->moveUploadedFile($uploaded_filename, self::WUNDERLIST_EXPORT_FILE.'.json') !== false) {
      $wunderlist_raw_data = $this->objectStorage->get(self::WUNDERLIST_EXPORT_FILE.'.json');
      
      if ($wunderlist_raw_data === false) {
        throw new \Exception(t('Error reading the Wunderlist export file'));
      }

      $wunderlist_json_data = json_decode($wunderlist_raw_data);

      if ($wunderlist_json_data == null) {
        throw new \Exception(t('Error reading the JSON data from the Wunderlist export file').' : '.json_last_error_msg());
      }

      unset($wunderlist_raw_data);
      
      $this->doImport($wunderlist_json_data);
    } else {
      throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
    }
  }
  
  private function doImport($json_data) {
    $projects = array();
    $tasks = array();

    $this->db->startTransaction();

    // Lists
    foreach ($json_data->data->lists as $list_to_import) {
      $project_data = array(
        'name' => $list_to_import->title,
        'is_active' => 1,
        'is_public' => $list_to_import->public ? 1 : 0 // Public access
      );

      $project_id = $this->project->create($project_data, $this->userSession->getId(), true);
      
      if ($project_id > 0) {
        $projects[$list_to_import->id] = $project_id;
      } else {
        $this->db->cancelTransaction();
        throw new \Exception(t('An error occured while importing the project %s', $list_to_import->title));
      }
    }

    // Tasks
    foreach ($json_data->data->tasks as $task_to_import) {
      $task_data = array(
        'title' => $task_to_import->title,
        'date_creation' => date_create($task_to_import->created_at)->getTimestamp(),
        'date_modification' => date_create()->getTimestamp(),
        'color_id' => $task_to_import->starred ? $this->color->find('red') : $this->color->getDefaultColor(),
        'project_id' => $projects[$task_to_import->list_id],
        'is_active' => $task_to_import->completed ? 0 : 1,
        'date_completed' => $task_to_import->completed ? date_create($task_to_import->completed_at)->getTimestamp() : null,
        'date_due' => isset($task_to_import->due_date) ? date_create($task_to_import->due_date)->getTimestamp() : null
      );

      // Description (note)
      foreach ($json_data->data->notes as $note_to_import) {
        if ($note_to_import->task_id == $task_to_import->id) {
          $task_data['description'] = str_replace('\n', PHP_EOL, $note_to_import->content);

          break;
        }
      }
      
      $task_id = $this->taskCreation->create($task_data);
      
      if ($task_id > 0) {
        $tasks[$task_to_import->id] = $task_id;
      } else {
        $this->db->cancelTransaction();
        throw new \Exception(t('An error occured while importing the task %s', $task_to_import->title));
      }
    }
    
    // Sub-tasks
    foreach ($json_data->data->subtasks as $subtasks_to_import) {
      $subtask_data = array(
        'title' => $subtasks_to_import->title,
        'status' => $subtasks_to_import->completed ? 2 : 0,
        'task_id' => $tasks[$subtasks_to_import->task_id]
      );
      
      if ($this->subtask->create($subtask_data) == 0) {
        $this->db->cancelTransaction();
        throw new \Exception(t('An error occured while importing the subtask %s', $subtasks_to_import->title));
      }
    }

    $this->db->closeTransaction();
  }

  /**
   * Wunderlist import page
   *
   * @access public
   */
  public function import() {
    if ($this->request->isPost()) {
      try {
        if (!isset($_FILES[self::WUNDERLIST_EXPORT_FILE]) or empty($_FILES[self::WUNDERLIST_EXPORT_FILE]['name'])) {
          throw new \Exception(t('Please select a file'));
        }
        
        if (empty($_FILES[self::WUNDERLIST_EXPORT_FILE]['tmp_name'])) {
          throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
        }
        
        if ($_FILES[self::WUNDERLIST_EXPORT_FILE]['error'] == UPLOAD_ERR_OK and $_FILES[self::WUNDERLIST_EXPORT_FILE]['size'] > 0) {
          $this->handleFile();
        } else {
          throw new \Exception(t('An error occured while uploading the Wunderlist export file'));
        }

        $this->flash->success(t('Wunderlist file imported successfuly'));
      } catch (\Exception $e) {
        $this->objectStorage->remove(self::WUNDERLIST_EXPORT_FILE);
        $this->flash->failure($e->getMessage());
      }
    }
    
    $this->response->html($this->helper->layout->config('wunderlist:wunderlist/import', array(
      'title' => t('Settings').' &gt; '.t('Import from Wunderlist'),
      'max_size' => ini_get('upload_max_filesize')
    )));
  }
}
