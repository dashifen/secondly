<?php

/** @noinspection PhpRedundantCatchClauseInspection */

use Dashifen\Exception\Exception;
use Dashifen\Secondly\App\Controller;

(function() {
  try {
    
    // our controller configures our dependency injection container.  it can
    // give us our router which, in turn, gets us our template.  with our
    // template, we can render the current route.  we don't use the WP router
    // because we want to control the view of site without posts.
    
    $controller = new Controller();
    $router = $controller->getRouter();
    $template = $router->getTemplate();
    $template->render();
  } catch(Exception $e) {
    
    // if a problem occurs within our routing or rendering process, then we'll
    // just send it to the screen.  hopefully that'll clue Dash into how a fix
    // might be possible.
    
    wp_die($e);
  }
})();

