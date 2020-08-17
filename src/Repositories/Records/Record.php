<?php

namespace Dashifen\Secondly\Repositories\Records;

use Dashifen\Secondly\App\RecordValidator;
use Dashifen\Repository\RepositoryException;
use Dashifen\Secondly\Repositories\ValidatingRepository;

/**
 * Class Record
 *
 * @property-read string $date
 * @property-read string $start
 * @property-read string $time
 * @property-read string $activity
 * @property-read int    $projectId
 * @property-read int    $taskId
 *
 * @package Dashifen\Secondly\Repositories\Records
 */
class Record extends ValidatingRepository
{
  protected string $date;
  protected string $start;
  protected string $end;
  protected string $activity;
  protected int $projectId;
  protected int $taskId;
  
  // our parent class doesn't require that we use a Validator object to do
  // our work.  but, we've prepped the RecordValidator object to do just that.
  // within our setters below, we check for the validity of values using it.
  
  private RecordValidator $validator;
  
  /**
   * AbstractRepository constructor.
   *
   * If given an associative data array, loops over its values settings
   * properties that match indices therein.
   *
   * @param array                $data
   * @param RecordValidator|null $validator
   *
   * @throws RepositoryException
   */
  public function __construct(array $data = [], RecordValidator $validator = null)
  {
    // it's highly unlikely that we'll ever get anything for our validator
    // object.  but to keep things as SOLID as possible, we'll let it be passed
    // to this object.  but, if it's null, we'll just instantiate the default
    // validator here.
    
    $this->validator = $validator ?? new RecordValidator();
    parent::__construct($data);
  }
  
  /**
   * setDate
   *
   * Sets the date property.
   *
   * @param string $date
   *
   * @return void
   * @throws RecordException
   */
  protected function setDate(string $date): void
  {
    if (!$this->validator->isValid('date', $date)) {
      throw new RecordException(
        'Invalid date: ' . $date,
        RecordException::INVALID_DATE
      );
    }
    
    $this->date = $date;
  }
  
  /**
   * setStart
   *
   * Sets the start property.
   *
   * @param string $start
   *
   * @return void
   * @throws RecordException
   */
  protected function setStart(string $start): void
  {
    if (!$this->validator->isValid('time', $start)) {
      throw new RecordException(
        'Invalid time ' . $start,
        RecordException::INVALID_TIME
      );
    }
    
    $this->start = $start;
  }
  
  /**
   * setEnd
   *
   * Sets the end property.
   *
   * @param string $end
   *
   * @return void
   * @throws RecordException
   */
  protected function setEnd(string $end): void
  {
    if (!$this->validator->isValid('time', $end)) {
      throw new RecordException(
        'Invalid time ' . $end,
        RecordException::INVALID_TIME
      );
    }
    
    $this->end = $end;
  }
  
  /**
   * setActivity
   *
   * Sets the activity property.
   *
   * @param string $activity
   *
   * @return void
   */
  protected function setActivity(string $activity): void
  {
    $this->activity = $activity;
  }
  
  /**
   * setProjectId
   *
   * Sets the project ID property.
   *
   * @param array $projectData
   *
   * @return void
   * @throws RecordException
   */
  protected function setProjectId(array $projectData): void
  {
    if (!$this->validator->isValid('termData', $projectData)) {
      throw new RecordException(
        'Invalid project data: ' . json_encode($projectData),
        RecordException::INVALID_PROJECT_DATA
      );
    }
    
    
  }
  
  /**
   * setTaskId
   *
   * Sets the task ID property.
   *
   * @param array $taskData
   *
   * @return void
   * @throws RecordException
   */
  protected function setTaskId(array $taskData): void
  {
    if (!$this->validator->isValid('termData', $taskData)) {
      throw new RecordException(
        'Invalid task data: ' . json_encode($taskData),
        RecordException::INVALID_TASK_DATA
      );
    }
    
    $this->taskId = $taskData['id'] === 'other';
  }
}
