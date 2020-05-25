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

require_once('JSONStream.php');

/**
 * Wunderlist plugin controller
 */
class Wunderlist extends BaseController {
  const WUNDERLIST_EXPORT_FILE = 'wunderlist_file';
  var $tmp_dir;

  private function handleFile() {
    $uploaded_filename = $_FILES[self::WUNDERLIST_EXPORT_FILE]['tmp_name'];
    $uploaded_size = $_FILES[self::WUNDERLIST_EXPORT_FILE]['size'];
    $this->tmp_dir = dirname($_FILES[self::WUNDERLIST_EXPORT_FILE]['tmp_name']);
    $this->tmp_ext_dir = $this->tmp_dir.DIRECTORY_SEPARATOR.'KanboardWunderlist';

    $zip = new \ZipArchive;
    if ($zip->open($uploaded_filename) === TRUE) {
      $uncompressed_size = self::getTotalUncompressedSize($zip);
      $free_space = disk_free_space($this->tmp_dir);

      if ($free_space < $uncompressed_size) {
        throw new \Exception(t('Not enough disk space. Required: %s, Available: %s', round($uncompressed_size / 1000000, 2).'MB', round($free_space / 1000000, 2).'MB'));
      }

      $zip->extractTo($this->tmp_ext_dir);
      $zip->close();
      unlink($uploaded_filename);
    } else {
      throw new \Exception(t('Backup file extraction error'));
    }

    if (!file_exists($this->tmp_ext_dir.DIRECTORY_SEPARATOR.'Tasks.json')) {
      throw new \Exception(t('No valid Tasks.json found'));
    }

    $this->db->startTransaction();
    try {
      $fp = self::fopen_utf8($this->tmp_ext_dir.DIRECTORY_SEPARATOR.'Tasks.json', 'r');
      $json = new \JSONStream(function() use($fp) { return fread($fp, 262144); });
      $json->enterArray();

      while (!$json->isEnded())
      {
        $json->readValue($project);
        try {
          echo ' '; // Keepalive if behind reverse proxy
          $project_id = $this->importList($project);
          foreach ($project['tasks'] as $task) {
            $task_id = $this->importTask($task, $project_id);
          }
        } catch (\Exception $e) {
          $this->flash->failure($e->getMessage());
        }
      }
      $this->db->closeTransaction();
    } catch (\Exception $e) {
      $this->db->cancelTransaction();
      $this->flash->failure($e->getMessage());
    }

    fclose($fp);
    self::deleteDir($this->tmp_ext_dir);
  }

  private function importList($project_data)
  {
    $project_model = $this->projectModel;
    $project_data['title'] = '[Imported] '.$project_data['title'];
    $project = $this->projectModel->getByName($project_data['title']);

    if (empty($project)) {
      $import_project_data = array(
        'name' => $project_data['title'],
        'is_active' => $project_model::ACTIVE,
      );

      if (isset($project_data['public'])) {
        $import_project_data['is_public'] = $project_data['public'] ? $project_model::TYPE_PRIVATE : $project_model::TYPE_TEAM; // Public access
      }

      if (empty($project_id)) $project_id = $project_model->create($import_project_data, $this->userSession->getId(), true);

      if ($project_id > 0) {
        return $project_id;
      } else {
        throw new \Exception(t('An error occured while importing the project %s', $import_project_data['name']));
      }
    } else {
      throw new \Exception(t('Project already exists %s', '(#'.$project['id'].') '.$project_data['title']));
    }
  }

  private function importTask($task, $projectId)
  {
    $red = $this->colorModel->find('red');
    $defaultColor = $this->colorModel->getDefaultColor();

    $task_data = [];

    if (isset($task['createdAt'])) {
      $task_data['date_creation'] = strtotime($task['createdAt']);
    } else {
      $task_data['date_creation'] = time();
    }
    
    $task_data = array(
      'title' => $task['title'],
      'color_id' => $task['starred'] ? $red : $defaultColor,
      'project_id' => $projectId,
      'is_active' => $task['completedAt'] ? 0 : 1,
      'date_completed' => $task['completedAt'] ? strtotime($task['completedAt']) : null,
      'date_due' => isset($task['dueDate']) ? strtotime($task['dueDate']) : null,
      'date_creation' => strtotime($task['createdAt']),
    );

    if (isset($task['notes'][0]['content'])) $task_data['description'] = str_replace('\n', PHP_EOL, str_replace('\r', '', $task['notes'][0]['content']));

    $task_id = $this->taskCreationModel->create($task_data);

    if ($task_id > 0) {
      foreach ($task['subtasks'] as $subtask) {
        $this->importSubtask($subtask, $task_id);
      }
      foreach ($task['comments'] as $comment) {
        $this->importComment($comment, $task_id);
      }
      foreach ($task['files'] as $file) {
        $this->importAttachment($file, $task_id);
      }
      return $task_id;
    } else {
      throw new \Exception(t('An error occured while importing the task %s', $task_to_import->title));
    }
  }

  private function importSubtask($subtask, $taskId)
  {
    $subtask_data = array(
      'title' => $subtask['title'],
      'status' => $subtask['completed'] ? 2 : 0,
      'task_id' => $taskId
    );

    $subtask_id = $this->subtaskModel->create($subtask_data);
    if ($subtask_id == 0) {
      throw new \Exception(t('An error occured while importing the subtask %s', $subtask['title']));
    }
  }

  private function importComment($comment, $taskId)
  {
    $comment_data = array(
      'task_id' => $taskId,
      'comment' => str_replace('\n', PHP_EOL, str_replace('\r', '', $comment['text'].' ('.$comment['author']['name'].')')),
      'user_id' => $this->userSession->getId(),
      'date_creation' => isset($comment['createdAt']) ? strtotime($comment['createdAt']) : time(),
      'reference' => ''
    );
    $comment_model = $this->commentModel;
    $comment_id = $comment_model->create($comment_data);

    if ($comment_id == 0) {
      throw new \Exception(t('An error occured while importing the comment %s', $comment['text']));
    }
  }

  private function importAttachment($file, $taskId)
  {
    if (!file_exists($this->tmp_ext_dir.DIRECTORY_SEPARATOR.$file['filePath'])) {
      throw new \Exception(t('File not found: %s', $this->tmp_ext_dir.DIRECTORY_SEPARATOR.$file['filePath']));
    }
    $file_model = $this->taskFileModel;
    $file_data = array(
                  'name' => $file['fileName'],
                  'tmp_name' => $this->tmp_ext_dir.DIRECTORY_SEPARATOR.$file['filePath'],
                  'size' => $file['fileSize'],
                  'error' => UPLOAD_ERR_OK
    );
    $file_id = $file_model->uploadFile($taskId, $file_data);
  }

  private static function getTotalUncompressedSize(\ZipArchive $zip)
  {
    $totalSize = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $fileStats = $zip->statIndex($i);
      $totalSize += $fileStats['size'];
  }
    return $totalSize;
  }

  public static function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return false;
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            self::deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
  }

  public static function fopen_utf8 ($filename, $mode) {
    $file = @fopen($filename, $mode);
    $bom = fread($file, 3);
    if ($bom != b"\xEF\xBB\xBF") {
      rewind($file, 0);
    }
    return $file;
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
        $this->flash->failure($e->getMessage());
      }
    }

    $this->response->html($this->helper->layout->config('wunderlist:wunderlist/import', array(
      'title' => t('Settings').' &gt; '.t('Import from Wunderlist'),
      'max_size' => ini_get('upload_max_filesize')
    )));
  }
}
