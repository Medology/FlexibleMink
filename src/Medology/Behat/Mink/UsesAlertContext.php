<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the AlertContext instance via $this->alertContext.
 */
trait UsesAlertContext
{
    /** @var AlertContext */
    protected $alertContext;

    /**
     * Gathers the AlertContext instance and stores a reference in $this->alertContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherAlertContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->alertContext = $environment->getContext(AlertContext::class)) {
            throw new RuntimeException('Failed to gather AlertContext');
        }
    }
}
