<?php

/**
 * This is the main base task of the plugin.
 *
 * Your tasks should extends this one while implementing the 2
 * following functions:
 * - doProcess()
 * - checkParameters() (return true if not neeeded)
 *
 * @see README for more informations
 * @see sfTaskLoggerSampleTask
 *
 * Vernet Loic aka COil <qrf_coil]at[yahoo[dot]fr>
 * @since  V1.0.0 - 7 sept 2009
 */

abstract class sfBaseTaskLoggerTask extends sfBaseTask
{
  protected
    $opts        = array(),
    $opts_string = '',
    $args        = array(),
    $args_string = '',
    $log_output  = '' // The final log output
  ;

  /**
   * Function that execute the main process of the task.
   */
  abstract protected function doProcess($arguments = array(), $options = array());

  /**
   * Create the application context.
   */
  protected function createContext()
  {
    sfContext::createInstance($this->configuration);
  }

  /**
   * Get the full name of the task.
   */
  protected function getTaskName()
  {
    return $this->namespace. ':'. $this->name;
  }

  /**
   * Initialisation the database instance.
   */
  protected function initDatabaseManager()
  {
    return new sfDatabaseManager($this->configuration);
  }

  /**
   * Get date for Propel / Doctrine timestamp.
   */
  protected function getDate()
  {
    return date('Y-m-d H:i:s', time());
  }

  /**
   * Log start of task.
   */
  protected function logStart()
  {
    // Save & print start time
    $message = 'Starting process: '. $this->getDate();
    $this->logSection('START', $message);

    $this->task->setStartedAt($this->getDate());
    $this->task->setIsRunning(true);
    $this->task->save();
  }

  /**
   * Log end of task.
   */
  protected function logEnd()
  {
    // Record all or only when datas where processed
    if (isset($this->opts['only-processed']) && $this->opts['only-processed'])
    {
      // Don't record if there was no activity
      if (($this->task->getCountProcessed() == 0) && ($this->task->getCountNotProcessed() == 0) && $this->task->getIsOk())
      {
        $this->printAndLog(' - Nothing was processed by the task, deleting logs...');
        $this->task->delete();
        @$this->getFilesystem()->remove($this->log_file);

        return false;
      }
    }

    // So we can access the main output in child classes
    $this->log_output = file_get_contents($this->log_file);

    // Record full log in the database if option is set to true
    if ($this->config['log_in_database'])
    {
      $this->task->setLog($this->log_output);
    }

    // Save & print end time
    $message = 'End of process: '. $this->getDate();
    $this->logSection('END', $message);
    $this->task->setEndedAt($this->getDate());
    $this->task->setIsRunning(false);
    $this->logger->shutdown();
    // need to disconnect from dispatcher to avoid symfony to try to write in the closed file -> BUG sf ?
    $this->dispatcher->disconnect('application.log', array($this->logger, 'listenToLogEvent'));

    // Remove the file if option is set to false
    if (!$this->config['log_in_file'])
    {
      $this->task->setLogFile(null);
      @$this->getFilesystem()->remove($this->log_file);
    }
    
    $this->task->save();
  }

  /**
   * Print and log a message.
   *
   * @todo: Alias for $priority levels ?
   * @see sfLogger for priority info
   */
  protected function printAndLog($message, $priority = sfLogger::INFO)
  {
    $this->log($message);
    $this->logger->log($message, $priority);
  }

  /**
   * @see sfTask
   */
  public function logSection($section, $message, $separator = '-', $size = null, $style = 'INFO')
  {
    $print_message = $message ? ($separator.$separator. ' '. $message. ' ') : '';
    $print_message = strlen($print_message) < 60 ? $print_message. str_repeat($separator, 60 - strlen($print_message)) : '';

    parent::logSection($section, $print_message, $size, $style);

    $this->logger->info('>> '. $section. ' >> '. $message);
  }

  /**
   * Save tasks parameters in $this->opt and $this->args arrays.
   */
  protected function setParameters($arguments = array(), $options = array())
  {
    // Options
    foreach ($options as $option => $value)
    {
      if (empty($option))
      {
        continue;
      }

      if (!empty($value))
      {
        $this->opts_string .= "--$option=". $value. ' ';
      }

      $this->opts[$option] = $value;
    }

    // Arguments
    foreach ($arguments as $argument => $value)
    {
      $this->args[$argument] = $value;

      if ($argument != 'task')
      {
        $this->args_string .= $argument. '='. $value. '; ';
      }
    }
  }

  /**
   * Advanced check of task parameters.
   */
  protected function checkParameters($arguments = array(), $options = array())
  {
    // Check if the task is already running
    if (isset($this->opts['check-running']) && $this->opts['check-running'])
    {
      if ($this->countRunningBatch($this->getFullName()))
      {
        throw new InvalidArgumentException(sprintf('The task "%s" is already running.'.
          ' Please wait for its end or if it failed changed its "is_running" flag to 0.'.
          ' Or run the task without the "check-running=1" option.'.
          ' Or run the task ./symfony task-logger:purge --task=%s"', $this->getFullName(), $this->getFullName()
        ));
      }
    }

    // Check if the task is supposed to be run once a day
    if (isset($this->opts['once-by-day']) && $this->opts['once-by-day'])
    {
      if ($this->countDayBatch($this->getFullName()))
      {
        throw new InvalidArgumentException(sprintf('The task "%s" was already run.'.
          ' If you really want to run it, remove the "once-by-day=1" option.', $this->getFullName()
        ));
      }
    }
    
    return true;
  }

  /**
   * Check that the plugin config is correct (no key is missing)
   *
   * @author COil
   * @since  V1.0.2 - 10/08/2010
   */
  protected function checkConfig()
  {
    // Check if the task is already running
    if (!array_key_exists('log_in_file', $this->config))
    {
      throw new InvalidArgumentException('The parameter "log_in_file" is not present in the plugin config wheras is required, check the default config of the plugin');
    }

    // Check if the task is already running
    if (!array_key_exists('log_in_database', $this->config))
    {
      throw new InvalidArgumentException('The parameter "log_in_database" is not present in the plugin config whereas is required, check the default config of the plugin');
    }

    return true;
  }

  /**
   * Default configuration.
   */
  protected function configure()
  {
    parent::configure();

    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'Application used', 'frontend'),
    ));
    
    $this->addOptions(array(
      new sfCommandOption('config', false, sfCommandOption::PARAMETER_REQUIRED, 'The yaml config used by the plugin', 'default'),
      new sfCommandOption('check-running', false, sfCommandOption::PARAMETER_OPTIONAL,  'Check that the same task is not currently running'),
      new sfCommandOption('only-processed', false, sfCommandOption::PARAMETER_OPTIONAL, 'Record into database only if there were things processed by the task.'),
      new sfCommandOption('once-by-day', false, sfCommandOption::PARAMETER_OPTIONAL,    'Check that the task was not already executed once today.'),
    ));
  }

  /**
   * Generate the config file if not present and returns a configuration for
   * a given key or return the default configuration if no specific key
   * was provided.
   *
   * @param $config String Config key
   * @return Array
   */
  public function checkAndGetConfig($config = null)
  {
    $configs = include(sfContext::getInstance()->getConfigCache()->checkConfig('config/plugin_sftl.yml'));

    if (!is_null($config) && !array_key_exists($config, $configs))
    {
      throw new RuntimeException(sprintf('There is no "%s" configuration ! Copy the plugin_sftl.yml into your /config project or application directory, copy/paste the "default" configuration and then remane it to "%s"',
        $config, $config
      ));
    }

    return $configs[$config];
  }

  /**
   * Create log file of task.
   */
  protected function initLogger()
  {
    // Init log file
    $task = $this->getFullName();
    
    // Init record for the task
    $this->task = new tlTask();
    $this->task->setTask($task);
    $this->task->save();
    $this->initLogFile($task);
    
    // Init logger
    $this->logger = new sfFileLogger(new sfEventDispatcher() , array('file' => $this->log_file));

    // Delete old log file deletion (can happen if the table was reinitialized)
    if ($this->old_file_deleted)
    {
      $this->printAndLog('- Previous log file deleted', sfLogger::WARNING);
    }

    // Save the log file path
    $this->logSection('CONFIG', '');
    $this->printAndlog(' - Log file: '. $this->log_file);
    $this->task->setLogFile($this->log_file);

    // Save arguments and options
    $this->task->setArguments($this->args_string);
    $this->task->setOptions($this->opts_string);

    // Save task and log ID
    $this->task->save();
    $this->printAndLog(' - Task ID: '. $this->task->getId());
  }

  /**
   * Init the main log file.
   */
  protected function initLogFile($task)
  {
    $this->log_file = str_replace(':', '-', $task);

    // If option to log the task is set to false then we just temporaly store the
    // log in the log directory and will be deleted afterward
    if ($this->config['log_in_file'])
    {
      $sub_directory =
        'tasks'. DIRECTORY_SEPARATOR.
        $this->getNamespace(). DIRECTORY_SEPARATOR.
        $this->getName(). DIRECTORY_SEPARATOR
      ;
    }
    else
    {
      $sub_directory = '';
    }

    // Then build full path
    $this->log_file = sfConfig::get('sf_log_dir'). DIRECTORY_SEPARATOR.
      $sub_directory.
      str_pad($this->task->getId(), 7, '0', STR_PAD_LEFT). "_". $this->log_file. '.log';

    // Remove old log file if exists
    $this->old_file_deleted = is_file($this->log_file);
    if ($this->old_file_deleted)
    {
      $this->getFilesystem()->remove($this->log_file);
    }
  }

  /**
   * Global process of the task.
   *
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->createContext();

    $this->config = $this->checkAndGetConfig($options['config']);

    $this->checkConfig();

    $this->setParameters($arguments, $options);

    $this->initDatabaseManager();

    $this->checkParameters($arguments, $options);

    $this->initLogger();

    $this->logStart();

    $this->doProcess($arguments, $options);

    $this->logEnd();
  }

  /**
   * Successfull execution of the task.
   */
  protected function setOk()
  {
    $this->logSection('RESULTS', 'OK');
    $this->printAndLog(' - Processed: '. $this->task->getCountProcessed());
    $this->printAndLog(' - Not processed: '. $this->task->getCountNotProcessed());
    $this->printAndLog(' - SUCCESS OF TASK');
    $this->task->setIsOk(true);
  }

  /**
   * Abnormal execution of the task.
   */
  protected function setNOK(Exception $e)
  {
    $this->logSection('RESULTS', 'NOK!');
    $this->printAndLog(' - Processed: '. $this->task->getCountProcessed());
    $this->printAndLog(' - Not processed: '. $this->task->getCountNotProcessed());
    $this->printAndLog('>> /!\ TASK FINISHED ABNORMALLY /!\ ');
    $this->printAndLog(' - Exception: '. $e->getMessage());
    $this->task->setIsOk(false);
  }

  /**
   * Check if a task with the same name is already running.
   */
  protected function countRunningBatch($task)
  {
    $method = 'count'. sfInflector::classify(sfConfig::get('sf_orm')). 'RunningBatch';

    return $this->$method($task);
  }

  /**
   * Check if a task with the same name is already running. (Doctrine version)
   */
  protected function countDoctrineRunningBatch($task)
  {
    $q = Doctrine::getTable('tlTask')->createQuery('t')
      ->where('t.task =  ?', $task)
      ->andWhere('t.is_running =  ?', 1)
    ;

    return $q->count();
  }

  /**
   * Check if a task with the same name is already running. (Propel version)
   */
  protected function countPropelRunningBatch($task)
  {
    $c = new Criteria();
    $c->add(tlTaskPeer::IS_RUNNING, true);
    $c->add(tlTaskPeer::TASK, $task);

    return tlTaskPeer::doCount($c);
  }

  /**
   * Check if a task with was already run today.
   */
  protected function countDayBatch($task)
  {
    $method = 'count'. sfInflector::classify(sfConfig::get('sf_orm')). 'DayBatch';

    return $this->$method($task);
  }

  /**
   * Check if a task with was already run today. (Doctrine version)
   */
  protected function countDoctrineDayBatch($task)
  {
    $q = Doctrine::getTable('tlTask')->createQuery('t')
      ->where('t.task = ?', $task)
      ->andWhere('DATE(t.created_at) = ?', date('Y-m-d'))
    ;

    return $q->count();
  }

  /**
   * Check if a task with was already run today. (Propel version)
   */
  protected function countPropelDayBatch($task)
  {
    $c = new Criteria();
    $c->add(tlTaskPeer::TASK, $task);
    $c->add(tlTaskPeer::CREATED_AT, sprintf('DATE('. $task. ') = \'%s\'', date('Y-m-d')), Criteria::CUSTOM);

    return tlTaskPeer::doCount($c);
  }
}