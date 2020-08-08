<?php

namespace Dashifen\Secondly;

use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\WPHandler\Handlers\Themes\AbstractThemeHandler;

class Theme extends AbstractThemeHandler
{
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
      $this->addAction('wp_enqueue_scripts', 'addAssets');
      $this->addAction('template_redirect', 'forceAuthentication');
      $this->addFilter('wp_die_handler', 'getExceptionHandler');
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
    
    $this->enqueue('//fonts.googleapis.com/css2?family=Recursive:CASL,wght,slnt@1,400..700,-15..0&display=swap');
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
