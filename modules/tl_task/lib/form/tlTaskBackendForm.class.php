<?php

/**
 * Specific form for backend application.
 *
 * @author COil
 * @since  5 aug 2010
 */

class tlTaskBackendForm extends tlTaskForm
{

  /**
   * Configuration du formulaire.
   *
   * @since 6 oct 2009
   */
  public function configure()
  {
    $this->_setWidgets();
  }

  protected function _setWidgets()
  {
    $this->widgetSchema['task']->setAttribute('size', 100);
    $this->widgetSchema['log_file']->setAttribute('size', 100);
    $this->widgetSchema['options']->setAttribute('size', 100);
    $this->widgetSchema['arguments']->setAttribute('size', 100);
    $this->widgetSchema['log']->setAttribute('cols', 100);
    $this->widgetSchema['comments']->setAttribute('cols', 100);
  }
}