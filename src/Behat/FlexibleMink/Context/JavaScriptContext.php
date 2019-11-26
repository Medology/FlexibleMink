<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\FlexibleMink\PseudoInterface\JavaScriptContextInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * {@inheritdoc}
 */
trait JavaScriptContext
{
    // Depends.
    use FlexibleContextInterface;
    // Implements.
    use JavaScriptContextInterface;

    /**
     * {@inheritdoc}
     *
     * @Then the javascript variable :variable should not be null
     */
    public function assertJavascriptVariableHasAValue($variable)
    {
        // Get the value of our variable from javascript
        $result = $this->getSession()->evaluateScript('return ' . $variable . ';');

        // If it's null - we failed
        if ($result === null) {
            throw new ExpectationException(
                'The custom variable "' . $variable . '" is null or does not exist.',
                $this->getSession()
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the javascript variable "(?P<variable>.+)" should(?P<not>| not) be type "(?P<type>[\w]+)"$/
     */
    public function assertJavascriptVariableType($variable, $not, $type)
    {
        $session = $this->getSession();

        $this->waitFor(static function () use ($type, $not, $variable, $session) {
            $result = $session->evaluateScript('return typeof(' . $variable . ');');

            if ($result !== $type xor $not) {
                throw new ExpectationException(
                    "The variable \"$variable\" should$not be type $type, but is $result",
                    $session
                );
            }

            return true;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @Given the javascript variable :variableName should have the following contents:
     */
    public function assertJsonContentsOneByOne($variableName, TableNode $values)
    {
        $returnedJsonData = $this->getSession()->evaluateScript(
            'return JSON.stringify(' . $variableName . ');'
        );
        $response = json_decode($returnedJsonData, true);

        foreach ($values->getHash() as $row) {
            if (!isset($response[$row['key']])) {
                throw new ExpectationException(
                    "Expected key \"{$row['key']}\" was not in the JS variable \"{$variableName}\"\n" .
                        "Actual: $returnedJsonData",
                    $this->getSession()
                );
            }
            $expected = $this->getRawOrJson($row['value']);
            $actual = $this->getRawOrJson($response[$row['key']]);

            if ($actual != $expected) {
                throw new ExpectationException(
                    "Expected \"$expected\" in {$row['key']} position but got \"$actual\"",
                    $this->getSession()
                );
            }
        }
    }

    /**
     * Returns as-is literal inputs (string, int, float), otherwise
     * returns the JSON encoded output.
     *
     * @param  mixed  $value
     * @return string JSON encoded string
     */
    protected function getRawOrJson($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @Then the javascript variable :variableName should have the value of :expectedValue
     */
    public function assertJavascriptVariable($variableName, $expectedValue)
    {
        $returnedValue = $this->getSession()->evaluateScript(
            'return ' . $variableName . ';'
        );

        if ($returnedValue != $expectedValue) {
            throw new ExpectationException(
                "Expected \"$expectedValue\" but got \"$returnedValue\"",
                $this->getSession()
            );
        }
    }
}
