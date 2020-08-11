<?php

namespace Dashifen\Secondly\Agents\Collection;

use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;
use Dashifen\WPHandler\Agents\Collection\AgentCollectionInterface;

interface SecondlyAgentCollectionInterface extends AgentCollectionInterface
{
  /**
   * getPostTypeRegistrationAgent
   *
   * Returns the instance of the post type registration agent stored within
   * our collection.
   *
   * @return PostTypeRegistrationAgent
   */
  public function getPostTypeRegistrationAgent(): PostTypeRegistrationAgent;
}
