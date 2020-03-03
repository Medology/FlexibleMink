<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the JavaScriptContext instance via $this->javaScriptContext.
 */
trait UsesJavaScriptContext
{
    /** @var JavaScriptContext */
    protected $javaScriptContext;

    /**
     * Gathers the JavaScriptContext instance and stores a reference in $this->javaScriptContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherJavaScriptContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->javaScriptContext = $environment->getContext(JavaScriptContext::class)) {
            throw new RuntimeException('Failed to gather JavaScriptContext');
        }
    }
}
