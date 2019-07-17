<?php

namespace Behat\FlexibleMink\Context;

use ArrayAccess;
use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Closure;
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
    public function put($thing, $key)
    {
        $this->registry[$key][] = $thing;
    }

    /**
     * {@inheritdoc}
     */
    public function assertIsStored($key, $nth = null)
    {
        if (!$thing = $this->isStored($key, $nth)) {
            throw new Exception("Entry $nth for $key was not found in the store.");
        }

        return $this->get($key, $nth);
    }


    /**
     * Retrieves a value from a nested array or object using array list.
     * (Modified version of data_get() laravel > 5.6)
     *
     * @param  mixed    $target    The target element
     * @param  string[] $key_parts Key string dot notation
     * @param  mixed    $default   If value doesn't exists
     * @return mixed
     */
    function data_get($target, array $key_parts, $default = null)
    {
        if (!count($key_parts)) {
            return $target;
        }
        foreach ($key_parts as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return $this->value($default);
                }
                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (!isset($target[$segment])) {
                    return $this->value($default);
                }
                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return $this->value($default);
                }
                $target = $target->{$segment};
            } else {
                return $this->value($default);
            }
        }
        return $target;
    }

    /**
     * Returns value itself or Closure will be executed and return result.
     *
     * @param  string $value Closure
     * @return mixed  Result of the Closure function or $value itself
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Converts a key part of the form "foo's bar" into "foo" and "bar".
     *
     * @param  string $key The key name to parse
     * @return array  [base key, nested_keys|null]
     */
    private function parseKeyNested($key)
    {
        $key_parts = explode('.', str_replace("'s ", ".", $key));
        return [array_shift($key_parts), $key_parts];
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
    public function get($key, $nth = null)
    {
        if (!$nth) {
            list($key, $nth) = $this->parseKey($key);
        }

        list($target_key, $key_parts) = $this->parseKeyNested($key);

        if (!$this->isStored($target_key, $nth)) {
            return;
        }

        return $nth ? $this->data_get($this->registry[$target_key][$nth - 1], $key_parts) :
            $this->data_get(end($this->registry[$target_key]), $key_parts);
    }


    /**
     * {@inheritdoc}
     */
    public function getThingProperty($key, $property, $nth = null)
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

            if (!$this->isStored($thingName)) {
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
     * {@inheritdoc}
     */
    public function isStored($key, $nth = null)
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

    /**
     * Assign the element of given key to the target object/array under given attribute/key.
     *
     * @Given /^"([^"]*)" is attached to "([^"]*)" with "([^"]*)" attribute$/
     * @param  string    $relatedModel_key Key of the Element to be assigned
     * @param  string    $target_key       Base array/object key
     * @param  string    $attribute        Attribute or key of the base element
     * @throws Exception If Target element is not object or array
     */
    public function assignToObjectAttribute($relatedModel_key, $target_key, $attribute)
    {
        $targetObj = $this->get($target_key);
        $relatedObj = $this->get($relatedModel_key);

        if($targetObj && $relatedObj){
            if(is_object($targetObj)){
                /** Any Object models */
                $targetObj->$attribute = $relatedObj;
                /** Eloquent models */
                is_callable([$targetObj, 'save']);
            } elseif(is_array($targetObj)) {
                /** Associative array */
                $targetObj[$attribute] = $relatedObj;
            } else {
                throw new Exception("The type of '$target_key' is ".gettype($targetObj).". 
                But expected Array or Object");
            }

            $this->put($targetObj, $target_key);
        }
    }
}
