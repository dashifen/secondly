<?php

namespace Dashifen\Secondly\Repositories\Records;

use Dashifen\Secondly\App\RecordValidator;
use Dashifen\Repository\RepositoryException;
use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\WPHandler\Traits\PostMetaManagementTrait;
use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;
use Dashifen\Secondly\Repositories\ValidatingRepository;

/**
 * Class Record
 *
 * @property-read int    $id
 * @property-read string $date
 * @property-read string $start
 * @property-read string $end
 * @property-read string $activity
 * @property-read int    $projectId
 * @property-read int    $taskId
 *
 * @package Dashifen\Secondly\Repositories\Records
 */
class Record extends ValidatingRepository implements RecordInterface
{
  use PostMetaManagementTrait;
  
  protected int $id = 0;
  protected string $date;
  protected string $start;
  protected string $end;
  protected string $activity;
  protected int $projectId;
  protected int $taskId;
  private bool $valid = false;
  
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
    $this->valid = true;
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
      ? wp_insert_term($project['other'], PostTypeRegistrationAgent::PROJECT)['term_id']
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
      $this->taskId = wp_insert_term($task['other'], PostTypeRegistrationAgent::TASK)['term_id'];
      
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
  
  /**
   * getPostMetaNames
   *
   * Returns an array of valid post meta keys managed by this object.
   *
   * @return array
   */
  protected function getPostMetaNames(): array
  {
    return ['date', 'start', 'end'];
  }
  
  
  /**
   * save
   *
   * Saves the record in the database and returns the post ID given to it.
   *
   * @return int
   * @throws HandlerException
   * @throws RecordException
   */
  public function save(): int
  {
    if (!$this->valid) {
      throw new RecordException(
        'Cannot save invalid record in the database',
        RecordException::INVALID_RECORD
      );
    }
    
    // by the time we get here, we know we have a project and task ID for this
    // record even if they are brand new.  what we don't have yet, because it's
    // a save action, is a record ID.  in fact, if we have one, that's a
    // problem.
    
    if ($this->id > 0) {
      throw new RecordException(
        'Attempt to create new database record from existing data (id: ' . $this->id . ')',
        RecordException::INVALID_RECORD
      );
    }
    
    // so, if we're here, we know that we have all the database we need to save
    // our record.  so, first we save the record to create an ID for it.  then,
    // we can use that ID to attach the terms and meta information stored in
    // our properties.
    
    $postData = [
      'post_type'      => PostTypeRegistrationAgent::RECORD,
      'post_title'     => $this->activity,
      'post_status'    => 'publish',
    ];
    
    $this->id = wp_insert_post($postData);
    wp_set_object_terms($this->id, $this->projectId, PostTypeRegistrationAgent::PROJECT);
    wp_set_object_terms($this->id, $this->taskId, PostTypeRegistrationAgent::TASK);
    
    foreach($this->getPostMetaNames() as $metaKey) {
      $this->updatePostMeta($this->id, $metaKey, $this->{$metaKey});
    }
    
    return $this->id;
  }
  
  /**
   * update
   *
   * Updates a record in the database returning true if it's successful and
   * false otherwise.
   *
   * @return bool
   */
  public function update(): bool
  {
    // TODO: Implement update() method.
  }
  
  /**
   * delete
   *
   * Removes a record from the database returning true if it's successful and
   * false otherwise.
   *
   * @return bool
   */
  public function delete(): bool
  {
    // TODO: Implement delete() method.
  }
  
  
}
