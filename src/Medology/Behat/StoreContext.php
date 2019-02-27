<?php

namespace Medology\Behat;

use Behat\Behat\Context\Context;
use Chekote\NounStore\Assert;
use Chekote\NounStore\Key;
use Chekote\NounStore\Store;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use ReflectionException;
use ReflectionFunction;
use ReflectionProperty;

/**
 * Context for storing and working with nouns.
 */
class StoreContext extends Store implements Context
{
    /** @var Assert */
    protected $assert;

    /** @var callable[] array of functions to call after the registry is cleared to initialize it for use */
    protected $initializations;

    /** @var Key the key service for the noun-store */
    protected $key;

    public function __construct()
    {
        parent::__construct();

        $this->assert = new Assert($this);
        $this->initializations = [];
        $this->key = Key::getInstance();
    }

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
        $this->reset();

        foreach ($this->initializations as $callable) {
            $callable($this);
        }
    }

    /**
     * Registers an initialization lambda to be called after the registry is cleared before each scenario.
     *
     * @param callable $callable must accept a single argument, which is the StoreContext
     */
    public function registerInitialization(callable $callable)
    {
        $this->initializations[] = $callable;
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
     * @param  int       $index    The index of the key entry to check.
     * @throws Exception If an object was not found under the specified key.
     * @throws Exception If the object does not have the specified property.
     * @return mixed     The value of the property.
     */
    public function getThingProperty($key, $property, $index = null)
    {
        $thing = $this->assert->keyExists($this->key->build($key, $index));

        if (isset($thing, $property)) {
            return $thing->$property;
        }

        throw new Exception("'$thing' existed in the store but had no '$property' property.'");
    }

    /**
     * Parses the string for references to stored items and replaces them with the value from the store.
     *
     * @param string   $string  String to parse.
     * @param callable $onGetFn Used to modify a resource after it is retrieved from store and
     *                          before properties of it are accessed. Takes one argument, the
     *                          resource retrieved and returns the resource after modifying it.
     *
     *                                              $thing = $onGetFn($thing);
     *
     * @param callable $hasValue Used to determine if the thing in the store has the required value.
     *                           Will default to using isset on objects and arrays if not present.
     *                           The callable should take two arguments:
     *
     *                                              $thing    - mixed  - The thing from the store.
     *                                              $property - string - The name of the property or key to check for.
     *
     * @throws InvalidArgumentException If the string references something that does not exist in the store.
     * @throws InvalidArgumentException If callable $onGetFn does not take exactly one argument.
     * @throws InvalidArgumentException If callable $hasValue does not take exactly two arguments.
     * @throws InvalidArgumentException If callable $onGetFn returns something other than array, object or callable.
     * @throws OutOfBoundsException     If the specified stored item does not have the specified property or
     *                                  key.
     * @throws ReflectionException      if $onGetFn was provided but the reflection API says it does not exist. This
     *                                  should not be possible.
     * @throws ReflectionException      if $hasValue was provided but the reflection API says it does not exist. This
     *                                  should not be possible.
     * @return string                   The parsed string.
     */
    public function injectStoredValues($string, callable $onGetFn = null, callable $hasValue = null)
    {
        if ($onGetFn && (new ReflectionFunction($onGetFn))->getNumberOfParameters() != 1) {
            throw new InvalidArgumentException('Method $onGetFn must take one argument!');
        }

        if ($hasValue) {
            if ((new ReflectionFunction($hasValue))->getNumberOfParameters() != 2) {
                throw new InvalidArgumentException('Lambda $hasValue must take two arguments!');
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

            $thing = $this->assert->keyExists($thingName);

            // applies the hook the to the entity
            if ($onGetFn) {
                $thing = $onGetFn($thing);
            }

            // must return object, array, but not function
            if (!is_object($thing) && !is_array($thing) || is_callable($thing)) {
                throw new InvalidArgumentException('The $onGetFn method must return an object or an array!');
            }

            $hasValueResult = $hasValue($thing, $thingProperty);
            if (!is_bool($hasValueResult)) {
                throw new InvalidArgumentException('$hasValue lambda must return a boolean!');
            }

            if (!$hasValueResult) {
                throw new OutOfBoundsException("$thingName does not have a $thingProperty property");
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
        if (
            substr($property, 0, 1) === "'" &&
            substr($property, -1) === "'"
        ) {
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

        if ($value instanceof DateTimeInterface) {
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
     * Formats a DateTimeInterface object from the specified host thing to the specified format.
     *
     * The method will attempt the following in sequence:
     *
     * 1. Format as per the format parameter if provided
     * 2. Format using the host thing if it is an object (@see self::formatDateTimeFromHostObject())
     * 3. Format via string casting
     *
     * @param  DateTimeInterface $dateTime the date time to format.
     * @param  array|object      $thing    the thing that the date time came from.
     * @param  string|null       $format   the optional format for the date time.
     * @return string            the formatted date time.
     */
    protected function formatDateTime(DateTimeInterface $dateTime, $thing, $format = null)
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
     * Formats a DateTimeInterface based on the configuration of the host object that it came from.
     *
     * This method is primarily for ensuring that Carbon instances are formatted properly when read from
     * an Eloquent model. Eloquent uses a static dateFormat property on the class which will cause the
     * Carbon instances to be formatted when the model is converted to an array or JSON. If the Carbon
     * instance is converted to a string via PHP, the dateFormat property is not going to be used. This
     * can cause problems because comparing a string Carbon instance locally to one received from the
     * server will result in different formatting. This method will ensure that the Carbon instance
     * is formatted as per the classes dateFormat property if it is present.
     *
     * @param  DateTimeInterface $dateTime the date time to format.
     * @param  object            $object   the host object that the date time came from.
     * @return string            the formatted date time.
     */
    protected function formatDateTimeFromHostObject(DateTimeInterface $dateTime, $object)
    {
        return ($format = $this->getPropertyValue($object, 'dateFormat'))
            ? $dateTime->format($format)
            : $this->formatDateTimeWithoutHostObject($dateTime);
    }

    /**
     * Formats a DateTime without taking into account the config of its host object.
     *
     * @param  DateTimeInterface $dateTime the date time to format.
     * @return string            the result of calling __toString() on the date time, or formatting it as
     *                                    static::$dateFormat if no __toString method exists.
     */
    protected function formatDateTimeWithoutHostObject(DateTimeInterface $dateTime)
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
     * Adds a reference to a stored thing under the new specified key.
     * @Given /^(?:the |)"(?P<current>[^"]*)" has an alias of "(?P<new>[^"]*)"$/
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
