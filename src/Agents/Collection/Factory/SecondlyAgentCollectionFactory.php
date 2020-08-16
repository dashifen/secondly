<?php

namespace Dashifen\Secondly\Agents\Collection\Factory;

use Dashifen\Repository\RepositoryException;
use Dashifen\Secondly\Agents\RecordManagementAgent;
use Dashifen\Secondly\Agents\PostTypeRegistrationAgent;
use Dashifen\Secondly\Agents\Collection\SecondlyAgentCollection;
use Dashifen\WPHandler\Agents\Collection\AgentCollectionInterface;
use Dashifen\WPHandler\Agents\Collection\Factory\AgentCollectionFactory;

class SecondlyAgentCollectionFactory extends AgentCollectionFactory implements SecondlyAgentCollectionFactoryInterface
{
  /**
   * produceAgentCollectionInterface
   *
   * This method provides an easy way to override the default use of the
   * AgentCollection object herein.  Just extend this object and override
   * this method and you're good to go!
   *
   * @return AgentCollectionInterface
   */
  protected function produceAgentCollectionInstance(): AgentCollectionInterface
  {
    return new SecondlyAgentCollection();
  }
  
  
  /**
   * registerAgents
   *
   * Used as a method call by our dependency injection container, this method
   * registers all of the agents in the collection produced by this factory.
   *
   * @return void
   * @throws RepositoryException
   */
  public function registerAgents(): void
  {
    $this->registerAgent(PostTypeRegistrationAgent::class);
    $this->registerAgent(RecordManagementAgent::class);
  }
  
}
