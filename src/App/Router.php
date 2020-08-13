<?php

namespace Dashifen\Secondly\App;

use Dashifen\Secondly\Templates\Homepage;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\WPTemplates\TemplateInterface;
use Dashifen\WPHandler\Traits\CaseChangingTrait;

/**
 * Class Router
 *
 * @package Dashifen\Secondly
 */
class Router
{
  use CaseChangingTrait;
  
  protected Controller $controller;
  
  /**
   * Router constructor.
   *
   * @param Controller $controller
   */
  public function __construct(Controller $controller) {
    $this->controller = $controller;
  }
  
  /**
   * getTemplate
   *
   * Uses the current route to return the appropriate template object.
   *
   * @return TemplateInterface
   */
  public function getTemplate(): TemplateInterface
  {
    if ($_SERVER['REQUEST_URI'] === '/') {
      
      // if we're on the homepage, the algorithm we use for everything else
      // doesn't work.  so, for it we'll just specify the homepage template
      // directly.
      
      $object = Homepage::class;
    } else {
      
      // otherwise, our route matches the folder structure that we've created
      // within the \Dashifen\Secondly\Templates namespace.  so, we can
      // construct template object names from the parts of our route.  only
      // thing we have to do here is make our route's parts studly (to match
      // the PHP class naming standards) and then add in the templates'
      // namespace.
      
      $nonBlankParts = array_filter(explode('/', $_SERVER['REQUEST_URI']));
      $partial = array_map(fn(string $x): string => self::camelToStudlyCase($x), $nonBlankParts);
      $complete = array_merge(['Dashifen','Secondly','Templates'], $partial);
      $object = join('\\', $complete);
    }

    return $this->controller->getTemplate($object);
  }
}
