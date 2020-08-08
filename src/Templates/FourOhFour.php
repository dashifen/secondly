<?php

namespace Dashifen\Secondly\Templates;

use Dashifen\Secondly\Templates\Framework\AbstractSecondlyTemplate;

class FourOhFour extends AbstractSecondlyTemplate
{
  /**
   * assignContext
   *
   * Returns an array that is assigned to the context property.  Named "assign"
   * to make it more clear that this isn't a typical, public setter.
   *
   * @return array
   */
  protected function getTemplateContext(): array
  {
    return [
      'page' => [
        'title' => 'File Not Found',
      ],
    ];
  }
  
  /**
   * assignTwig
   *
   * Returns the name of our twig template which is assigned to our file
   * property using the setFile method.  Named "assign" to make it more clear
   * that this isn't a typical, public setter.
   *
   * @return string
   */
  protected function getTemplateTwig(): string
  {
    return '404.twig';
  }
}
