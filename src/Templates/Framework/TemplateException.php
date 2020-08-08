<?php

namespace Dashifen\Secondly\Templates\Framework;

use Dashifen\Exception\Exception;

class TemplateException extends Exception
{
  public const UNKNOWN_TWIG = 1;
  public const UNKNOWN_CONTEXT = 2;
}
