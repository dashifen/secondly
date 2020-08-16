<?php

namespace Dashifen\Secondly\Templates;

use Dashifen\Secondly\Templates\Framework\AbstractSecondlyTemplate;

class AddRecord extends AbstractSecondlyTemplate
{
  /**
   * getTemplateContext
   *
   * Returns an array of data for this template's context that is merged with
   * the default data to form the context for the entire request.
   *
   * @return array
   */
  protected function getTemplateContext(): array
  {
    
    return [
      'page'       => [
        'title' => 'Add Record',
      ],
      'recordForm' => [
        'recordId'     => '',
        'action'       => 'add-record',
        'destination'  => admin_url('admin-post.php'),
        'nonce'        => wp_nonce_field('add-action', 'add-action-nonce', false, false),
        'jsonProjects' => json_encode($this->theme->getProjects()),
        'jsonTasks'    => json_encode($this->theme->getTasks()),
        'jsonValues'   => json_encode([]),
      ],
    ];
  }
  
  /**
   * getTemplateTwig
   *
   * Returns the name of this template's twig file.
   *
   * @return string
   */
  protected function getTemplateTwig(): string
  {
    return 'add-record.twig';
  }
}
