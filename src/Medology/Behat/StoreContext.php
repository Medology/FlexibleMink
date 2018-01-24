<?php

namespace Medology\Behat;

use Behat\Behat\Context\Context;
use Exception;
use nounstore\Store;
use ReflectionFunction;

/**
 * Context for storing and working with nouns.
 */
class StoreContext extends Store implements Context
{
    /**
     * Clears the registry before each Scenario to free up memory and prevent access to stale data.
     *
     * @BeforeScenario
     */
    public function clearRegistry()
    {
        $this->reset();

        if (method_exists($this, 'onStoreInitialized')) {
            $this->onStoreInitialized();
        }
    }

    /**
     * Asserts that the thing under the specified key equals the specified value.
     *
     * This method uses strict type checking, and as such you will need to ensure
     * your context is using the Behat\FlexibleMink\Context\TypeCaster trait.
     *
     * @Then   /^the "(?P<key>[^"]+)" should be (?P<value>true|false|(?:\d*[.])?\d+|'(?:[^']|\\')*'|"(?:[^"]|\\"|)*")$/
     * @param  string    $key      the key to compare.
     * @param  mixed     $expected the value to compare with.
     * @throws Exception if the thing does not match the expected value.
     */
    public function assertThingIs($key, $expected = null)
    {
        if (($actual = $this->get($key)) !== $expected) {
            throw new Exception(
                "Expected $key to be " . var_export($expected, true) . ', but it was ' . var_export($actual, true)
            );
        }
    }

    /**
     * Gets the value of a property from an object of the store.
     *
     * @param  string    $key      The key to retrieve the object for.
     * @param  string    $property The name of the property to retrieve from the object.
     * @param  int       $nth      The nth value for the object to retrieve.
     * @throws Exception If an object was not found under the specified key.
     * @throws Exception If the object does not have the specified property.
     * @return mixed     The value of the property.
     */
    public function getThingProperty($key, $property, $nth = null)
    {
        $thing = $this->assertHas($key, $nth);

        if (isset($thing, $property)) {
            return $thing->$property;
        }

        throw new Exception("'$thing' existed in the store but had no '$property' property.'");
    }

    /**
     * Parses the string for references to stored items and replaces them with the value from the store.
     *
     * @param string   $string   String to parse.
     * @param callable $onGetFn  Used to modify a resource after it is retrieved from store and before properties of
     *                           it are accessed. Takes one argument, the resource retrieved and returns the resource
     *                           after modifying it.
     *                           $thing = $onGetFn($thing);
     * @param callable $hasValue Used to determine if the thing in the store has the required value. Will default
     *                           to using isset on objects and arrays if not present. The callable should take two
     *                           arguments:
     *
     *                             $thing    - mixed  - The thing from the store.
     *                             $property - string - The name of the property (or key, etc) to check for.
     *
     * @throws Exception If the string references something that does not exist in the store.
     * @return string    The parsed string.
     */
    public function injectStoredValues($string, callable $onGetFn = null, callable $hasValue = null)
    {
        if ($onGetFn && (new ReflectionFunction($onGetFn))->getNumberOfParameters() != 1) {
            throw new Exception('Method $onGetFn must take one argument!');
        }

        if ($hasValue) {
            if ((new ReflectionFunction($hasValue))->getNumberOfParameters() != 2) {
                throw new Exception('Lambda $hasValue must take two arguments!');
            }
        } else {
            $hasValue = function ($thing, $property) {
                return !(is_object($thing) && !isset($thing->$property)) ||
                    (is_array($thing) && !isset($thing[$property]));
            };
        }

        preg_match_all('/\(the ([^\)]+) of the ([^\)]+)\)/', $string, $matches);
        foreach ($matches[0] as $i => $match) {
            $thingName = $matches[2][$i];
            $thingProperty = str_replace(' ', '_', strtolower($matches[1][$i]));

            if (!$this->has($thingName)) {
                throw new Exception("Did not find $thingName in the store");
            }

            // applies the hook the to the entity
            $thing = $onGetFn ? $onGetFn($this->get($thingName)) : $this->get($thingName);

            // must return object, array, but not function
            if (!is_object($thing) && !is_array($thing) || is_callable($thing)) {
                throw new Exception('The $onGetFn method must return an object or an array!');
            }

            $hasValueResult = $hasValue($thing, $thingProperty);
            if (!is_bool($hasValueResult)) {
                throw new Exception('$hasValue lambda must return a boolean!');
            }

            if (!$hasValueResult) {
                throw new Exception("$thingName does not have a $thingProperty property");
            }

            $string = str_replace($match, $thing->$thingProperty, $string);
        }

        return $string;
    }

    /**
     * Adds a reference to a stored thing under the new specified key.
     *
     * @When  /^(?:I |)refer to (?:the |)"(?P<current>[^"]*)" as "(?P<new>[^"]*)"$/
     * @param string $current The current key of the thing.
     * @param string $new     The new key under which to store the thing.
     */
    public function referToStoredAs($current, $new)
    {
        $this->set($new, $this->get($current));
    }

    /**
     * Assert if the property of thing contains value.
     *
     * @Then   the :property of the :thing should contain :keyword
     * @param  string    $thing    The thing to be inspected.
     * @param  string    $property The property to be inspected.
     * @param  string    $expected The string keyword to be searched.
     * @throws Exception When the value is not found in the property
     */
    public function assertThingPropertyContains($thing, $property, $expected)
    {
        $expected = $this->injectStoredValues($expected);

        $actual = $this->getThingProperty($thing, $property);
        if (strpos($actual, $expected) === false) {
            throw new Exception(
                "Expected the '$property' of the '$thing' to contain '$expected', but found '$actual' instead"
            );
        }
    }
}
