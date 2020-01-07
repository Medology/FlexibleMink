<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Mink\Exception\ExpectationException;

/**
 * Pseudo interface for tracking the methods of the ContainerContext.
 */
trait ContainerContextInterface
{
    /**
     * Asserts that specified container has specified text.
     *
     * @param string $text           Text to assert.
     * @param string $containerLabel Text of label for container.
     *
     * @throws ExpectationException If the text is not found in the container.
     */
    abstract public function assertTextInContainer($text, $containerLabel);
}
