<?php

require_once(dirname(__FILE__). '/sfBaseTaskLoggerTask.class.php');

/**
 * This a sample task of the sfTaskLoggerPlugin.
 *
 * @author Vernet Loïc aka COil <qrf_coil]at[yahoo[dot]fr>
 * @since  1.0.0 - 7 aug 2009
 */

class sfTaskLoggerSampleTask extends sfBaseTaskLoggerTask
{
  const ERROR_CODE_FAILURE = -1;
  const ERROR_CODE_SUCCESS = 1;

  /**
   * Main task configuration.
   */
  protected function configure()
  {
    parent::configure();

    $this->addArguments(array(
      new sfCommandArgument('arg_1', sfCommandArgument::OPTIONAL, 'Test argument 1', 'arg_1_value'),
      new sfCommandArgument('arg_2', sfCommandArgument::OPTIONAL, 'Test argument 2', 'arg_2_value'),
    ));

    $this->namespace = 'task-logger';
    $this->name      = 'sample';

    $this->briefDescription = 'This is a sample task for the sfTaskLoggerPlugin.';

    $this->detailedDescription = <<<EOF
The task [task-logger:sample|INFO] doesn't do that much.
It logs itself in the database and in the file system:

  [./symfony task-logger:sample --application=backend --env=prod|INFO]
EOF;
  }

  /**
   * Advanced check of task parameters.
   */
  protected function checkParameters($arguments = array(), $options = array())
  {
    // check parent parameters
    parent::checkParameters($arguments, $options);

    // Stupid test
    if ($this->args['arg_1'] != 'arg_1_value')
    {
      throw new InvalidArgumentException('The value for argument 1 is not valid ! Check the help of the task.');
    }

    return true;
  }

  /**
   * Main task process.
   */
  protected function doProcess($arguments = array(), $options = array())
  {
    try
    {
      $this->printAndLog(' - This is a log info !!');
      $rand = rand(1, 5);
      $this->printAndLog(sprintf(' - Sleeping... for... %d seconds...', $rand));
      sleep($rand);
      $this->task->setErrorCode(self::ERROR_CODE_SUCCESS);
      $this->setOk();
    }
    catch (Exception $e)
    {
      $this->task->setErrorCode(self::ERROR_CODE_FAILURE);
      $this->setNOk($e);
    }
  }
}