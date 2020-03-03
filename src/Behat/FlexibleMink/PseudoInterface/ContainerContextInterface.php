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
     * @param string $text           text to assert
     * @param string $containerLabel text of label for container
     *
     * @throws ExpectationException if the text is not found in the container
     */
    abstract public function assertTextInContainer($text, $containerLabel);
}
