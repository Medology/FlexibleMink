<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Exception;

/**
 * Functionality for waiting for a particular condition.
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
     * @param  callable  $lambda  The lambda to call. Must return true on success.
     * @param  int       $timeout The number of seconds to spin for.
     * @throws Exception If the timeout expires and the lambda has thrown a Exception.
     * @return mixed     The result of the lambda if it succeeds.
     */
    abstract public function waitFor(callable $lambda, $timeout = 30);
}
