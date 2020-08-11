<?php
/**
 * @noinspection PhpDocFieldTypeMismatchInspection
 */

namespace Dashifen\Secondly;

use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\WPHandler\Handlers\Themes\AbstractThemeHandler;
use Dashifen\Secondly\Agents\Collection\SecondlyAgentCollection;
use Dashifen\WPHandler\Agents\Collection\AgentCollectionInterface;

class Theme extends AbstractThemeHandler
{
  public const PREFIX = 'secondly';
  
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
      
      // we initialize agents at priority 1 so that the default priority of 10
      // will still be available for agents to use.  otherwise, there could be
      // a timing problem if we initialize agents during the same action and
      // priority that they want to do stuff.
      
      $this->addAction('init', 'initializeAgents', 1);
      $this->addAction('after_switch_theme', 'flushPermalinks');
      $this->addAction('wp_enqueue_scripts', 'addAssets');
      $this->addAction('template_redirect', 'forceAuthentication');
      $this->addFilter('wp_die_handler', 'getExceptionHandler');
    }
  }
  
  /**
   * flushPermalinks
   *
   * Themes don't really get an activation action in the way that plugins do,
   * but they can use the after_switch_theme hook to perform similar work.
   * This method watches for the activation of our theme and then makes sure
   * that our permalinks are ready-to-go.  we flush all the time when
   *
   * @return void
   */
  protected function flushPermalinks(): void
  {
    if (self::isDebug() || wp_get_theme()->get('Name') === 'Secondly') {
      $this->agentCollection->getPostTypeRegistrationAgent()->register();
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
    // we request the Recursive variable font's casual subfamily with a range
    // of weights between 400 and 700 (normal and bold) and a slant range of
    // -15 to 0 (italic to vertical).
    
    $this->enqueue('//fonts.googleapis.com/css2?family=Recursive:slnt,wght,CASL@-15..0,400..700,1&display=swap');
    $this->enqueue('assets/dashifen.css');
    $this->enqueue('assets/dashifen.js');
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
   * getExceptionHandler
   *
   * Returns the name of exception handler for this application.
   *
   * @return string
   */
  protected function getExceptionHandler(): string
  {
    // at the moment, we simply use the WP default die handler; eventually we
    // can make something better.
    
    return '_default_wp_die_handler';
  }
}
