<?php

namespace Medology;

use Exception;

class Spinner
{
    public static $default_timeout = 30;

    /**
     * Calls the $lambda until it does not throw an exception or the timeout expires.
     *
     * This method is a "spinner" that will check a condition as many times as possible (up to 10 times a second)
     * during the specified timeout period. As soon as the lambda does not throw an exception, the method will return
     * the value returned by the lambda. This is useful when waiting on remote drivers such as Selenium.
     *
     * @param  callable                $lambda  The lambda to call. Must return true on success.
     * @param  int                     $timeout The number of seconds to spin for.
     * @throws SpinnerTimeoutException If the timeout expires before the assertion can be made even once.
     * @return mixed                   The result of the lambda if it succeeds.
     */
    public static function waitFor(callable $lambda, $timeout = null)
    {
        if (!$timeout) {
            $timeout = self::$default_timeout;
        }

        $start = time();
        while (time() - $start < $timeout) {
            try {
                return $lambda();
            } catch (Exception $e) {
                $lastException = $e;
            }

            // sleep for 10^8 nanoseconds (0.1 second)
            time_nanosleep(0, pow(10, 8));
        }

        throw isset($lastException) ? $lastException : new SpinnerTimeoutException();
    }
}
