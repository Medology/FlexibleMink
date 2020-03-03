<?php

namespace Medology\Behat;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the CsvContext instance via $this->csvContext.
 */
trait UsesCsvContext
{
    /** @var CsvContext */
    protected $csvContext;

    /**
     * Gathers the CsvContext instance and stores a reference in $this->csvContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherCsvContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->csvContext = $environment->getContext(CsvContext::class)) {
            throw new RuntimeException('Failed to gather CsvContext');
        }
    }
}
