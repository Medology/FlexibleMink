<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Exception;

/**
 * Pseudo interface for tracking the methods of the StoreContext.
 */
trait StoreContextInterface
{
    /**
     * Asserts that the thing under the specified key equals the specified value.
     *
     * This method uses strict type checking, and as such you will need to ensure
     * your context is using the Behat\FlexibleMink\Context\TypeCaster trait.
     *
     * @param string $key      the key to compare
     * @param mixed  $expected the value to compare with
     */
    abstract public function assertThingIs($key, $expected = null);

    /**
     * Asserts that the specified thing exists in the registry.
     *
     * @param string $key the key to check
     * @param int    $nth the nth value of the key
     *
     * @return mixed the thing from the store
     */
    abstract public function assertIsStored($key, $nth = null);

    /**
     * Retrieves the thing stored under the specified key on the nth position in the registry.
     *
     * @param string $key the key to retrieve the thing for
     * @param int    $nth the nth value for the thing to retrieve
     *
     * @return mixed the thing that was retrieved
     */
    abstract public function get($key, $nth = null);

    /**
     * Retrieves all the things stored under the specified key in the registry.
     *
     * @param string $key the key to retrieve the things for
     *
     * @return array the things that were retrieved
     */
    abstract public function all($key);

    /**
     * Gets the value of a property from an object of the store.
     *
     * @param string $key      the key to retrieve the object for
     * @param string $property the name of the property to retrieve from the object
     * @param int    $nth      the nth value for the object to retrieve
     *
     * @throws Exception if an object was not found under the specified key
     * @throws Exception if the object does not have the specified property
     *
     * @return mixed the value of the property
     */
    abstract public function getThingProperty($key, $property, $nth = null);

    /**
     * Parses the string for references to stored items and replaces them with the value from the store.
     *
     * @param string   $string   string to parse
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
     * @throws Exception if the string references something that does not exist in the store
     *
     * @return string the parsed string
     */
    abstract public function injectStoredValues($string, callable $onGetFn = null, callable $hasValue = null);

    /**
     * Checks that the specified thing exists in the registry.
     *
     * @param string $key the key to check
     * @param int    $nth the nth value of the key
     *
     * @return bool true if the thing exists, false if not
     */
    abstract public function isStored($key, $nth = null);

    /**
     * Stores the specified thing under the specified key in the registry.
     *
     * @param mixed  $thing the thing to be stored
     * @param string $key   the key to store the thing under
     */
    abstract public function put($thing, $key);

    /**
     * Adds a reference to a stored thing under the new specified key.
     *
     * @param string $current the current key of the thing
     * @param string $new     the new key under which to store the thing
     */
    abstract public function referToStoredAs($current, $new);

    /**
     * Assert if the property of thing contains value.
     *
     * @param string $thing    the thing to be inspected
     * @param string $property the property to be inspected
     * @param string $expected the string keyword to be searched
     *
     * @throws Exception When the value is not found in the property
     */
    abstract public function assertThingPropertyContains($thing, $property, $expected);
}
