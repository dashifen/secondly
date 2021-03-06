<?php

namespace Dashifen\Secondly\App;

use Dashifen\Secondly\Theme;
use League\Container\Container;
use Dashifen\Debugging\DebuggingTrait;
use Latitude\QueryBuilder\QueryFactory;
use League\Container\ReflectionContainer;
use Dashifen\WPTemplates\TemplateInterface;
use Dashifen\Secondly\Templates\FourOhFour;
use Latitude\QueryBuilder\Engine\MySqlEngine;
use League\Container\Exception\NotFoundException;
use Dashifen\Secondly\App\Services\RecordDataHelper;
use Dashifen\Secondly\Agents\Collection\Factory\SecondlyAgentCollectionFactory;

class Controller
{
  use DebuggingTrait;
  
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
    self::$container->defaultToShared();
    self::$container->delegate(new ReflectionContainer());
    self::$container->add(Controller::class);
  
    self::$container->add(SecondlyAgentCollectionFactory::class)
      ->addMethodCall('registerAgents');
    
    self::$container->add(Theme::class)
      ->addMethodCall(
        'setAgentCollection',
        [
          self::$container->get(SecondlyAgentCollectionFactory::class),
        ]
      );
    
    // our router will need a reference to this object.  this is so that it can
    // access the getTemplate() method we define below.
    
    self::$container->add(Router::class)->addArgument($this);
  }
  
  /**
   * getTheme
   *
   * Returns an instance of the Theme object.
   *
   * @return Theme
   */
  public function getTheme(): Theme
  {
    return self::$container->get(Theme::class);
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
      
      // the Theme parameter to an AbstractSecondlyTemplate's constructor is
      // auto-wired into place.  and, because we defaulted to shared objects
      // when we configured our DI container above, we know that the template
      // gets the same Theme object as everything else does.
  
      $template = self::$container->get($template);
    } catch (NotFoundException $e) {
      header("HTTP/1.0 404 Not Found");
      $template = self::$container->get(FourOhFour::class);
    }
    
    return $template;
  }
  
  /**
   * getRouter
   *
   * Returns an instance of the Router object.
   *
   * @return Router
   */
  public function getRouter(): Router
  {
    return self::$container->get(Router::class);
  }
}
