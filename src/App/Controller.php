<?php

namespace Dashifen\Secondly\App;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Dashifen\WPTemplates\TemplateInterface;
use Dashifen\Secondly\Templates\FourOhFour;
use League\Container\Exception\NotFoundException;

class Controller
{
  private static ?Container $container = null;
  
  /**
   * Controller constructor.
   *
   * @throws ControllerException
   */
  public function __construct()
  {
    if (self::$container === null) {
      
      // we only want to configure out dependency injection container once.
      // so, if we get here and it's null, then we call its configuration
      // method below.  because it's a static property, it'll remain configured
      // even if we accidentally re-instantiate this class.
      
      $this->configureContainer();
    }
  }
  
  /**
   * configureContainer
   *
   * Configures the private, static League/Container property.
   *
   * @return void
   * @throws ControllerException
   */
  private function configureContainer(): void
  {
    if (!self::$container === null) {
      
      // this shouldn't happen (because we make the opposite test in the
      // constructor and that should be the only way to get here), but just in
      // case, we'll throw an exception if we get here inappropriately.
      
      throw new ControllerException(
        'Attempt to reconfigure container.',
        ControllerException::RECONFIGURATION
      );
    }
    
    // now that all that unpleasantness is behind us, let's configure the
    // container.  we'll instantiate the League/Container object and then
    // delegate anything that we don't configure here to its auto-wiring
    // capabilities.  for information on those capabilities, see:
    // https://container.thephpleague.com/3.x/auto-wiring.
    
    self::$container = new Container();
    self::$container->delegate(new ReflectionContainer());
    self::$container->share(Controller::class);
    
    // our router will need a reference to this object.  this is so that it can
    // access the getTemplate() method we define below.
    
    self::$container->share(Router::class)->addArgument($this);
  }
  
  /**
   * getTemplate
   *
   * Given the name of a Template object, constructs and returns it.
   *
   * @param string $template
   *
   * @return TemplateInterface
   */
  public function getTemplate(string $template): TemplateInterface
  {
    try {
      $template = self::$container->get($template);
    } catch (NotFoundException $e) {
      $template = self::$container->get(FourOhFour::class);
    }
    
    return $template;
  }
  
  /**
   * getRouter
   *
   * Returns the Router object.
   *
   * @return Router
   */
  public function getRouter(): Router
  {
    return self::$container->get(Router::class);
  }
}
