<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Exception;
use ReflectionFunction;

/**
 * {@inheritdoc}
 */
trait StoreContext
{
    // Implements.
    use StoreContextInterface;

    /** @var array */
    protected $registry;

    /**
     * Clears the registry before each Scenario to free up memory and prevent access to stale data.
     *
     * @BeforeScenario
     */
    public function clearRegistry()
    {
        $this->registry = [];

        if (method_exists($this, 'onStoreInitialized')) {
            $this->onStoreInitialized();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the "(?P<key>[^"]+)" should be (?P<value>true|false|(?:\d*[.])?\d+|'(?:[^']|\\')*'|"(?:[^"]|\\"|)*")$/
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
     * {@inheritdoc}
     */
    protected function put($thing, $key)
    {
        $this->registry[$key][] = $thing;
    }

    /**
     * {@inheritdoc}
     */
    protected function assertIsStored($key, $nth = null)
    {
        if (!$thing = $this->isStored($key, $nth)) {
            throw new Exception("Entry $nth for $key was not found in the store.");
        }

        return $this->get($key, $nth);
    }

    /**
     * Converts a key of the form "nth thing" into "n" and "thing".
     *
     * @param  string $key The key to parse
     * @return array  For a key "nth thing", returns [thing, n], else [thing, null]
     */
    private function parseKey($key)
    {
        if (preg_match('/^([1-9][0-9]*)(?:st|nd|rd|th) (.+)$/', $key, $matches)) {
            $nth = $matches[1];
            $key = $matches[2];
        } else {
            $nth = '';
        }

        return [$key, $nth];
    }

    /**
     * {@inheritdoc}
     */
    protected function get($key, $nth = null)
    {
        if (!$nth) {
            list($key, $nth) = $this->parseKey($key);
        }

        if (!$this->isStored($key, $nth)) {
            return;
        }

        return $nth ? $this->registry[$key][$nth - 1] : end($this->registry[$key]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getThingProperty($key, $property, $nth = null)
    {
        $thing = $this->assertIsStored($key, $nth);

        if (isset($thing, $property)) {
            return $thing->$property;
        }

        throw new Exception("'$thing' existed in the store but had no '$property' property.'");
    }

    /**
     * {@inheritdoc}
     */
    protected function injectStoredValues($string, callable $onGetFn = null, callable $hasValue = null)
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

            if (!$this->isStored($thingName)) {
                throw new Exception("Did not find $thingName in the store");
            }

            // applies the hook the to the entity
            $thing = $onGetFn ? $onGetFn($this->get($thingName)) : $this->get($thingName);

            // must return object, array, but not function
            if (!is_object($thing) && !is_array($thing) || is_callable($thing)) {
                throw new Exception('The $onGetFn method must return an object or an array!');
            }

            $replaceValue = function($match, $thing, $thingProperty) use ($string, $thingName, $hasValue) {
                $hasValueResult = $hasValue($thing, $thingProperty);
                if (!is_bool($hasValueResult)) {
                    throw new Exception('$hasValue lambda must return a boolean!');
                }

                if (!$hasValueResult) {
                    throw new Exception("$thingName does not have a $thingProperty property");
                }

                return str_replace($match, $thing->$thingProperty, $string);
            };

            if (strpos($thingProperty, '->') !== false) {
                $pieces = explode('->', $thingProperty);
                $deepThing = $thing;
                $lastPieceIndex = count($pieces) - 1;
                for ($a = 0; $a < $lastPieceIndex; ++$a) {
                    $piece = $pieces[$a];
                    if (!property_exists($deepThing, $piece)) {
                        throw new Exception("$thingName does not have a deep property called '$piece'");
                    }

                    $deepThing = $deepThing->$piece;
                }

                $string = $replaceValue($match, $deepThing, $pieces[$lastPieceIndex]);
            }
            else {
                $string = $replaceValue($match, $thing, $thingProperty);
            }
        }

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    protected function isStored($key, $nth = null)
    {
        if (!$nth) {
            list($key, $nth) = $this->parseKey($key);
        }

        return $nth ? isset($this->registry[$key][$nth - 1]) : isset($this->registry[$key]);
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:I |)refer to (?:the |)"(?P<current>[^"]*)" as "(?P<new>[^"]*)"$/
     */
    public function referToStoredAs($current, $new)
    {
        $this->put($this->get($current), $new);
    }

    /**
     * {@inheritdoc}
     *
     * @Then the :property of the :thing should contain :keyword
     */
    public function assertThingPropertyContains($thing, $property, $expected)
    {
        $expected = $this->injectStoredValues($expected);

        $actual = $this->getThingProperty($thing, $property);
        if (strpos($actual, $expected) === false) {
            throw new Exception("Expected the '$property' of the '$thing' to contain '$expected', but found '$actual' instead");
        }
    }
}
