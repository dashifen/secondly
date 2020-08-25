<?php

namespace Dashifen\Secondly\Repositories\Records;

use WP_Post;
use WP_Term;
use WP_Error;
use Dashifen\Secondly\Theme;
use Dashifen\Secondly\App\RecordValidator;
use Dashifen\WPDebugging\WPDebuggingTrait;
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
 * @property-read int    $project
 * @property-read int    $task
 *
 * @package Dashifen\Secondly\Repositories\Records
 */
class Record extends ValidatingRepository implements RecordInterface
{
  use PostMetaManagementTrait;
  use WPDebuggingTrait;
  
  protected int $id = 0;
  protected string $date;
  protected string $start;
  protected string $end;
  protected string $activity;
  protected int $project;
  protected int $task;
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
   * setProject
   *
   * Sets the project ID property.
   *
   * @param array $project
   *
   * @return void
   * @throws RecordException
   */
  protected function setProject(array $project): void
  {
    if (!$this->validator->isValid('termData', $project)) {
      throw new RecordException(
        'Invalid project data: ' . json_encode($project),
        RecordException::INVALID_PROJECT_DATA
      );
    }
    
    if ($project['id'] === 'other') {
      $project['id'] = $this->maybeCreateOtherTerm($project['other'], PostTypeRegistrationAgent::PROJECT);
    }
    
    $this->project = $project['id'];
  }
  
  /**
   * maybeCreateOtherTerm
   *
   * Adds a term to the database while also avoiding an accidental re-insertion
   * of a term.
   *
   * @param string $termName
   * @param string $taxonomy
   *
   * @return int
   */
  private function maybeCreateOtherTerm(string $termName, string $taxonomy): int
  {
    // first, we'll try to select a term with the given name from the specified
    // taxonomy.  if we can't do so, the we'll insert a new term using our
    // parameters and return it's ID.  but, if the term exists, we'll just
    // send back it's ID to avoid WP yelling at us about duplicating a term.
  
    $term = get_term_by('name', $termName, $taxonomy);
    return !($term instanceof WP_Term)
      ? wp_insert_term($termName, $taxonomy)['term_id']
      : $term->term_id;
  }
  
  /**
   * setTask
   *
   * Sets the task ID property.
   *
   * @param array $task
   *
   * @return void
   * @throws RecordException
   */
  protected function setTask(array $task): void
  {
    if (!$this->validator->isValid('termData', $task)) {
      throw new RecordException(
        'Invalid task data: ' . json_encode($task),
        RecordException::INVALID_TASK_DATA
      );
    }
    
    if (!is_numeric($this->project)) {
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
      $task['id'] = $this->maybeCreateOtherTerm($task['other'], PostTypeRegistrationAgent::TASK);
      if (is_numeric($task['id'])) {
        
        // because it's possible that we aren't creating a new term but have
        // identified an older term that someone re-entered using the other
        // option, we use update_term_meta here.  had we used add_term_meta,
        // then when the maybeCreateOtherTerm method found an existing term,
        // we'd re-add the project meta to it.  using the update function makes
        // sure that we only ever have one project per task.
        
        update_term_meta($task['id'], PostTypeRegistrationAgent::PROJECT, $this->project);
      }
    }
    
    $this->task = $task['id'];
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
   * getPostMetaNamePrefix
   *
   * Returns the prefix that that is used to differentiate the post meta for
   * this handler's sphere of influence from others.  By default, we return
   * an empty string, but we assume that this will likely get overridden.
   * Public in case an agent needs to ask their handler what prefix to use.
   *
   * @return string
   */
  public function getPostMetaNamePrefix(): string
  {
    return Theme::PREFIX . '-';
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
        'Attempt to update invalid record',
        RecordException::INVALID_RECORD
      );
    }
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
    
    $postId = wp_insert_post($this->getPostData());
    if ($postId instanceof WP_Error || $postId === 0) {
      throw new RecordException(
        'Unable to save record in database',
        RecordException::DATABASE_ERROR
      );
    }
    
    $this->id = $postId;
    $this->setRecordTerms(PostTypeRegistrationAgent::PROJECT);
    $this->setRecordTerms(PostTypeRegistrationAgent::TASK);
    $this->setRecordMeta();
    return $this->id;
  }
  
  /**
   * getPostData
   *
   * Returns an array suitable for use in wp_insert_post or wp_update_post.
   *
   * @param int $postId
   *
   * @return array
   */
  private function getPostData(int $postId = 0): array
  {
    return [
      'ID'          => $postId,
      'post_status' => 'publish',
      'post_type'   => PostTypeRegistrationAgent::RECORD,
      'post_title'  => $this->activity,
    ];
  }
  
  /**
   * setRecordTerms
   *
   * Sets the current record in the database to have the term in our properties
   * and only that term.
   *
   * @param string $taxonomy
   *
   * @return void
   * @throws RecordException
   */
  private function setRecordTerms(string $taxonomy): void
  {
    $termId = $taxonomy === PostTypeRegistrationAgent::PROJECT
      ? $this->project
      : $this->task;
    
    $terms = wp_get_object_terms($this->id, $taxonomy);
    if (($termCount = sizeof($terms)) > 1) {
      
      // this shouldn't happen; we're careful to make sure that one and only
      // one term from each of our project taxonomies are attached to a record.
      // so, if it does happen, Dash wants to know about it so they can fix it.
      
      throw new RecordException(
        'Record with more than one ' . $taxonomy,
        RecordException::TOO_MANY_TERMS
      );
    } elseif ($termCount === 1) {
      
      // if there's one term, we'll see if its the same one as the one we
      // identified above.  if it is, we're done.  if it's not, we remove it
      // and proceed below.
      
      if ($termId === $terms[0]) {
        return;
      }
      
      wp_remove_object_terms($this->id, $terms[0], $taxonomy);
    }
    
    // if we're here, then either we had zero terms attached to this post to
    // begin with or we had a single, different term that we removed in the
    // elseif-block above.  either way, here, we add our term to this record
    // and return to the calling scope.
    
    wp_set_object_terms($this->id, $termId, $taxonomy);
  }
  
  /**
   * setRecordMeta
   *
   * Sets our record's meta data related to dates and times.
   *
   * @return void
   * @throws HandlerException
   */
  private function setRecordMeta(): void
  {
    // typically, the getPostMetaNames method is only used by our trait's
    // methods to determine which metadata we manipulate here.  but, we can
    // also use it to save each of those data.
    
    foreach ($this->getPostMetaNames() as $metaKey) {
      $this->updatePostMeta($this->id, $metaKey, $this->{$metaKey});
    }
  }
  
  /**
   * update
   *
   * Updates a record in the database returning true if it's successful and
   * false otherwise.
   *
   * @return bool
   * @throws HandlerException
   * @throws RecordException
   */
  public function update(): bool
  {
    if (!$this->valid) {
      throw new RecordException(
        'Attempt to update invalid record',
        RecordException::INVALID_RECORD
      );
    }
    
    if ($this->id === 0) {
      throw new RecordException(
        'Attempt to update record without ID',
        RecordException::INVALID_RECORD
      );
    }
    
    // wp_update_post returns WP_Error or zero on failure.  if we get either
    // of those, then we'll return false.  but, otherwise, we can update the
    // information about our terms and meta data and then return true.
    
    $success = wp_update_post($this->getPostData($this->id));
    if ($success instanceof WP_Error || $success === 0) {
      return false;
    }
    
    $this->setRecordTerms(PostTypeRegistrationAgent::PROJECT);
    $this->setRecordTerms(PostTypeRegistrationAgent::TASK);
    $this->setRecordMeta();
    return true;
  }
  
  /**
   * delete
   *
   * Removes a record from the database returning true if it's successful and
   * false otherwise.
   *
   * @return bool
   * @throws RecordException
   */
  public function delete(): bool
  {
    if (!$this->valid) {
      throw new RecordException(
        'Attempt to update invalid record',
        RecordException::INVALID_RECORD
      );
    }
    
    if ($this->id === 0) {
      throw new RecordException(
        'Attempt to update record without ID',
        RecordException::INVALID_RECORD
      );
    }
   
    // wp_delete_post returns false or null on failure and a WP_Post when it
    // works.  so, if we call it and get a WP_Post back out of it, we're good
    // to go.
    
    return wp_delete_post($this->id) instanceof WP_Post;
  }
}
