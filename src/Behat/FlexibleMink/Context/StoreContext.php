<?php

namespace Behat\FlexibleMink\Context;

use ArrayAccess;
use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Closure;
use DateTime;
use Exception;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionProperty;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

/**
 * {@inheritdoc}
 */
trait StoreContext
{
    // Implements.
    use StoreContextInterface;

    /** @var array */
    protected $registry;

    protected static $dateFormat = DateTime::ISO8601;

    protected static $FORMAT_MYSQL_DATE = 'a MySQL date';
    protected static $FORMAT_MYSQL_DATE_AND_TIME = 'a MySQL date and time';
    protected static $FORMAT_US_DATE = 'a US date';
    protected static $FORMAT_US_DATE_AND_TIME = 'a US date and time';
    protected static $FORMAT_US_DATE_AND_12HR_TIME = 'a US date and 12hr time';
    protected static $format_map = [
        'a MySQL date'            => 'Y-m-d',
        'a MySQL date and time'   => 'Y-m-d H:i:s',
        'a US date'               => 'm/d/Y',
        'a US date and time'      => 'm/d/Y H:i:s',
        'a US date and 12hr time' => 'm/d/Y \a\t g:i A',
    ];

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
     * (Modified version of data_get() laravel > 5.6).
     *
     * @param  mixed    $target    The target element
     * @param  string[] $key_parts List of nested values
     * @param  mixed    $default   If value doesn't exists
     * @return mixed
     */
    public function data_get($target, array $key_parts, $default = null)
    {
        foreach ($key_parts as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return $this->closureValue($default);
                }
                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (!isset($target[$segment])) {
                    return $this->closureValue($default);
                }
                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return $this->closureValue($default);
                }
                $target = $target->{$segment};
            } else {
                return $this->closureValue($default);
            }
        }

        return $target;
    }

    /**
     * Returns value itself or Closure will be executed and return result.
     *
     * @param  string $value Closure that needs to be evaluated
     * @return mixed  Result of the Closure function or $value itself
     */
    public function closureValue($value)
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
        $key_parts = explode('.', str_replace("'s ", '.', $key));

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

        return $nth
            ? $this->data_get($this->registry[$target_key][$nth - 1], $key_parts)
            : $this->data_get(end($this->registry[$target_key]), $key_parts);
    }

    /**
     * {@inheritdoc}
     */
    public function all($key)
    {
        return isset($this->registry[$key]) ? $this->registry[$key] : [];
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

        preg_match_all('/\(the ([^\)]+) of the ([^\)]+?)( formatted as ([^\)]+))?\)/', $string, $matches);
        foreach ($matches[0] as $i => $match) {
            $thingName = $matches[2][$i];
            $thingProperty = $this->parseProperty($matches[1][$i]);
            $thingFormat = $matches[4][$i];

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

            $string = str_replace(
                $match,
                $this->getValueForInjection($thingProperty, $thing, $thingFormat),
                $string
            );
        }

        return $string;
    }

    /**
     * Converts the property name used for reference to the actual key name.
     *
     * @param  string $property The property name used to reference the key.
     * @return string
     */
    protected function parseProperty($property)
    {
        if (substr($property, 0, 1) === "'" && substr($property, -1) === "'") {
            return trim($property, "'");
        }

        return str_replace(' ', '_', strtolower($property));
    }

    /**
     * Fetches a value from an object and ensures it is prepared for injection into a string.
     *
     * @param  mixed  $property       the property to get from the object
     * @param  object $thing          the object to get the value from
     * @param  string $propertyFormat the pattern for formatting the value.
     * @return mixed  the prepared value
     */
    protected function getValueForInjection($property, $thing, $propertyFormat = null)
    {
        $value = $thing->$property;

        if ($propertyFormat) {
            $propertyFormat = $this->processPropertyFormat($propertyFormat);
        }

        if ($value instanceof DateTime) {
            $value = $this->formatDateTime($value, $thing, $propertyFormat);
        }

        return $value;
    }

    /**
     * Provides the programmatic value for a plain english property format.
     *
     * e.g. 'a MySQL date and time' equates to the PHP date_format 'Y-m-d H:i:s'
     *
     * @param  string                   $propertyFormat the name of the property format to process.
     * @throws InvalidArgumentException if the property format is not supported.
     * @return string                   the programmatic format.
     */
    protected function processPropertyFormat($propertyFormat)
    {
        if (!isset(self::$format_map[$propertyFormat])) {
            throw new InvalidArgumentException("Unknown value for thingFormat: $propertyFormat");
        }

        return self::$format_map[$propertyFormat];
    }

    /**
     * Formats a DateTime object from the specified host thing to the specified format.
     *
     * The method will attempt the following in sequence:
     *
     * 1. Format as per the format parameter if provided
     * 2. Format using the host thing if it is an object (@see self::formatDateTimeFromHostObject())
     * 3. Format via string casting
     *
     * @param  DateTime     $dateTime the date time to format.
     * @param  array|object $thing    the thing that the date time came from.
     * @param  string|null  $format   the optional format for the date time.
     * @return string       the formatted date time.
     */
    protected function formatDateTime(DateTime $dateTime, $thing, $format = null)
    {
        if ($format) {
            $value = $dateTime->format($format);
        } elseif (is_object($thing)) {
            $value = $this->formatDateTimeFromHostObject($dateTime, $thing);
        } else {
            $value = $this->formatDateTimeWithoutHostObject($dateTime);
        }

        return $value;
    }

    /**
     * Formats a DateTime based on the configuration of the host object that it came from.
     *
     * This method is primarily for ensuring that Carbon instances are formatted properly when read from
     * an Eloquent model. Eloquent uses a static dateFormat property on the class which will cause the
     * Carbon instances to be formatted when the model is converted to an array or JSON. If the Carbon
     * instance is converted to a string via PHP, the dateFormat property is not going to be used. This
     * can cause problems because comparing a string Carbon instance locally to one received from the
     * server will result in different formatting. This method will ensure that the Carbon instance
     * is formatted as per the classes dateFormat property if it is present.
     *
     * @param  DateTime $dateTime the date time to format.
     * @param  object   $object   the host object that the date time came from.
     * @return string   the formatted date time.
     */
    protected function formatDateTimeFromHostObject(DateTime $dateTime, $object)
    {
        return ($format = $this->getPropertyValue($object, 'dateFormat'))
            ? $dateTime->format($format)
            : $this->formatDateTimeWithoutHostObject($dateTime);
    }

    /**
     * Formats a DateTime without taking into account the config of its host object.
     *
     * @param  DateTime $dateTime the date time to format.
     * @return string   the result of calling __toString() on the date time, or formatting it as static::$dateFormat if
     *                           no __toString method exists.
     */
    protected function formatDateTimeWithoutHostObject(DateTime $dateTime)
    {
        return method_exists($dateTime, '__toString')
            ? (string) $dateTime
            : $dateTime->format(static::$dateFormat);
    }

    /**
     * Attempts to get the value of a property (public or otherwise) on an object.
     *
     * @param  object     $object       the object to read the property from.
     * @param  string     $propertyName the name of the property to read.
     * @return mixed|null the value of the property. Will return null if the property does not exist.
     */
    protected function getPropertyValue($object, $propertyName)
    {
        $value = null;

        if (isset($object->$propertyName)) {
            $value = $object->$propertyName;
        } else {
            try {
                $property = new ReflectionProperty(get_class($object), $propertyName);
                $property->setAccessible(true);

                $value = $property->getValue($object);
            } catch (ReflectionException $e) {
                // do nothing
            }
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isStored($key, $nth = null)
    {
        if (!$nth) {
            list($key, $nth) = $this->parseKey($key);
        }

        list($key, $unused) = $this->parseKeyNested($key);

        return $nth ? isset($this->registry[$key][$nth - 1]) : isset($this->registry[$key]);
    }

    /**
     * {@inheritdoc}
     *
     * @Given /^(?:the |)"(?P<current>[^"]*)" has an alias of "(?P<new>[^"]*)"$/
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
     * @Given /^"([^"]*)" is stored as (key|property) "([^"]*)" of "([^"]*)"$/
     * @param  string               $relatedModel_key Key of the Element to be assigned
     * @param  string               $keyword          Property of key
     * @param  string               $target_key       Base array/object key
     * @param  string               $attribute        Attribute or key of the base element
     * @throws InvalidTypeException If Target element is not object or array
     */
    public function setThingProperty($relatedModel_key, $keyword, $attribute, $target_key)
    {
        $targetObj = $this->get($target_key);
        $relatedObj = $this->get($relatedModel_key);

        if ($targetObj && $relatedObj) {
            if (is_object($targetObj)) {
                $targetObj->$attribute = $relatedObj;
            } elseif (is_array($targetObj)) {
                $targetObj[$attribute] = $relatedObj;
            } else {
                throw new InvalidTypeException("Expected type for '$target_key' is array/object but '".
                    gettype($targetObj)."' given");
            }

            $this->put($targetObj, $target_key);
        }
    }
}
