<?php

namespace Medology\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Interface for Behat contexts that utilize other Contexts.
 *
 * This interface defines a single function that is triggered before each Behat scenario. The implementing class
 * should use this function to gather any contexts from the environment that it needs to interact with.
 *
 * @see http://behat-docs.readthedocs.io/en/mvp1.0/cookbooks/gathering_contexts_when_using_multiple_contexts.html
 */
interface GathersContexts
{
    /**
     * Gathers other Contexts from the Environment.
     *
     * This method allows this Context to leverage features from other Contexts. Behat will only create a single
     * instance of each Context that was specified for the suite. Therefore, the Contexts are essentially
     * singletons. This method allows this Context to get references to the Context singletons, and
     * leverage their features.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope);
}
