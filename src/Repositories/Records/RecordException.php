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
  public const NO_PROJECT_ID = 6;
  public const NO_RECORD_ID = 7;
  public const INVALID_RECORD = 8;
  public const TOO_MANY_TERMS = 9;
  public const DATABASE_ERROR = 10;
}
