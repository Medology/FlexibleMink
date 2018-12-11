<?php namespace Behat\FlexibleMink\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

trait QualityAssurance
{

    /**
     * Get a NodeElement by qaId.
     *
     * @param  string                  $qaId string the qaId of the Element to get
     * @return NodeElement             Page element node
     */
    protected function getNodeElementByQaID($qaId)
    {
        $this->waitForPageLoad();
        return $this->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should be fully visible in the viewport$/
     *
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is fully visible
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     */
    public function assertQaIDIsFullyVisibleInViewport($qaId)
    {
        $this->waitForPageLoad();
        $element = $this->getNodeElementByQaID($this->injectStoredValues($qaId));
        if (!$element) {
            throw new ExpectationException(
                "Data QA ID '$qaId' is not visible, but it should be",
                $this->getSession()
            );
        }
        if (!$this->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException('Node is not visible in the viewport.',
                $this->assertSelenium2Driver(__CLASS__ . '::' . __FUNCTION__)
            );
        }
    }

    /**
     * Asserts that a qaId is not fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should not be fully visible in the viewport$/
     *
     * @param  string                           $qaId
     * @throws ExpectationException             If the element is fully visible
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     */
    public function assertQaIDIsNotFullyVisibleInViewport($qaId)
    {
        $this->waitForPageLoad();
        $element = $this->getNodeElementByQaID($this->injectStoredValues($qaId));
        if (!$element) {
            return;
        }
        if ($this->nodeIsFullyVisibleInViewport($element)) {
            throw new ExpectationException("$qaId is visible in the viewport.",
                $this->assertSelenium2Driver(__CLASS__ . '::' . __FUNCTION__)
            );
        }
    }
}
