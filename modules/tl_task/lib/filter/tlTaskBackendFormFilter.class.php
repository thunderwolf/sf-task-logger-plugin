<?php

/**
 * Specific filter for the task name.
 *
 * @author COil
 * @since  23 sept 2011
 */

class tlTaskBackendFormFilter extends tlTaskFormFilter
{
  /**
   * Specific widgets and validators configuration.
   */
  public function configure()
  {
    $this->_setWidgets();
    $this->_setValidators();
  }

  /**
   * Set specific widgets.
   */
  protected function _setWidgets()
  {
    $this->widgetSchema['id']   = new sfWidgetFormFilterInput();
    $this->widgetSchema['task'] = new sfWidgetFormSelectForText(
      array(
        'choices' => Doctrine::getTable('tlTask')->getTasksList()
      )
    );
  }

  /**
   * Set specific validators.
   */
  protected function _setValidators()
  {
    $this->validatorSchema['id'] = new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false)));
  }
}