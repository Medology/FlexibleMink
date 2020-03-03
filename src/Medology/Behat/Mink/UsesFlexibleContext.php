<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the FlexibleContext instance via $this->flexibleContext.
 */
trait UsesFlexibleContext
{
    /** @var FlexibleContext */
    protected $flexibleContext;

    /**
     * Gathers the FlexibleContext instance and stores a reference in $this->flexibleContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherFlexibleContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->flexibleContext = $environment->getContext(FlexibleContext::class)) {
            throw new RuntimeException('Failed to gather FlexibleContext');
        }
    }
}
