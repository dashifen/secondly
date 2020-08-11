<?php
/**
 * @noinspection PhpStatementHasEmptyBodyInspection
 * @noinspection PhpIncludeInspection
 */

use Timber\Timber;
use Dashifen\Secondly\Theme;
use Dashifen\Exception\Exception;
use Dashifen\Secondly\App\Controller;

// the autoloader might be in one of two locations:  the if-condition defines
// the location on the web, the else defines it on Dash's local machine.  if
// the first doesn't exist, we try the second.  if neither exist, the use of
// require_once will throw a PHP error.

if (file_exists($autoloader = dirname(ABSPATH) . '/deps/vendor/autoload.php'));
else $autoloader = 'vendor/autoload.php';
require_once $autoloader;

(function (): void {
  try {
    $controller = new Controller();
    $theme = $controller->getTheme();
    
    // Timber needs to know where to find our twigs.  since they're in a folder
    // adjacent to the theme's stylesheet, we can identify that location very
    // straightforwardly as follows.
    
    Timber::$locations = $theme->getStylesheetDir() . "/assets/twigs";
    $theme->initialize();
  } catch (Exception $e) {
    wp_die($e->getMessage());
  }
})();
