<?php namespace Behat\FlexibleMink\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

trait QualityAssurance
{
    /**
     * Get a NodeElement by qaId.
     *
     * @param  string      $qaId string the qaId of the Element to get
     * @return NodeElement Page element node
     */
    public function getNodeElementByQaId($qaId)
    {
        $this->waitForPageLoad();

        return $this->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Asserts the a qaId was located in the document.
     *
     * @param  string               $qaId    The qaId of the element
     * @param  NodeElement|null     $element NodeElement located by the qaId
     * @throws ExpectationException If the element was not found
     */
    protected function assertQaIdWasFoundInTheDocument($qaId, $element)
    {
        if (!$element) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->getSession()
            );
        }
    }

    /**
     * Asserts the visibility of a QA element.
     *
     * @Then /^"(?P<qaId>[^"]+)" should (?:|(?P<not>not) )be (?P<visibility>fully|partially) visible in the (?P<place>viewport)$/
     * @Then /^"(?P<qaId>[^"]+)" should (?:|(?P<not>not) )be visible in the (?P<place>document)$/
     *
     * @param  string               $qaId       The qaId of the element
     * @param  string               $place      Where to check for visibility
     * @param  string               $visibility Type of visibility to check for
     * @param  bool                 $not        If not
     * @throws ExpectationException If the element is not fully visible
     */
    public function assertVisibilityOfQaId($qaId, $place, $visibility = '', $not = false)
    {
        $this->waitForPageLoad();
        $element = $this->getNodeElementByQaId($this->injectStoredValues($qaId));
        if ($not && !$element) {
            return;
        }
        $this->assertQaIdWasFoundInTheDocument($qaId, $element);
        $nodeIsVisible = $this->nodeIsVisible($element, $place, $visibility);
        if (($not && $nodeIsVisible) || (!$not && !$nodeIsVisible)) {
            throw new ExpectationException(
                $qaId . ' is' . ($not ? '' : ' not') . ($visibility ? ' ' . $visibility : '') . ' visible'
                . " in the $place.",
                $this->getSession()
            );
        }
    }
}
