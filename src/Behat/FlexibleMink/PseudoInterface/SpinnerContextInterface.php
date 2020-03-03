<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Exception;

/**
 * Pseudo interface for tracking the methods of the SpinnerContext.
 */
trait SpinnerContextInterface
{
    /**
     * Calls the $lambda until it returns true or the timeout expires.
     *
     * This method is a "spinner" that will check a condition as many times as possible during the specified timeout
     * period. As soon as the lambda returns true, the method will return. This is useful when waiting on remote
     * drivers such as Selenium.
     *
     * @param callable $lambda  The lambda to call. Must return true on success.
     * @param int      $timeout the number of seconds to spin for
     *
     * @throws Exception if the timeout expires and the lambda has thrown a Exception
     *
     * @return mixed the result of the lambda if it succeeds
     */
    abstract public function waitFor(callable $lambda, $timeout = 30);
}
