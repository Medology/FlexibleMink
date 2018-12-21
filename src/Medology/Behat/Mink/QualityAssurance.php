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
    public function getNodeElementByQaId($qaId)
    {
        $this->flexibleContext->waitForPageLoad();

        return $this->flexibleContext->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
    }

    /**
     * Asserts the a qaId was located in the document
     *
     * @param string                $qaId    The qaId of the element
     * @param NodeElement|null      $element NodeElement located by the qaId
     * @throws ExpectationException If the element was not found
     */
    protected function assertQaIdWasFoundInTheDocument($qaId, $element) {
        if(!$element) {
            throw new ExpectationException(
                "$qaId was not found in the document.",
                $this->flexibleContext->getSession()
            );
        }
    }

    /**
     * Asserts the visibility of a QA element.
     *
     * @Then /^"(?P<qaId>[^"]+)" should (?:|(?P<not>not) )be (?:|(?P<visibility>fully|partially) )visible in the (?P<place>viewport)$/
     * @Then /^"(?P<qaId>[^"]+)" should (?:|(?P<not>not) )be visible in the (?P<place>document)$/
     *
     * @param string $qaId                      The qaId of the element
     * @param string $place                     Where to check for visibility
     * @param string $visibility                Type of visibility to check for
     * @param bool $not                         If not
     *
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                               passed. This should never happen. If it does, there is a problem with
     *                                               the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expired before the assertion could be run even once.
     * @throws ExpectationException             If the element is not fully visible
     * @throws UnsupportedDriverActionException If driver does not support the requested action.
     */
    public function assertVisibilityOfQaId($qaId, $place, $visibility = '', $not = false)
    {
        $this->flexibleContext->waitForPageLoad();
        $element = $this->getNodeElementByQaId($this->storeContext->injectStoredValues($qaId));
        if($not && !$element) {
            return;
        }
        $this->assertQaIdWasFoundInTheDocument($qaId, $element);
        $nodeIsVisible = $this->flexibleContext->nodeIsVisible($element, $place, $visibility);
        if ( ($not && $nodeIsVisible) || (!$not && !$nodeIsVisible) ) {
            throw new ExpectationException(
                $qaId . ' is' . ($not ? '':' not') . ($visibility ? ' ' . $visibility : '') . ' visible'
                    . " in the $place.",
                $this->flexibleContext->getSession()
            );
        }
    }
}
