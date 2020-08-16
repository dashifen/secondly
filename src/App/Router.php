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
  
  public const APP_ROUTES = [
    'add-record'
  ];
  
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
   * isAppRoute
   *
   * Returns true if the current route is one of our application routes.
   *
   * @return bool
   */
  public static function isAppRoute(): bool
  {
    $route = untrailingslashit($_SERVER['REQUEST_URI']);
    
    // above, we remove a trailing slash if it exists.  we also want to remove
    // a leading slash if it's present.  so, we check if the first character is
    // a slash, and if it is, we hack it off and keep the rest.
    
    if (substr($route, 0, 1) === '/') {
      $route = substr($route, 1);
    }
    
    return in_array($route, Router::APP_ROUTES);
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
