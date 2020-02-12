<?php

namespace Medology\Behat;

use Exception;

/**
 * Provides basic functionality for Behat contexts that have a wait proxy.
 *
 * Host classes will need to:
 *  - Define a specific protected wait property type.
 *  - Add a typed property-read annotation to their docblock to facility strict type checking and auto-completion.
 *  - Initialize the wait property in their constructor.
 */
trait HasWaitProxy
{
    protected $wait;

    /**
     * Dynamic accessor for the wait proxy.
     *
     * @param  string    $property the property to read.
     * @throws Exception if the property does not exist on this class.
     * @return mixed     the value of the property if it exists and is accessible.
     */
    public function __get($property)
    {
        if ($property === 'wait') {
            return $this->wait;
        }

        throw new Exception('The property ' . __CLASS__ . "::$property does not exist");
    }
}
