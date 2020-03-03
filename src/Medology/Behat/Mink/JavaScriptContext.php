<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Medology\Behat\UsesStoreContext;

/**
 * Provides functionality modifying and checking the Javascript environment in the browser.
 */
class JavaScriptContext implements Context
{
    use UsesFlexibleContext;
    use UsesStoreContext;

    /**
     * Determines if a javascript variable is set and has a value.
     *
     * @Then   the javascript variable :variable should not be null
     *
     * @param string $variable the variable to check
     *
     * @throws ExpectationException if the given variable is undefined
     */
    public function assertJavascriptVariableHasAValue($variable)
    {
        // Get the value of our variable from javascript
        $result = $this->flexibleContext->getSession()->evaluateScript('return ' . $variable . ';');

        // If it's null - we failed
        if ($result === null) {
            throw new ExpectationException('The custom variable "' . $variable . '" is null or does not exist.', $this->flexibleContext->getSession());
        }
    }

    /**
     * Determines if the type of a javascript variable matches a specific type.
     *
     * @Then   /^the javascript variable "(?P<variable>.+)" should(?P<not>| not) be type "(?P<type>[\w]+)"$/
     *
     * @param string      $variable the variable to evaluate type for
     * @param string|bool $not      invert the check? Cast as a boolean based on PHP typecasting
     * @param string      $type     the type to match against
     *
     * @throws ExpectationException if the type of the given variable does not match what's expected
     */
    public function assertJavascriptVariableType($variable, $not, $type)
    {
        // Get the type of our variable from javascript.
        $result = $this->flexibleContext->getSession()->evaluateScript('return typeof(' . $variable . ');');

        // If it doesn't match - we failed.
        if ($result != $type xor $not) {
            throw new ExpectationException("The variable \"$variable\" should$not be type $type, but is $result", $this->flexibleContext->getSession());
        }
    }

    /**
     * Selectively compares two JSON objects.
     *
     * @Then   the javascript variable :variableName should have the following contents:
     *
     * @param string    $variableName the name of the JS variable to look for
     * @param TableNode $values       javaScript variable key-value pair
     *
     * @throws ExpectationException if the Javascript variable isn't a match
     */
    public function assertJsonContentsOneByOne($variableName, TableNode $values)
    {
        $returnedJsonData = $this->flexibleContext->getSession()->evaluateScript(
            'return JSON.stringify(' . $variableName . ');'
        );
        $response = json_decode($returnedJsonData, true);

        foreach ($values->getHash() as $row) {
            if (!isset($response[$row['key']])) {
                throw new ExpectationException("Expected key \"{$row['key']}\" was not in the JS variable \"{$variableName}\"\n" . "Actual: $returnedJsonData", $this->flexibleContext->getSession());
            }
            $expected = $this->getRawOrJson($row['value']);
            $actual = $this->getRawOrJson($response[$row['key']]);

            if ($actual != $expected) {
                throw new ExpectationException("Expected \"$expected\" in {$row['key']} position but got \"$actual\"", $this->flexibleContext->getSession());
            }
        }
    }

    /**
     * Asserts that a javascript variable has a specified value.
     *
     * @Then   the javascript variable :variableName should have the value of :expectedValue
     *
     * @param string $variableName  this is the name of the variable to be checked
     * @param string $expectedValue this is the expected value
     *
     * @throws ExpectationException if variable value does not match expected value
     */
    public function assertJavascriptVariable($variableName, $expectedValue)
    {
        $returnedValue = $this->flexibleContext->getSession()->evaluateScript(
            'return ' . $variableName . ';'
        );

        if ($returnedValue != $expectedValue) {
            throw new ExpectationException("Expected \"$expectedValue\" but got \"$returnedValue\"", $this->flexibleContext->getSession());
        }
    }

    /**
     * Asserts that a set of javascript variables have specified values.
     * The $table should have the variable name in the first column, and the value in the second.
     *
     * @Then   the javascript variables should be:
     *
     * @param TableNode $table the variable names and values to check
     *
     * @throws ExpectationException if variable value does not match expected value
     */
    public function assertJavascriptVariables(TableNode $table)
    {
        $attributes = array_map([$this->storeContext, 'injectStoredValues'], $table->getRowsHash());

        foreach ($attributes as $key => $value) {
            $this->assertJavascriptVariable($key, $value);
        }
    }

    /**
     * Returns as-is literal inputs (string, int, float), otherwise
     * returns the JSON encoded output.
     *
     * @param mixed $value
     *
     * @return string JSON encoded string
     */
    protected function getRawOrJson($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
