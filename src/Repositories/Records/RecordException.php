<?php

namespace Dashifen\Secondly\Repositories\Records;

use Dashifen\Repository\RepositoryException;

class RecordException extends RepositoryException
{
  public const INVALID_DATE = 1;
  public const INVALID_TIME = 2;
  public const INVALID_ACTIVITY = 3;
  public const INVALID_PROJECT_DATA = 4;
  public const INVALID_TASK_DATA = 5;
}
