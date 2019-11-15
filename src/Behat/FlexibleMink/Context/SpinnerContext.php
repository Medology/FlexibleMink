<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\SpinnerContextInterface;
use Exception;

trait SpinnerContext
{
    // Implements.
    use SpinnerContextInterface;

    /**
     * {@inheritdoc}
     */
    public function waitFor(callable $lambda, $timeout = 30)
    {
        $lastException = new Exception(
            'Timeout expired before a single try could be attempted. Is your timeout too short?'
        );

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

        throw $lastException;
    }

    /**
     * Waits the indicated number of seconds before proceeding to the next step.
     *
     * @When /^(?:I |)wait (?P<seconds>\d+) seconds?$/
     *
     * @param  string $seconds The number of seconds to wait.
     * @return void
     */
    public function waitSeconds($seconds)
    {
        sleep($seconds);
    }
}
