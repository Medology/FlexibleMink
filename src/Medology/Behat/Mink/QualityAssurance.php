<?php namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Medology\Behat\UsesStoreContext;
use Medology\SpinnerTimeoutException;
use ReflectionException;

/**
 * Overwrites some MinkContext step definitions to make them more resilient to failures caused by browser/driver
 * discrepancies and unpredictable load times.
 *
 * Class QualityAssurance
 * @package Medology\Behat\Mink
 */
class QualityAssurance implements Context
{
    use UsesFlexibleContext;
    use UsesStoreContext;

    /**
     * Get a NodeElement by qaId
     *
     * @param string                   $qaId string the qaId of the Element to get
     * @return NodeElement             Page element node
     * @throws ExpectationException    Exception thrown for failed expectations
     * @throws SpinnerTimeoutException Thrown when the Spinner did not execute a
     *                                 single attempt of the closure before the timeout expired.
     */
    protected function getNodeElementByQaID($qaId) {
        $this->flexibleContext->waitForPageLoad();
        return $this->flexibleContext->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should be fully visible in the viewport$/
     *
     * @param string $qaId
     * @throws ExpectationException             If the element is fully visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @throws \WebDriver\Exception
     */
    public function assertQaIDIsFullyVisibleInViewport($qaId)
    {
        $this->flexibleContext->waitForPageLoad();

        $element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId));

        if (!$element) {
            throw new ExpectationException(
                "Data QA ID '$qaId' is not visible, but it should be",
                $this->flexibleContext->getSession()
            );
        }

        if (!$this->flexibleContext->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException('Node is not visible in the viewport.',
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should not be fully visible in the viewport$/
     *
     * @param string $qaId
     * @throws ExpectationException             If the element is fully visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException Exception thrown by drivers when they don't support the requested action.
     * @throws \WebDriver\Exception
     */
    public function assertQaIDIsNotFullyVisibleInViewport($qaId)
    {
        $this->flexibleContext->waitForPageLoad();

        $element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId));

        if (!$element) {
            return;
        }

        if ($this->flexibleContext->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException('Node is visible in the viewport.',
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }
}
