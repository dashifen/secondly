<?php

namespace Dashifen\Secondly\Repositories\Records;

use Dashifen\Secondly\App\RecordValidator;
use Dashifen\Repository\RepositoryException;
use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;
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
   * @throws RecordException
   */
  protected function setActivity(string $activity): void
  {
    if (!$this->validator->isValid('activity', $activity)) {
      throw new RecordException(
        'A record\'s activity cannot be empty',
        RecordException::INVALID_ACTIVITY
      );
    }
    
    $this->activity = $activity;
  }
  
  /**
   * setProjectId
   *
   * Sets the project ID property.
   *
   * @param array $project
   *
   * @return void
   * @throws RecordException
   */
  protected function setProjectId(array $project): void
  {
    if (!$this->validator->isValid('termData', $project)) {
      throw new RecordException(
        'Invalid project data: ' . json_encode($project),
        RecordException::INVALID_PROJECT_DATA
      );
    }
    
    $this->projectId = $project['id'] === 'other'
      ? wp_insert_term($project['other'], PostTypeRegistrationAgent::PROJECT)
      : $project['id'];
  }
  
  /**
   * setTaskId
   *
   * Sets the task ID property.
   *
   * @param array $task
   *
   * @return void
   * @throws RecordException
   */
  protected function setTaskId(array $task): void
  {
    if (!$this->validator->isValid('termData', $task)) {
      throw new RecordException(
        'Invalid task data: ' . json_encode($task),
        RecordException::INVALID_TASK_DATA
      );
    }
    
    if (!is_numeric($this->projectId)) {
      throw new RecordException(
        'Cannot add task without associated project',
        RecordException::NO_PROJECT_ID
      );
    }
    
    // tasks are linked to specific projects.  in the setProjectId method, we
    // could just insert our project when it didn't exist and be done.  but
    // here, if the task doesn't exist, we insert it and then add a term meta
    // datum that creates the link to its project.
    
    if ($task['id'] === 'other') {
      $task['id'] = wp_insert_term($task['other'], PostTypeRegistrationAgent::TASK);
      
      // above, if the term doesn't insert, we just let PHP throw a type error
      // to identify the problem for the time being.  but, here we need to
      // check by hand because we're about to use it before PHP would interact
      // with the property type hint in anyway.
      
      if (is_numeric($task['id'])) {
        add_term_meta($task['id'], 'project', $this->projectId);
      }
    }
    
    $this->taskId = $task['id'];
  }
}
