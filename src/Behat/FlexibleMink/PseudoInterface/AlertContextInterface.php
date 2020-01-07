<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Mink\Exception\ExpectationException;

/**
 * A context for handling JavaScript alerts. Based on a gist by Benjamin Lazarecki with improvements.
 *
 * @link https://gist.github.com/blazarecki/2888851
 */
trait AlertContextInterface
{
    /**
     * Confirms the current JavaScript alert.
     */
    abstract public function confirmAlert();

    /**
     * Cancels the current JavaScript alert.
     */
    abstract public function cancelAlert();

    /**
     * Asserts that the current JavaScript alert contains the given text.
     *
     * @param string $expected The expected text.
     *
     * @throws ExpectationException if the given text is not present in the current alert.
     */
    abstract public function assertAlertMessage($expected);

    /**
     * Fills in the given text to the current JavaScript prompt.
     *
     * @param string $message The text to fill in.
     */
    abstract public function setAlertText($message);
}
