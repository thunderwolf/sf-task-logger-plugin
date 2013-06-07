sfTaskLoggerPlugin
------------------

The **sfTaskLoggerPlugin** allows you to run custom tasks and store the results.
Results are stored in the database in a specific table and/or in a log file. Each task
has its own log file, which is stored in a specific directory depending on its
namespace and name. (`/log/tasks/:TASK_NAMESPACE/:TASK_NAME`). It allows you
to have a clean log history of all the CRON executed by your symfony project.

The database record stores the following informations:

  * Name of the task
  * List of arguments of the task
  * List of options of the task
  * Count of processed records
  * Count of NOT processed records
  * Flag that tells if task is actually running
  * Last record Id fully processed without error
  * Process start time
  * Process end time
  * Flag that tells if task finished without error
  * An error code for success or failure of the task
  * The full console output of the task (optional)
  * The log file path associated to the task
  * Additional admin comments about the task and its results (can be modified with the admin generator module)

The plugin base task has several useful options:

  * `config`: The YAML config used by the plugin (explained in the next section)
  * `check-running`: Check that the task is not currently running
  * `only-processed`: Record into log or database only if there were things processed by the task
  * `once-by-day`: Check that the task was not already executed once today

>**Note**
>The plugin is both [Doctrine](http://www.doctrine-project.org) and
>[Propel](http://propel.phpdb.org) friendly, it you are using Doctrine, the
> `/lib/config/doctrine/schema.yml` will be used whereas using Propel
> `the /lib/config/schema.yml` will be used.
>(I am sorry but I didn't test the Propel version with the last 1.4 package, so
>feel free to report me issues if you use it with Propel. Neither the admin 
>generator module is available.)

Installation
============

 * Install the plugin:

        $ symfony plugin:install sfTaskLoggerPlugin

   (download it and unzip in your `/plugins` directory or use svn `http://svn.symfony-project.com/plugins/sfTaskLoggerPlugin/tags/sfTaskLoggerPlugin_1_0_4/`)

 * Build the new plugin table and associated models:

        $ ./symfony doctrine:build --all-classes --db --and-load --env=dev
(or launch each "build" task individually)

Or for Propel:

        $ symfony propel:build-all-load

>**Note**
>At this point you should have
>
>  * A new table called `tl_tasks` in your database
>  * A new set of model classes in `lib/model/sfTaskLoggerPlugin` or `lib/model/doctrine/sfTaskLoggerPlugin`

 * Clear you cache

        $ symfony cc

Configuration
=============

The plugin comes with a *base* task class which is named `sfBaseTaskLoggerTask`
Therefore your tasks must extend this one. Because there is no auto-loading at
the task level, one must include it manually:

    [php]
    require_once(dirname(__FILE__). '/sfBaseTaskLoggerTask.class.php');

>**Note**
>Of course you will have to change this path depending on where is located your
>task. For example if it is located in the `/lib/task` folder of your project, use
>the following code.
>
>Generally you will want to extend all your tasks with a custom project task
>so all of them will benefit from its generic methods, arguments or options
>(thus, it must stay abstract). It would looks like this:

    [php]
    /**
     * This the base task for all tasks of myProject.
     *
     * @author COil
     * @since  01/09/2010
     */

    require_once(dirname(__FILE__). '/../../plugins/sfTaskLoggerPlugin/lib/task/sfBaseTaskLoggerTask.class.php');

    abstract class mySuperBaseTask extends sfBaseTaskLoggerTask
    {
      /**
       * This function is callable by all the project tasks.
       */
      public function superFunction()
      {
      }
    }

Your final task should extends this custom task and look like this:

    [php]
    /**
     * This a custom task
     *
     * @author Vernet Loïc aka COil <qrf_coil]at[yahoo[dot]fr>
     * @since  1.0.0 - 7 aug 2009
     */
    class sfMyTask extends mySuperBaseTask
    {
      // check the following section for functions to implement
    }

----

Moreover the plugin comes with a *default* YAML configuration file, this file allows
you to tell if you want to log into the database, a file or both:

 * Copy the `/plugins/sfTaskLoggerPlugin/config/plugin_sftl.yml` into the `config` folder
   of your application. Then this file will be used.

 * Now, you can add your own configurations. (copy paste the default one and rename the
   key of the configuration) You should keep the `default` one which is the basic
   configuration provided by the plugin.

 * To use a specific config for a task, pass a `config` option to the task. (--config=myConfig)
   where `myConfig` is the key of your configuration. If the config is invalid
   an alert will be raised.

Usage
=====

In your task (like `sfMyTask` above):

  * 1 - Implement the `configure()` method as you would do with a standard task:
(don't forget to call the parent method to include generic parameters and options)

        [php]
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

            $this->namespace = 'sf_task_logger';
            $this->name      = 'sample';

            $this->briefDescription = 'This is a sample task !';

            $this->detailedDescription = <<<EOF
        The task [sf_task_logger:sample|INFO] doesn't do that much.
        It logs itself in the database and in the file system:

          [./symfony sf_task_logger:sample --env=prod|INFO]
        EOF;
          }

Now there are 2 specific methods to implement:

  * 2 - `checkParameters()`
(don't forget to call the parent method too)

        [php]
        /**
         * Advanced check of task parameters.
         */
        protected function checkParameters($arguments = array(), $options = array())
        {
          parent::checkParameters($arguments, $options);

          if ($this->args['arg_1'] != 'arg_1_value')
          {
            throw new InvalidArgumentException('The value for argument 1 is not valid ! Check the help of the task.');
          }

          return true;
        }

>**Note**
>This method can be useful if you have advanced controls to do on task parameters or
>arguments. Raise an `InvalidArgumentException` if there is at least an invalid parameter.
>Don't forget to call at first the parent function so generic parameters can be
>checked at the base task level. (or at your project base task level)

  * 4 - `doProcess()`

        [php]
        /**
         * Main task process.
         */
        protected function doProcess($arguments = array(), $options = array())
        {
          try
          {
            $this->printAndLog(' - This is a log info !!');
            $this->task->setErrorCode(self::ERROR_CODE_SUCCESS);
            $this->setOk();
          }
          catch (Exception $e)
          {
            $this->task->setErrorCode(self::ERROR_CODE_FAILURE);
            $this->setNOk($e);
          }
        }

>**Note**
>This is the main method of your task process. `$this->task` is the database
>object that will be saved. As you can see the `setOk()` and `setNOk` methods
>allow to set the status flag automatically depending on the success or
>failure of the task. We also set a status code that will give more details
>than success or not about how ended the process.
>
>If you want to resume a batch process from a given id, you can use the `last_id_processed`
>field for this purpose.

Finally, if you want more control on the task process you can also re-implement the `execute()`
method of the base class which is responsible for calling all others sub functions:

    [php]
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


Admin generator module
======================

The plugin comes with a handy admin generator module (Doctrine, symfony >= 1.2)
in order to manage the main `tl_task` table.

Add the module `tl_task` to the enabled module list of the `settings.yml` file
of your application.

        enabled_modules:  [default, tl_task]

After having generated all the model files of the plugin, edit the
`/lib/model/doctrine/sfTaskLoggerPlugin/tlTaskTable.class.php` class and make it
extend the class `PlugintlTaskTableExtended`:

    [php]
    /**
     * tlTaskTable
     *
     * This class has been auto-generated by the Doctrine ORM Framework
     */
    class tlTaskTable extends PlugintlTaskTableExtended
    {
        /**
         * Returns an instance of this class.
         *
         * @return object tlTaskTable
         */
        public static function getInstance()
        {
            return Doctrine_Core::getTable('tlTask');
        }
    }

Then call the module, `you_backend.php/tl_task` ! That's it ! ;)

>**Note**
>The plugin comes with a route `tl_task` for this admin generator module.

Notes
=====

>**Note**
>The plugin is bundled with a sample task: `/lib/task/sfTaskLoggerSampleTask.class.php`
>which can be run with the following command (replace "frontend" with a valid application
>name of your project and "dev" with a valid environment):
>
>     > ./symfony task-logger:sample --application="frontend" --env="dev"
>
>And also with a task: `/lib/task/sfTaskLoggerPurgeRunningTask.class.php`
>to purge tasks who ended with a fatal error and which stayed
>with the running flag to "ON":
>
>     > ./symfony task-logger:purge --task="myProject:myTask" --application=backend --env="dev"
>

Console output
==============

You may want to have your console output disabled when running cron tasks, for
example because of some server related configuration - in this case, add the
__--quiet__ option to your cli command line:

     ./symfony task-logger:sample --application=frontend --env="prod" --quiet

TODO
====

  * V1.1.0: Advanced features to keep a state of "processed objects"
  * V1.0.5: Test the Propel version

Support
=======

Send me an email or report bugs on the symfony TRAC, I could also answer if you ask on the
symfony mailing list.

Changelog
=========

(check the changelog tab)

----

See you. [COil](http://www.strangebuzz.com) :)

----

This plugin is sponsored by [SQL Technologies](http://www.sqltechnologies.com)

 ![SQL Technologies](http://www.php-debug.com/images/sql.gif)