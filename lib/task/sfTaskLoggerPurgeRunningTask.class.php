<?php

require_once(dirname(__FILE__). '/sfBaseTaskLoggerTask.class.php');

/**
 * This a task to flag as "not running" a given task.
 *
 * @author Vernet Loïc aka COil <qrf_coil]at[yahoo[dot]fr>
 * @since  1.0.3 - 20 aug 2009
 */

class sfTaskLoggerPurgeRunningTask extends sfBaseTaskLoggerTask
{
  const ERROR_CODE_FAILURE = -1;
  const ERROR_CODE_SUCCESS = 1;

  protected function configure()
  {
    parent::configure();

    $this->addOptions(array(
      new sfCommandOption('task', null, sfCommandOption::PARAMETER_REQUIRED, 'The task to purge'),
    ));
        
    $this->namespace           = 'task-logger';
    $this->name                = 'purge';
    $this->briefDescription    = 'Purge running (or failed) tasks ';
    $this->detailedDescription = <<<EOF
        The [task-logger:purge|INFO] task set the "is_running" flag to "0" for all recorded tasks of a given name.

Call it with:

  [php symfony task-logger:purge --task="my-namespace:my-task"|INFO]

-->
  [php symfony task-logger:purge --task="my-namespace:my-task"|INFO]
EOF;
  }

  /**
   * Advanced check of task parameters.
   */
  protected function checkParameters($arguments = array(), $options = array())
  {
    // check parent parameters
    parent::checkParameters($arguments, $options);

    return true;
  }

  /**
   * Main task process.
   */
  protected function doProcess($arguments = array(), $options = array())
  {
    try
    {
      $purge_count = $this->purge($this->opts['task']);
      $this->task->setCountProcessed($purge_count);
      $this->printAndLog('>  '. $purge_count. ' record(s) updated.');
      $this->task->setErrorCode(self::ERROR_CODE_SUCCESS);
      $this->setOk();
    }
    catch (Exception $e)
    {
      $this->task->setErrorCode(self::ERROR_CODE_FAILURE);
      $this->setNOk($e);
    }
  }

  /**
   * Purge tasks.
   */
  protected function purge($task)
  {
    $method = 'purge'. sfInflector::classify(sfConfig::get('sf_orm'));

    return $this->$method($task);
  }

  /**
   * Purge tasks, Doctrine version.
   */
  protected function purgeDoctrine($task)
  {
    $q = Doctrine_Query::create()
      ->from('tlTask tt')
      ->where('tt.task = ?', $task)
      ->andWhere('tt.is_running = ?', 1)
      ->andWhere('tt.id != ?', $this->task->getId())
    ;

    $results = $q->execute();
    foreach($results as $task) {
      $task->setIsRunning(0);
      $task->setComments(
        $task->getComments() .
        ' Purged by task-logger:purge [task id: ' .
        $this->task->getId() .
        '] on ' . date('Y-m-d') . '.'
      );
      $task->save();
    }

    return $results->count();
  }

  /**
   * Purge tasks, Propel version.
   *
   * @TODO
   */
  protected function purgePropel($task)
  {
    return 0;
  }
}