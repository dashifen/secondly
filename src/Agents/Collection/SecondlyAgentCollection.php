<?php

namespace Dashifen\Secondly\Agents\Collection;

use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;
use Dashifen\WPHandler\Agents\Collection\AgentCollection;

class SecondlyAgentCollection extends AgentCollection implements SecondlyAgentCollectionInterface
{
  /**
   * getPostTypeRegistrationAgent
   *
   * Returns the instance of the post type registration agent stored within
   * our collection.
   *
   * @return PostTypeRegistrationAgent
   */
  public function getPostTypeRegistrationAgent(): PostTypeRegistrationAgent
  {
    /** @var PostTypeRegistrationAgent $agent */
    
    $agent = $this->collection[PostTypeRegistrationAgent::class];
    return $agent;
  }
}
