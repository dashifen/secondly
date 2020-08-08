<?php

namespace Dashifen\Secondly\Templates\Framework;

use Timber\Timber;
use Dashifen\WPTemplates\AbstractTemplate;
use Dashifen\WPTemplates\TemplateException as WPTemplatesException;

abstract class AbstractSecondlyTemplate extends AbstractTemplate
{
  /**
   * AbstractSecondlyTemplate constructor.
   *
   * @throws WPTemplatesException
   */
  public function __construct()
  {
    parent::__construct(
      $this->assignTwig(),
      $this->assignContext()
    );
  }
  
  /**
   * assignTwig
   *
   * Returns the name of our twig template which is assigned to our file
   * property using the setFile method.  Named "assign" to make it more clear
   * that this isn't a typical, public setter.
   *
   * @return string
   */
  abstract protected function assignTwig(): string;
  
  /**
   * assignContext
   *
   * Returns an array that is assigned to the context property.  Named "assign"
   * to make it more clear that this isn't a typical, public setter.
   *
   * @return array
   */
  abstract protected function assignContext(): array;
  
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
    
    if (empty($file ??= $this->twig)) {
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
    
    // if we haven't exited, then we'll print the information in our file and
    // context for debugging purposes if we're called to do so.  then, we pass
    // the necessary information over to the Timber::fetch method and call it a
    // day.
    
    if ($debug || self::isDebug()) {
      self::debug(
        [
          'file'    => $file,
          'context' => $context,
        ]
      );
    }
    
    return Timber::fetch($file, $context);
  }
}
