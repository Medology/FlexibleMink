<?php

namespace Behat\FlexibleMink\PseudoInterface;

/**
 * Trait for storing and retrieving things via keys.
 */
trait StoreContextInterface
{
    /**
     * Stores the specified thing under the specified key in the registry.
     *
     * @param mixed  $thing The thing to be stored.
     * @param string $key   The key to store the thing under.
     */
    abstract protected function put($thing, $key);

    /**
     * Retrieves the thing stored under the specified key in the registry.
     *
     * @param  string $key The key to retrieve the thing for.
     * @return mixed  The thing that was retrieved.
     */
    abstract protected function get($key);
}
