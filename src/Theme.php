<?php
/**
 * @noinspection PhpDocFieldTypeMismatchInspection
 */

namespace Dashifen\Secondly;

use Dashifen\Secondly\App\Router;
use Latitude\QueryBuilder\QueryFactory;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Latitude\QueryBuilder\Engine\MySqlEngine;
use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\WPHandler\Traits\OptionsManagementTrait;
use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;
use Dashifen\WPHandler\Handlers\Themes\AbstractThemeHandler;
use Dashifen\Secondly\Agents\Collection\SecondlyAgentCollection;
use Dashifen\WPHandler\Agents\Collection\AgentCollectionInterface;

use function Latitude\QueryBuilder\on;
use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;

class Theme extends AbstractThemeHandler
{
  use OptionsManagementTrait;
  use WPDebuggingTrait;
  
  public const PREFIX = 'secondly';
  
  private array $projects;
  private array $tasks;
  
  /**
   * @var SecondlyAgentCollection
   */
  protected AgentCollectionInterface $agentCollection;
  
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
      wp_safe_redirect(wp_login_url(home_url()));
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
   * getProjects
   *
   * Returns an array of the projects for which we're tracking time.
   *
   * @return array
   */
  public function getProjects(): array
  {
    // for projects, all we need is a map of IDs to names for the project
    // taxonomy.  luckily, the fields argument for a WP_Term_Query can give us
    // exactly that.  so, we can just return the array that get_terms produces
    // without alteration.  if we haven't set our private $projects property,
    // we do that now so that we only have to make this query once.
    
    if (!isset($this->projects)) {
      $this->projects = get_terms(
        [
          'taxonomy'   => PostTypeRegistrationAgent::PROJECT,
          'fields'     => 'id=>name',
          'hide_empty' => false,
        ]
      );
    }
    
    return $this->projects;
  }
  
  /**
   * getTasks
   *
   * Returns an array, indexed by projects, of each project's tasks.
   *
   * @return array
   */
  public function getTasks(): array
  {
    // just like the prior method, we want to cache our list of tasks for each
    // request.  this method does a lot of work (as you'll shortly see) so
    // this cache helps avoid that work over and over again.
    
    if (isset($this->tasks)) {
      return $this->tasks;
    }
    
    global $wpdb;
    
    // for tasks, we need to do more work than we did for projects.  that's
    // because we want a multi-dimensional array indexed by project ID in which
    // values are the project's name and the terms within it.  that means we
    // can use the prior method to get the projects and then, with them, we
    // get term information.  instead of making N queries, where N is the count
    // of projects, we'll make one query that we build ourselves and gets us
    // everything that we need all at once.
    
    $projects = $this->getProjects();
    $queryFactory = new QueryFactory(new MySqlEngine());
    $query = $queryFactory
      ->select('tt.term_id', 'name', alias('meta_value', 'project'))
      ->from(alias($wpdb->term_taxonomy, 'tt'))
      ->join(alias($wpdb->terms, 't'), on('tt.term_id', 't.term_id'))
      ->join(alias($wpdb->termmeta, 'tm'), on('tt.term_id', 'tm.term_id'))
      ->where(field('meta_key')->eq(PostTypeRegistrationAgent::PROJECT))
      ->andWhere(field('taxonomy')->eq(PostTypeRegistrationAgent::TASK))
      ->compile();
    
    // our query factory produces a SQL string with question marks in place of
    // the parameters.  but, the WPDB class uses sprintf-like markers for its
    // params.  since both of our parameters are strings, in this case, we can
    // rather quickly switch those marks for %s as follows.
    
    $sql = str_replace('?', '%s', $query->sql());
    $statement = $wpdb->prepare($sql, $query->params());
    $terms = $wpdb->get_results($statement, ARRAY_A);
    
    // now we have a list of our terms along with their project association.
    // but, we need to split that up into the project-indexed list that we send
    // back to the calling scope.  to do that, we loop over our projects and
    // liberally use array_filter to create the structure we want.
    
    $tasks = [];
    foreach ($projects as $projectId => $project) {
      
      // we use == rather than === in our anonymous function because the value
      // in our array is a string while $projectId is an int.  we could cast
      // one to the other, but that seems unnecessary; a loosely typed
      // comparison is good enough for us here.
      
      $filtered = array_filter(
        $terms,
        fn(array $t): bool => $t['project'] == $projectId
      );
      
      // now that we have our filtered list, we want to change that from a
      // numerically indexed array of arrays into an ID to name mapping.  we
      // array_combine takes two arrays and makes the first the keys and the
      // latter the values for the final array.  so, we make two mappings that
      // pluck the IDs and names out of our array and pass those to into a
      // call to array_combine to get the array we want.
      
      $tasks[$projectId] = [
        'project' => $project,
        'tasks'   => array_combine(
          array_map(fn(array $t): string => $t['term_id'], $filtered),
          array_map(fn(array $t): string => $t['name'], $filtered)
        ),
      ];
    }
    
    // notice that we set the $tasks array that we've constructed through this
    // method to a property right before we return it.  this sets our cache of
    // tasks and makes sure that we only do the above data manipulation once
    // per page request.
  
    return $this->tasks = $tasks;
  }
}
