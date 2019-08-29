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
     * @param  string               $destination The location the link should be pointint to.
     * @throws ExpectationException
     */
    abstract public function assertCanonicalTagLocation($destination);
}
