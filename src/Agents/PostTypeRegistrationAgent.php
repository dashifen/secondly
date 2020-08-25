<?php

namespace Dashifen\Secondly\Agents;

use Dashifen\Secondly\Theme;
use Dashifen\WPHandler\Agents\AbstractThemeAgent;
use Dashifen\WPHandler\Handlers\HandlerException;
use Dashifen\WPHandler\Traits\PostTypeRegistrationTrait;
use Dashifen\WPHandler\Traits\TaxonomyRegistrationTrait;

class PostTypeRegistrationAgent extends AbstractThemeAgent
{
  use PostTypeRegistrationTrait;
  use TaxonomyRegistrationTrait;
  
  public const RECORD = Theme::PREFIX . '-record';
  public const PROJECT = Theme::PREFIX . '-project';
  public const TASK = Theme::PREFIX . '-task';
  
  /**
   * initialize
   *
   * Hooks methods of this object into the WordPress ecosystem.
   *
   * @return void
   * @throws HandlerException
   */
  public function initialize(): void
  {
    if (!$this->isInitialized()) {
      $this->addAction('init', 'register');
    }
  }
  
  /**
   * register
   *
   * Registers our post type and associated taxonomies.  Public so that it can
   * be accessed during the after_theme_switch action to register and flush
   * permalinks when the theme is activated.
   *
   */
  public function register(): void
  {
    $this->registerRecordPostType();
    $this->registerProjectTaxonomy();
    $this->registerTaskTaxonomy();
  }
  
  /**
   * registerRecordPostType
   *
   * Registers the post type for each record that a visitor enters into the
   * database.
   *
   * @return void
   */
  private function registerRecordPostType(): void
  {
      $args = array(
        'label'                 => 'Record',
        'description'           => 'A record of time spent on a task',
        'labels'                => $this->getPostTypeLabels('Record', 'Records', 'secondly'),
        'menu_icon'             => 'dashicons-clock',
        'has_archive'           => 'records',
        'capability_type'       => 'page',
        'supports'              => ['title', 'content'],
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'exclude_from_search'   => true,
        'publicly_queryable'    => true,
        'show_in_rest'          => true,
        'menu_position'         => 6,
      );
      
      register_post_type(self::RECORD, $args);
  }
  
  /**
   * registerProjectTaxonomy
   *
   * Each record is linked to a project.  This method registers the
   * hierarchical taxonomy that represents those projects.
   *
   */
  private function registerProjectTaxonomy(): void
  {
    $args = [
      'labels'            => $this->getTaxonomyLabels('Project', 'Projects', 'secondly'),
      'show_tagcloud'     => false,
      'hierarchical'      => true,
      'public'            => true,
      'show_ui'           => true,
      'show_admin_column' => true,
      'show_in_nav_menus' => true,
      'show_in_rest'      => true,
      'rewrite'           => [
        'slug'         => 'records/' . self::PROJECT,
        'hierarchical' => true,
        'with_front'   => true,
      ],
    ];
  
    register_taxonomy(self::PROJECT, [self::RECORD], $args);
  }
  
  /**
   * registerTaskTaxonomy
   *
   * Each record is linked to a task within a project.  This method registers
   * the non-hierarchical taxonomy that represents those tasks.
   *
   * @return void
   */
  private function registerTaskTaxonomy(): void
  {
    $args = [
      'labels'            => $this->getTaxonomyLabels('Task', 'Tasks', 'secondly'),
      'show_tagcloud'     => false,
      'hierarchical'      => false,
      'public'            => true,
      'show_ui'           => true,
      'show_admin_column' => true,
      'show_in_nav_menus' => true,
      'show_in_rest'      => true,
      'rewrite'           => [
        'slug'         => 'records/' . self::PROJECT . '/' . self::TASK,
        'hierarchical' => false,
        'with_front'   => true,
      ],
    ];
  
    register_taxonomy(self::TASK, [self::RECORD], $args);
  }
}
