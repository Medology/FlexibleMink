<?php namespace Behat\FlexibleMink\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Exception;

trait QualityAssurance
{
    /**
     * Get a NodeElement by qaId.
     *
     * @param  string           $qaId string the qaId of the Element to get
     * @return NodeElement|null Page element node
     */
    public function getNodeElementByQaID($qaId)
    {
        $xpath = '//*[@data-qa-id="' . $this->injectStoredValues($qaId) . '"]';

        return $this->getSession()->getPage()->find('xpath', $xpath);
    }

    /**
     * Assert a NodeElement by qaId.
     *
     * @param  string               $qaId string the qaId of the Element to get
     * @throws ExpectationException Exception thrown for failed expectations
     * @return NodeElement          Page element node
     */
    public function assertNodeElementExistsByQaId($qaId)
    {
        $element = $this->getNodeElementByQaID($qaId);

        if (!$element) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->getSession()
            );
        }

        return $element;
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then :qaId should be fully visible in the viewport
     *
     * @param  string               $qaId
     * @throws ExpectationException If the element is not fully visible
     */
    public function assertQaIdIsFullyVisibleInViewport($qaId)
    {
        $this->waitFor(function () use ($qaId) {
            $element = $this->assertNodeElementExistsByQaId($qaId);

            if (!$this->nodeIsFullyVisibleInViewport($element)) {
                throw new ExpectationException(
                    "$qaId is not fully visible in the viewport.",
                    $this->getSession()->getDriver()
                );
            }
        });
    }

    /**
     * Asserts that a qaId is partially visible in the viewport.
     *
     * @Then :qaId should be partially visible in the viewport
     *
     * @param  string               $qaId
     * @throws ExpectationException If the element is not visible
     */
    public function assertQaIdIsPartiallyVisibleInViewport($qaId)
    {
        $this->waitFor(function () use ($qaId) {
            $element = $this->assertNodeElementExistsByQaId($qaId);

            if (
                $this->nodeIsFullyVisibleInViewport($element) ||
                !$this->nodeIsVisibleInViewport($element)
            ) {
                throw new ExpectationException(
                    "$qaId is not partially visible in the viewport.",
                    $this->getSession()->getDriver()
                );
            }
        });
    }

    /**
     * Asserts that a qaId is not visible in the viewport.
     *
     * @Then :qaId should not be visible in the viewport
     *
     * @param  string               $qaId
     * @throws ExpectationException If the element is visible
     */
    public function assertQaIdIsNotVisibleInViewport($qaId)
    {
        $this->waitFor(function () use ($qaId) {
            $element = $this->getNodeElementByQaID($qaId);

            if ($element && $this->nodeIsVisibleInViewport($element)) {
                throw new ExpectationException(
                    "$qaId is visible in the viewport.",
                    $this->getSession()->getDriver()
                );
            }
        });
    }

    /**
     * Asserts that a qaId is visible in the document.
     *
     * @Then :qaId should be visible in the document
     *
     * @param  string               $qaId
     * @throws ExpectationException If the element is not visible in the document
     */
    public function assertQaIDIsVisibleInDocument($qaId)
    {
        $this->waitFor(function () use ($qaId) {
            $element = $this->assertNodeElementExistsByQaId($qaId);

            if (!$this->nodeIsVisibleInDocument($element)) {
                throw new ExpectationException(
                    "$qaId is not visible in the document.",
                    $this->getSession()->getDriver()
                );
            }
        });
    }

    /**
     * Asserts that a qaId is not visible in the document.
     *
     * @Then :qaId should not be visible in the document
     *
     * @param  string               $qaId
     * @throws ExpectationException If the element is visible in the document
     */
    public function assertQaIDIsNotVisibleInDocument($qaId)
    {
        $this->waitFor(function () use ($qaId) {
            $element = $this->getNodeElementByQaID($qaId);

            if ($element && $this->nodeIsVisibleInDocument($element)) {
                throw new ExpectationException(
                    "$qaId is visible in the document.",
                    $this->getSession()->getDriver()
                );
            }
        });
    }

    /**
     * Check a node element with specific text inside the qa element.
     *
     * @When /^I (?P<action>check|uncheck) the checkbox "(?P<checkbox>[^"]+)" in the "(?P<qaId>[^"]+)"$/
     *
     * @param  string               $action   The action on the checkbox.
     * @param  string               $checkbox The text inside the checkbox.
     * @param  string               $qaId     The qa ID
     * @throws Exception            If the string references something that does not exist in the store.
     * @throws ExpectationException If the qa element was not found.
     * @throws ExpectationException If the Checkbox with label was not found.
     */
    public function checkElementWithTextInQaElement($action, $checkbox, $qaId)
    {
        $checkbox = $this->injectStoredValues($checkbox);

        $this->waitFor(function () use ($action, $checkbox, $qaId) {
            $qaElement = $this->assertNodeElementExistsByQaId($qaId);

            $targetElement = $qaElement->find(
                'xpath',
                '//label[contains(.,"' . $checkbox . '")]/input[@type="checkbox"]'
            );

            if (!$targetElement) {
                throw new ExpectationException(
                    "Checkbox with label '$checkbox' was not found within '$qaId'",
                    $this->getSession()
                );
            }

            if ($action === 'check') {
                $targetElement->check();
            } else {
                $targetElement->uncheck();
            }
        });
    }
}
