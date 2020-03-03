<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Mink\Exception\ExpectationException;

/**
 * Pseudo interface for tracking the methods of the LinkContext.
 */
trait LinkContextInterface
{
    /**
     * Asserts that the canonical tag points to the given location.
     *
     * @param string $destination the location the link should be pointing to
     *
     * @throws ExpectationException when the canonical tag does not contain the given destination
     */
    abstract public function assertCanonicalTagLocation($destination);
}
