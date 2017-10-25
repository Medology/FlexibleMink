<?php

namespace Behat\FlexibleMink\Context;

use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\FlexibleMink\PseudoInterface\ContainerContextInterface;
use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\Mink\Exception\ExpectationException;
use Medology\Behat\StoreContext;
use Medology\Spinner;
use RuntimeException;

trait ContainerContext
{
    // Implements.
    use ContainerContextInterface;
    // Depends.
    use FlexibleContextInterface;

    /** @var StoreContext */
    protected $storeContext;

    /**
     * {@inheritdoc}
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException(
                'Expected Environment to be ' . InitializedContextEnvironment::class .
                    ', but got ' . get_class($environment)
          );
        }

        if (!$this->storeContext = $environment->getContext(StoreContext::class)) {
            throw new RuntimeException('Failed to gather StoreContext');
        }
    }

    /**
     * {@inheritdoc}
     * @Then /^I should see "([^"]*)" in the "([^"]*)" container$/
     */
    public function assertTextInContainer($text, $containerLabel)
    {
        Spinner::waitFor(function () use ($text, $containerLabel) {
            $text = $this->storeContext->injectStoredValues($text);
            $containerLabel = $this->storeContext->injectStoredValues($containerLabel);
            $node = $this->getSession()->getPage()->find('xpath', "//*[contains(text(),'$containerLabel')]");
            if (!$node) {
                throw new ExpectationException("The '$containerLabel' container was not found", $this->getSession());
            }
            $containerId = $node->getAttribute('data-label-for');
            $container = $this->getSession()->getPage()->findById($containerId);

            if (!$container->find('xpath', "//*[contains(text(),'$text')]")) {
                throw new ExpectationException("'$text' was not found in the '$containerLabel' container", $this->getSession());
            }
        });
    }
}
