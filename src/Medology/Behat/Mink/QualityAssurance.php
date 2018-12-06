<?php namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Medology\Behat\UsesStoreContext;
use Medology\SpinnerTimeoutException;
use ReflectionException;
use WebDriver\Exception as WebDriverException;

class QualityAssurance implements Context
{
    use UsesFlexibleContext;
    use UsesStoreContext;

    /**
     * Get a NodeElement by qaId
     *
     * @param $qaId string the qaId of the Element to get
     *
     * @return NodeElement
     *
     * @throws ExpectationException
     * @throws SpinnerTimeoutException
     */
    protected function getNodeElementByQaID($qaId) {
        $this->flexibleContext->waitForPageLoad();
        return $this->flexibleContext->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Get a array of NodeElements by qaId
     *
     * @param $qaId string the qaId of the Elements to get
     *
     * @return NodeElement[]
     *
     * @throws ExpectationException
     * @throws SpinnerTimeoutException
     */
    protected function getNodeElementsByQaID($qaId) {
        $this->flexibleContext->waitForPageLoad();
        return $this->flexibleContext->getSession()->getPage()->findAll('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should be fully visible$/
     *
     * @param string $qaId
     * @throws ExpectationException             If the element is fully visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @throws WebDriverException
     */
    public function assertQaIDIsFullyVisible($qaId)
    {
        $this->flexibleContext->waitForPageLoad();

        $element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId));

        if (!$element) {
            throw new ExpectationException(
                "Data QA ID '$qaId' is not visible, but it should be",
                $this->flexibleContext->getSession()
            );
        }

        $this->flexibleContext->assertElementIsFullyVisible($element);
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should not be fully visible$/
     *
     * @param string $qaId
     * @throws ExpectationException             If the element is fully visible
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws UnsupportedDriverActionException
     * @throws WebDriverException
     */
    public function assertQaIDIsNotFullyVisible($qaId)
    {
        $this->flexibleContext->waitForPageLoad();

        $element = $this->getNodeElementByQaID($this->storeContext->injectStoredValues($qaId));

        if (!$element) {
            return;
        }

        $this->flexibleContext->assertElementIsNotFullyVisible($element);
    }
}
