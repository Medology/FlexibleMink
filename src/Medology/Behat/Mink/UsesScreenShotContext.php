<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the ScreenShotContext instance via $this->screenShotContext.
 */
trait UsesScreenShotContext
{
    /** @var ScreenShotContext */
    protected $screenShotContext;

    /**
     * Gathers the ScreenShotContext instance and stores a reference in $this->screenShotContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherScreenShotContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->screenShotContext = $environment->getContext(ScreenShotContext::class)) {
            throw new RuntimeException('Failed to gather ScreenShotContext');
        }
    }
}
