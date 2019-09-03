<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Mink\Exception\ExpectationException;

/**
 * Pseudo interface for tracking the methods of the ContainerContext.
 */
trait LinkContextInterface
{
    /**
     * Asserts that the canonical tag points to the given location.
     *
     * @param  string               $destination The location the link should be pointing to.
     * @throws ExpectationException When the canonical tag does not contain the given destination.
     */
    abstract public function assertCanonicalTagLocation($destination);
}
