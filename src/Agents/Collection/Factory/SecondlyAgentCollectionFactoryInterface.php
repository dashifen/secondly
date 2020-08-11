<?php

namespace Dashifen\Secondly\Agents\Collection\Factory;

use Dashifen\WPHandler\Agents\Collection\Factory\AgentCollectionFactoryInterface;

interface SecondlyAgentCollectionFactoryInterface extends AgentCollectionFactoryInterface
{
  /**
   * registerAgents
   *
   * Used as a method call by our dependency injection container, this method
   * registers all of the agents in the collection produced by this factory.
   *
   * @return void
   */
  public function registerAgents(): void;
}
