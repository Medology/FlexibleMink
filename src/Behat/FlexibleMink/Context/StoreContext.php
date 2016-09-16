<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Exception;

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
     * {@inheritdoc}
     */
    protected function get($key, $nth = null)
    {
        if (!$nth && preg_match('/^([1-9][0-9]*)(?:st|nd|rd|th) (.+)$/', $key, $matches)) {
            $nth = $matches[1];
            $key = $matches[2];
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
    protected function injectStoredValues($string, callable $onGetFn = null)
    {
        if ($onGetFn && (new \ReflectionFunction($onGetFn))->getNumberOfParameters() != 1) {
            throw new Exception('Method $onGetFn must take one argument!');
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

            if (
                (is_object($thing) && !property_exists($thing, $thingProperty)) ||
                (is_array($thing) && !isset($thing, $thingProperty))
            ) {
                throw new Exception("$thingName does not have a $thingProperty property");
            }

            $string = str_replace($match, $thing->$thingProperty, $string);
        }

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    protected function isStored($key, $nth = null)
    {
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
}
