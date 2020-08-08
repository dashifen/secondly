<?php

namespace Dashifen\Secondly\Templates\Framework;

use Timber\Timber;
use Dashifen\WPTemplates\AbstractTemplate;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Dashifen\WPTemplates\TemplateException as WPTemplatesException;

abstract class AbstractSecondlyTemplate extends AbstractTemplate
{
  use WPDebuggingTrait;
  
  /**
   * AbstractSecondlyTemplate constructor.
   *
   * @throws WPTemplatesException
   */
  public function __construct()
  {
    $context = array_merge(
      $this->getDefaultContext(),
      $this->getTemplateContext()
    );
    
    parent::__construct($this->getTemplateTwig(), $context);
  }
  
  /**
   * getDefaultContext
   *
   * Returns an array of data that's used throughout the site.
   *
   * @return array
   */
  private function getDefaultContext(): array
  {
    return [
      'year' => date('Y'),
      'site' => [
        'title' => 'Secondly'
      ]
    ];
  }
  
  /**
   * getTemplateContext
   *
   * Returns an array of data for this template's context that is merged with
   * the default data to form the context for the entire request.
   *
   * @return array
   */
  abstract protected function getTemplateContext(): array;
  
  /**
   * getTemplateTwig
   *
   * Returns the name of this template's twig file.
   *
   * @return string
   */
  abstract protected function getTemplateTwig(): string;
  
  /**
   * compile
   *
   * Compiles either a previously set template file and context or can use
   * the optional parameters here to specify the file and context at the time
   * of the call and returns it to the calling scope.
   *
   * @param bool        $debug
   * @param string|null $file
   * @param array|null  $context
   *
   * @return string
   * @throws TemplateException
   */
  public function compile(bool $debug = false, ?string $file = null, ?array $context = null): string
  {
    // the null coalescing assignment operator is equivalent to performing the
    // following assignment:  $x = $x ?? $y.  so, here we either use the values
    // sent here from the calling scope or the values of our properties when
    // those are null.  things remain empty after our assignments, we throw an
    // exception.
    
    if (empty($file ??= $this->file)) {
      throw new TemplateException(
        'Cannot compile without twig.',
        TemplateException::UNKNOWN_TWIG
      );
    }
    
    if (empty($context ??= $this->context)) {
      throw new TemplateException(
        'Cannot compile without twig context.',
        TemplateException::UNKNOWN_CONTEXT
      );
    }
    
    $compilation = Timber::fetch($file, $context);
  
    // if we haven't exited, then we'll print the information in our file and
    // context for debugging purposes if we're called to do so.  then, we pass
    // the necessary information over to the Timber::fetch method and call it a
    // day.
  
    if ($debug || self::isDebug()) {
      $context['twig'] = $file;
      $compilation .= '<!--' . PHP_EOL;
      $compilation .= print_r($context, true);
      $compilation .= PHP_EOL . '-->';
    }
    
    return $compilation;
  }
}
