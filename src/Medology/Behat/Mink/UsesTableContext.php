<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the TableContext instance via $this->tableContext.
 */
trait UsesTableContext
{
    /** @var TableContext */
    protected $tableContext;

    /**
     * Gathers the TableContext instance and stores a reference in $this->tableContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherTableContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->tableContext = $environment->getContext(TableContext::class)) {
            throw new RuntimeException('Failed to gather TableContext');
        }
    }
}
