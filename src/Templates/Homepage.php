<?php

namespace Dashifen\Secondly\Templates;

use Dashifen\Secondly\Templates\Framework\AbstractSecondlyTemplate;

class Homepage extends AbstractSecondlyTemplate
{
  /**
   * assignTwig
   *
   * Returns the name of our twig template which is assigned to our file
   * property using the setFile method.  Named "assign" to make it more clear
   * that this isn't a typical, public setter.
   *
   * @return string
   */
  protected function assignTwig(): string
  {
    return 'homepage.twig';
  }
  
  /**
   * assignContext
   *
   * Returns an array that is assigned to the context property.  Named "assign"
   * to make it more clear that this isn't a typical, public setter.
   *
   * @return array
   */
  protected function assignContext(): array
  {
    return [];
  }
  
}
