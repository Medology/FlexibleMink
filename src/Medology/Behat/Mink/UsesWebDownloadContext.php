<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use RuntimeException;

/**
 * Trait that grants access to the WebDownloadContext instance via $this->webDownloadContext.
 */
trait UsesWebDownloadContext
{
    /** @var WebDownloadContext */
    protected $webDownloadContext;

    /**
     * Gathers the WebDownloadContext instance and stores a reference in $this->webDownloadContext.
     *
     * @throws RuntimeException if the current environment is not initialized
     * @BeforeScenario
     */
    public function gatherWebDownloadContext(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException('Expected Environment to be ' . InitializedContextEnvironment::class . ', but got ' . get_class($environment));
        }

        if (!$this->webDownloadContext = $environment->getContext(WebDownloadContext::class)) {
            throw new RuntimeException('Failed to gather WebDownloadContext');
        }
    }
}
