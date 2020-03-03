<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Pseudo interface for tracking the methods of the JavaScriptContext.
 */
trait JavaScriptContextInterface
{
    /**
     * Determines if a javascript variable is set and has a value.
     *
     * @param string $variable the variable to check
     *
     * @throws ExpectationException if the given variable is undefined
     */
    abstract public function assertJavascriptVariableHasAValue($variable);

    /**
     * Determines if the type of a javascript variable matches a specific type.
     *
     * @param string      $variable the variable to evaluate type for
     * @param string|bool $not      invert the check? Cast as a boolean based on PHP typecasting
     * @param string      $type     the type to match against
     *
     * @throws ExpectationException if the type of the given variable does not match what's expected
     */
    abstract public function assertJavascriptVariableType($variable, $not, $type);

    /**
     * Selectively compares two JSON objects.
     *
     * @param string    $variableName the name of the JS variable to look for
     * @param TableNode $values       javaScript variable key-value pair
     *
     * @throws ExpectationException if the Javascript variable isn't a match
     */
    abstract public function assertJsonContentsOneByOne($variableName, TableNode $values);

    /**
     * Asserts that a javascript variable has a specified value.
     *
     * @param string $variableName  this is the name of the variable to be checked
     * @param string $expectedValue this is the expected value
     *
     * @throws ExpectationException if variable value does not match expected value
     */
    abstract public function assertJavascriptVariable($variableName, $expectedValue);
}
