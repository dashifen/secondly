<?php

namespace Dashifen\Secondly\App\Services;


use Latitude\QueryBuilder\QueryFactory;
use Dashifen\WPDebugging\WPDebuggingTrait;
use Latitude\QueryBuilder\Engine\MySqlEngine;
use Dashifen\Secondly\Repositories\Records\Record;
use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;

use function Latitude\QueryBuilder\on;
use function Latitude\QueryBuilder\alias;
use function Latitude\QueryBuilder\field;

class RecordDataHelper
{
  use WPDebuggingTrait;
  
  private QueryFactory $queryFactory;
  private array $projects;
  private array $tasks;
  
  /**
   * RecordDataHelper constructor.
   *
   * @param QueryFactory|null $queryFactory
   */
  public function __construct(?QueryFactory $queryFactory = null)
  {
    $this->queryFactory = $queryFactory ?? new QueryFactory(new MySqlEngine());
  }
  
  /**
   * getProjects
   *
   * Returns an array of the projects for which we're tracking time.
   *
   * @return array
   */
  public function getProjects(): array
  {
    // for projects, all we need is a map of IDs to names for the project
    // taxonomy.  luckily, the fields argument for a WP_Term_Query can give us
    // exactly that.  so, we can just return the array that get_terms produces
    // without alteration.  if we haven't set our private $projects property,
    // we do that now so that we only have to make this query once.
    
    if (!isset($this->projects)) {
      $this->projects = get_terms(
        [
          'taxonomy'   => PostTypeRegistrationAgent::PROJECT,
          'fields'     => 'id=>name',
          'hide_empty' => false,
        ]
      );
    }
    
    return $this->projects;
  }
  
  /**
   * getTasks
   *
   * Returns an array, indexed by projects, of each project's tasks.
   *
   * @return array
   */
  public function getTasks(): array
  {
    // just like the prior method, we want to cache our list of tasks for each
    // request.  this method does a lot of work (as you'll shortly see) so
    // this cache helps avoid that work over and over again.
    
    if (isset($this->tasks)) {
      return $this->tasks;
    }
    
    $terms = $this->getTerms();
    
    // now we have a list of our terms along with their project association.
    // but, we need to split that up into the project-indexed list that we send
    // back to the calling scope.  to do that, we loop over our projects and
    // liberally use array_filter, array_map, and array_combine to create the
    // structure we want.
    
    foreach ($this->getProjects() as $projectId => $project) {
      $filtered = array_filter($terms, fn($t) => $projectId === (int) $t->project_id);
      
      // now that we have our filtered list, we want to change that from a
      // numerically indexed array of arrays into an ID to name mapping.  we
      // array_combine takes two arrays and makes the first the keys and the
      // latter the values for the final array.  so, we make two mappings that
      // pluck the IDs and names out of our array and pass those to into a
      // call to array_combine to get the array we want.
      
      $tasks[$projectId] = [
        'project' => $project,
        'tasks'   => array_combine(
          array_map(fn($t) => $t->term_id, $filtered),
          array_map(fn($t) => $t->name, $filtered)
        ),
      ];
    }
    
    // notice that we set the $tasks array that we've constructed through this
    // method to a property right before we return it.  this sets our cache of
    // tasks and makes sure that we only do the above data manipulation once
    // per page request.
    
    return $this->tasks = ($tasks ?? []);
  }
  
  /**
   * getTerms
   *
   * Performs a single database query to return an array of 3-tuples of task
   * IDs, names, and associated projects.
   *
   * @return array
   */
  private function getTerms(): array
  {
    global $wpdb;
    
    // we could make multiple queries using get_task in the foreach loop in
    // the getTasks method, but we'd rather not use up all that time on
    // database operations.  instead, here we build a single query that selects
    // term IDs and names from the task taxonomy along with their associated
    // project and return that to the calling scope.  this reduces the
    // operation from O(N) queries, where N is the number of projects, to O(1).
    
    $query = $this->queryFactory
      ->select('tt.term_id', 'name', alias('meta_value', 'project_id'))
      ->from(alias($wpdb->term_taxonomy, 'tt'))
      ->join(alias($wpdb->terms, 't'), on('tt.term_id', 't.term_id'))
      ->join(alias($wpdb->termmeta, 'tm'), on('tt.term_id', 'tm.term_id'))
      ->where(field('meta_key')->eq(PostTypeRegistrationAgent::PROJECT))
      ->andWhere(field('taxonomy')->eq(PostTypeRegistrationAgent::TASK))
      ->compile();
    
    // our query factory produces a SQL string with question marks in place of
    // the parameters.  but, the WPDB class uses sprintf-like markers for its
    // params.  since both of our parameters are strings, in this case, we can
    // rather quickly switch those marks for %s as follows.
    
    $sql = str_replace('?', '%s', $query->sql());
    $statement = $wpdb->prepare($sql, $query->params());
    return $wpdb->get_results($statement);
  }
  
  /**
   * getValues
   *
   * Returns the previously entered Record values for a specific date.
   *
   * @return array
   */
  public function getValues(): array
  {
    $date = $_REQUEST['date'] ?? '2020-08-24';
    
    // this method returns an array of record information that's used to
    // produce tables of data on-screen.  we want to select the information
    // that was entered for $date which we could do with calls to get_posts,
    // get_post_meta, and various taxonomy functions.  or we could do it all
    // in one albeit very complex query that gets us all the records we need
    // in one swell foop.
  
    global $wpdb;
    $query = $this->queryFactory
      ->select(
        alias('ID', 'id'),
        alias('post_title', 'activity'),
        alias('d.meta_value', 'date'),
        alias('s.meta_value', 'start'),
        alias('e.meta_value','end'),
        alias('pt.name', 'project'),
        alias('tt.name', 'task')
      )
      ->from($wpdb->posts)
      ->join(alias($wpdb->postmeta, 'd'), on('d.post_id', 'ID'))
      ->join(alias($wpdb->postmeta, 's'), on('s.post_id', 'ID'))
      ->join(alias($wpdb->postmeta, 'e'), on('e.post_id', 'ID'))
      ->join(alias($wpdb->term_relationships, 'ptr'), on ('ptr.object_id', 'ID'))
      ->join(alias($wpdb->term_taxonomy, 'ptt'), on('ptt.term_taxonomy_id','ptr.term_taxonomy_id'))
      ->join(alias($wpdb->terms, 'pt'), on('pt.term_id','ptt.term_id'))
      ->join(alias($wpdb->term_relationships, 'ttr'), on ('ttr.object_id', 'ID'))
      ->join(alias($wpdb->term_taxonomy, 'ttt'), on('ttt.term_taxonomy_id','ttr.term_taxonomy_id'))
      ->join(alias($wpdb->terms, 'tt'), on('tt.term_id','ttt.term_id'))
      ->where(field('post_type')->eq(PostTypeRegistrationAgent::RECORD))
      ->andWhere(field('d.meta_key')->eq(Record::DATE))
      ->andWhere(field('d.meta_value')->eq($date))
      ->andWhere(field('s.meta_key')->eq(Record::START))
      ->andWhere(field('e.meta_key')->eq(Record::END))
      ->andWhere(field('ptt.taxonomy')->eq(PostTypeRegistrationAgent::PROJECT))
      ->andWhere(field('ttt.taxonomy')->eq(PostTypeRegistrationAgent::TASK))
      ->orderBy('s.meta_value')
      ->orderBy('e.meta_value')
      ->compile();
    
    // our query builder uses the default MySQL means of preparing statements
    // using question marks as their placeholder.  but, we need to switch those
    // for the sprintf-like placeholders for the WPDB object.  luckily, in this
    // case, all of our parameters are strings, so we can just replace ? with
    // %s and then move on.
  
    $sql = str_replace('?', '%s', $query->sql());
    $statement = $wpdb->prepare($sql, $query->params());
    $records = $wpdb->get_results($statement, ARRAY_A);
    
    // there's only one other thing we want to do:  index the array we return
    // to the calling scope based on the ID column of our data.  a cunning use
    // of array_combine and array_column makes this easy:
    
    return array_combine(array_column($records, 'id'), $records);
  }
}
