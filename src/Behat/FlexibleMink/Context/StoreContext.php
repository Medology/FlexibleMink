<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;

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
    }

    /**
     * {@inheritdoc}
     */
    protected function put($thing, $key)
    {
        $this->registry[$key] = $thing;
    }

    /**
     * {@inheritdoc}
     */
    protected function get($key)
    {
        $array = $this->registry;

        if (isset($array[$key])) {
            // Just return the store thing directly.
            return $array[$key];
        }

        // Look for nested things using dot notation.
        foreach (explode('.', $key) as $segment) {
            if ( ! is_array($array) || ! array_key_exists($segment, $array))
            {
                return null;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}
