<?php
/**
 * @noinspection PhpDocFieldTypeMismatchInspection
 */

namespace Dashifen\Secondly;

use Dashifen\Secondly\App\Router;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\Secondly\App\Services\RecordDataHelper;
use Dashifen\WPHandler\Traits\OptionsManagementTrait;
use Dashifen\WPHandler\Hooks\Factory\HookFactoryInterface;
use Dashifen\WPHandler\Handlers\Themes\AbstractThemeHandler;
use Dashifen\Secondly\Agents\Collection\SecondlyAgentCollection;
use Dashifen\WPHandler\Agents\Collection\AgentCollectionInterface;
use Dashifen\WPHandler\Hooks\Collection\Factory\HookCollectionFactoryInterface;

class Theme extends AbstractThemeHandler
{
  use OptionsManagementTrait;
  use WPDebuggingTrait;
  
  public const PREFIX = 'secondly';
  
  /**
   * @var SecondlyAgentCollection
   */
  protected AgentCollectionInterface $agentCollection;
  protected RecordDataHelper $dataHelper;
  
  /**
   * AbstractHandler constructor.
   *
   * @param HookFactoryInterface|null           $hookFactory
   * @param HookCollectionFactoryInterface|null $hookCollectionFactory
   * @param RecordDataHelper|null               $dataHelper
   */
  public function __construct(
    ?HookFactoryInterface $hookFactory = null,
    ?HookCollectionFactoryInterface $hookCollectionFactory = null,
    ?RecordDataHelper $dataHelper = null
  ) {
    parent::__construct($hookFactory, $hookCollectionFactory);
    $this->dataHelper = $dataHelper ?? new RecordDataHelper();
  }
  
  /**
   * getOptionNames
   *
   * Returns an array of valid option names for use within the isOptionValid
   * method.
   *
   * @return array
   */
  protected function getOptionNames(): array
  {
    return array_map(
      fn(string $option): string => self::PREFIX . '-' . $option,
      [
        'app-route-count',
      ]
    );
  }
  
  /**
   * __call
   *
   * This method is triggered when invoking inaccessible methods in an
   * object context.
   *
   * @param $method      string
   * @param $arguments   array
   *
   * @return mixed
   */
  public function __call(string $method, array $arguments)
  {
    return in_array($method, get_class_methods($this->dataHelper))
      ? $this->dataHelper->{$method}(...$arguments)
      : $this->{$method}(...$arguments);
  }
  
  
  /**
   * initialize
   *
   * Uses addAction and/or addFilter to connect WordPress protected methods of
   * this object to the WordPress ecosystem.
   *
   * @return void
   * @throws HandlerException
   */
  public function initialize(): void
  {
    if (!$this->isInitialized()) {
      
      // themes don't get an activation action in the way that plugins do.
      // but, we can use the after_theme_switch hook to do something similar.
      
      $this->addAction('after_switch_theme', 'flushPermalinks');
      
      // we initialize agents at priority 1 so that the default priority of 10
      // will still be available for agents to use.  otherwise, there could be
      // a timing problem if we initialize agents during the same action and
      // priority that they want to do stuff.
      
      $this->addAction('init', 'initializeAgents', 1);
      $this->addAction('init', 'addAppRoutesAsEndpoints');
      $this->addAction('wp_enqueue_scripts', 'addAssets');
      $this->addAction('template_redirect', 'forceAuthentication');
      $this->addFilter('template_include', 'forceIndexTemplate');
      $this->addFilter('wp_die_handler', 'getExceptionHandler');
    }
  }
  
  /**
   * flushPermalinks
   *
   * This method watches for the activation of our theme and then makes sure
   * that our permalinks are ready-to-go.
   *
   * @return void
   * @throws HandlerException
   */
  protected function flushPermalinks(): void
  {
    if (self::isDebug() || wp_get_theme()->get('Name') === 'Secondly') {
      $this->agentCollection->getPostTypeRegistrationAgent()->register();
      $this->addAppRoutesAsEndpoints();
      flush_rewrite_rules();
    }
  }
  
  /**
   * addAppRoutesAsEndpoints
   *
   * Adds the application routes (e.g. add-record) to the endpoints that WP
   * knows about preventing a 404 header from being thrown by the WP router.
   *
   * @return void
   * @throws HandlerException
   */
  protected function addAppRoutesAsEndpoints(): void
  {
    foreach (Router::APP_ROUTES as $route) {
      
      // the false we send to the add_rewrite_endpoint means that we don't
      // register a query variable for these endpoints.
      
      add_rewrite_endpoint($route, EP_ROOT, false);
    }
    
    // now that we've registered our routes, we need to determine if we have
    // to flush the rewrite rules to update the permalinks for these endpoints.
    // if the current size of the routes we just registered is different from
    // what our record of application routes is in the database, then we flush
    // the rules after updating the database record of the route counts.
    
    $routeCount = sizeof(Router::APP_ROUTES);
    if ($routeCount !== $this->getOption('secondly-app-route-count', 0)) {
      $this->updateOption('secondly-app-route-count', $routeCount);
      flush_rewrite_rules();
    }
  }
  
  /**
   * addAssets
   *
   * Enqueues our font, CSS, and JS assets.
   *
   * @return void
   */
  protected function addAssets(): void
  {
    // we request the Recursive variable font with a range of weights between
    // 400 and 700 (normal and bold) and a slant range of -15 to 0 (italic to
    // vertical).
    
    $this->enqueue('//fonts.googleapis.com/css2?family=Recursive:slnt,wght@-15..0,400..700&display=swap');
    $this->enqueue('assets/dashifen.css');
    
    // for our script, we enqueue it and then we add a an inline script after
    // it that'll guarantee that we have a variable that tells scripts where
    // the WP admin ajax page is.
    
    wp_add_inline_script(
      $this->enqueue('assets/dashifen.js'),
      'window.ajaxUrl = "' . admin_url('admin-ajax.php') . '";'
    );
  }
  
  /**
   * forceAuthentication
   *
   * If the current visitor has not been authenticated, we redirect to the
   * login form and do so.
   */
  protected function forceAuthentication(): void
  {
    if (get_current_user_id() === 0) {
      wp_safe_redirect(wp_login_url(home_url($_SERVER['REQUEST_URI'])));
    }
  }
  
  /**
   * forceIndexTemplate
   *
   * Forces the index template for our application routes.
   *
   * @param string $template
   *
   * @return string
   */
  protected function forceIndexTemplate(string $template): string
  {
    return Router::isAppRoute() ? locate_template('index.php') : $template;
  }
  
  /**
   * getExceptionHandler
   *
   * Returns the name of exception handler for this application.
   *
   * @return callable
   */
  protected function getExceptionHandler(): callable
  {
    return function ($message): void {
      self::debug($message, true);
    };
  }
}
