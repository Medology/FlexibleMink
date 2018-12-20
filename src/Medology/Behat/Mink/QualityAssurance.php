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
 */
class QualityAssurance implements Context
{
    use UsesFlexibleContext;
    use UsesStoreContext;

    /**
     * Get a NodeElement by qaId.
     *
     * @param  string                  $qaId string the qaId of the Element to get
     * @throws ExpectationException    Exception thrown for failed expectations
     * @throws SpinnerTimeoutException Thrown when the Spinner did not execute a
     *                                      single attempt of the closure before the timeout expired.
     * @return NodeElement             Page element node
     */
    protected function getNodeElementByQaID($qaId)
    {
        $this->flexibleContext->waitForPageLoad();

        return $this->flexibleContext->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then :qaId should be fully visible in the viewport
     *
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is not fully visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @throws \WebDriver\Exception
     */
    public function assertQaIDIsFullyVisibleInViewport($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        if (!$element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId))) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->flexibleContext->getSession()
            );
        }
        if (!$this->flexibleContext->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException(
                "$qaId is not fully visible in the viewport.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is not fully visible.
     *
     * @Then :qaId should not be fully visible in the viewport
     *
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is fully visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException Exception thrown by drivers when they don't support the requested action.
     */
    public function assertQaIDIsNotFullyVisibleInViewport($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        if (!$element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId))) {
            return;
        }
        if ($this->flexibleContext->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException(
                "$qaId is fully visible in the viewport.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is visible in the viewport.
     *
     * @Then :qaId should be visible in the viewport
     *
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is not visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException Exception thrown by drivers when they don't support the requested action.
     */
    public function assertQaIDIsVisibleInViewport($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        if (!$element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId))) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->flexibleContext->getSession()
            );
        }

        if (!$this->flexibleContext->nodeIsVisibleInViewport($element)) {
            throw new ExpectationException(
                "$qaId is not visible in the viewport.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is not visible in the viewport.
     *
     * @Then :qaId should not be visible in the viewport
     *
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException Exception thrown by drivers when they don't support the requested action.
     */
    public function assertQaIDIsNotVisibleInViewport($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        if (!$element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId))) {
            return;
        }

        if ($this->flexibleContext->nodeIsVisibleInViewport($element)) {
            throw new ExpectationException(
                "$qaId is visible in the viewport.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is visible in the document.
     *
     * @Then :qaId should be visible in the document
     *
     * @param  string                  $qaId
     * @throws ExpectationException    If the element is not visible in the document
     * @throws ReflectionException     If injectStoredValues incorrectly believes one or more closures were
     *                                      passed. This should never happen. If it does, there is a problem with
     *                                      the injectStoredValues method.
     * @throws SpinnerTimeoutException If the timeout expired before the assertion could be run even once.
     */
    public function assertQaIDIsVisibleInDocument($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        $element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId));

        if (!$element) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->flexibleContext->getSession()
            );
        }

        if (!$this->flexibleContext->nodeIsVisibleInDocument($element)) {
            throw new ExpectationException(
                "$qaId is not visible in the document.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is not visible in the document.
     *
     * @Then :qaId should not be visible in the document
     *
     * @param  string                  $qaId
     * @throws ExpectationException    If the element is visible in the document
     * @throws ReflectionException     If injectStoredValues incorrectly believes one or more closures were
     *                                      passed. This should never happen. If it does, there is a problem with
     *                                      the injectStoredValues method.
     * @throws SpinnerTimeoutException If the timeout expired before the assertion could be run even once.
     */
    public function assertQaIDIsNotVisibleInDocument($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        $element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId));

        if (!$element) {
            return;
        }

        if ($this->flexibleContext->nodeIsVisibleInDocument($element)) {
            throw new ExpectationException(
                "$qaId is visible in the document.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }
}
