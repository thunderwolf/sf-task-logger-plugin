<?php

/**
 * Function to get the distinct task name list.
 *
 * @author COil
 * @since  V1.0.4 - 22 sept 2011
 */
class PlugintlTaskTableExtended extends PlugintlTaskTable
{    
  /**
   * Return the list of distinct tasks that are in the database.
   */
  public function getTasksList()
  {
    $results = array('' => '');
    $q = $this->createQuery('t')
      ->select('distinct(t.task) INDEXBY t.task')
      ->orderBy('t.task ASC')
    ;

    foreach ($q->fetchArray() as $task)
    {
      $results[] = $task['t.task'];
    }

    return array_combine($results, $results);
  }
}