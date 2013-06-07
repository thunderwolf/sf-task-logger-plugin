<?php

/**
 * Widget that displays a select for a text column.
 *
 * @package    sfTaskLoggerPlugin
 * @subpackage widget
 * @since      V1.0.4 - 23 sept 2011
 */

class sfWidgetFormSelectForText extends sfWidgetFormSelect
{
  /**
   * @see sfWidgetFormSelect
   */
  public function render($name, $value = null, $attributes = array(), $errors = array())
  {
    return parent::render($name. '[text]', $value, $attributes = array(), $errors = array());
  }
}