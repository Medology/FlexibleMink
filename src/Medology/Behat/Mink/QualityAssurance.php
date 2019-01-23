<?php namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Medology\Behat\UsesStoreContext;
use ReflectionException;
use WebDriver\Exception as WebDriverException;

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
     * @noinspection PhpDocMissingThrowsInspection waitForPageLoad throws ExpectationException, not Exception.
     * @param  string               $qaId string the qaId of the Element to get
     * @throws ReflectionException  If injectStoredValues incorrectly believes one or more closures were
     * @throws ExpectationException Exception thrown for failed expectations
     * @return NodeElement|null     Page element node
     */
    public function getNodeElementByQaId($qaId)
    {
        $this->flexibleContext->waitForPageLoad();
        $xpath = '//*[@data-qa-id="' . $this->storeContext->injectStoredValues($qaId) . '"]';

        return $this->flexibleContext->getSession()->getPage()->find('xpath', $xpath);
    }

    /**
     * Assert a NodeElement by qaId.
     *
     * @param  string               $qaId string the qaId of the Element to get
     * @throws ExpectationException Exception thrown for failed expectations
     * @throws ReflectionException  If injectStoredValues incorrectly believes one or more closures were
     *                                   single attempt of the closure before the timeout expired.
     * @return NodeElement          Page element node
     */
    public function assertNodeElementExistsByQaId($qaId)
    {
        $element = $this->getNodeElementByQaID($qaId);

        if (!$element) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->flexibleContext->getSession()
            );
        }

        return $element;
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
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @throws WebDriverException               If cannot get the Web Driver
     */
    public function assertQaIdIsFullyVisibleInViewport($qaId)
    {
        $element = $this->assertNodeElementExistsByQaId($qaId);

        if (!$this->flexibleContext->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException(
                "$qaId is not fully visible in the viewport.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }

    /**
     * Asserts that a qaId is partially visible in the viewport.
     *
     * @Then :qaId should be partially visible in the viewport
     *
     * @param  string                           $qaId
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     * @throws ExpectationException             If the element is not visible
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws UnsupportedDriverActionException Exception thrown by drivers when they don't support the requested action.
     * @throws WebDriverException               If cannot get the Web Driver
     */
    public function assertQaIdIsPartiallyVisibleInViewport($qaId)
    {
        $element = $this->assertNodeElementExistsByQaId($qaId);

        if (!$this->flexibleContext->nodeIsVisibleInViewport($element) ||
            $this->flexibleContext->nodeIsFullyVisibleInViewport($element)
        ) {
            throw new ExpectationException(
                "$qaId is not partially visible in the viewport.",
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
     *                                               passed. This should never happen. If it does, there is a problem
     *                                               with the injectStoredValues method.
     * @throws UnsupportedDriverActionException Exception thrown by drivers when they don't support the requested action.
     * @throws WebDriverException               If cannot get the Web Driver
     */
    public function assertQaIdIsNotVisibleInViewport($qaId)
    {
        $element = $this->getNodeElementByQaID($qaId);

        if ($element && $this->flexibleContext->nodeIsVisibleInViewport($element)) {
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
     * @param  string                           $qaId
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     * @throws ExpectationException             If the element is not visible in the document
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws WebDriverException               If cannot get the Web Driver
     * @throws UnsupportedDriverActionException
     */
    public function assertQaIdIsVisibleInDocument($qaId)
    {
        $element = $this->assertNodeElementExistsByQaId($qaId);

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
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is visible in the document
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws UnsupportedDriverActionException If driver is not the selenium 2 driver
     * @throws WebDriverException               If cannot get the Web Driver
     */
    public function assertQaIdIsNotVisibleInDocument($qaId)
    {
        $element = $this->getNodeElementByQaID($qaId);

        if ($element && $this->flexibleContext->nodeIsVisibleInDocument($element)) {
            throw new ExpectationException(
                "$qaId is visible in the document.",
                $this->flexibleContext->getSession()->getDriver()
            );
        }
    }
}
