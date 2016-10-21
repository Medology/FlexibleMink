<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\SpinnerContextInterface;

trait SpinnerContext
{
    // Implements.
    use SpinnerContextInterface;

    /**
     * {@inheritdoc}
     */
    public function waitFor(callable $lambda, $timeout = 30)
    {
        $lastException = new \Exception(
            'Timeout expired before a single try could be attempted. Is your timeout too short?'
        );

        $start = time();
        while (time() - $start < $timeout) {
            try {
                return $lambda();
            } catch (\Exception $e) {
                $lastException = $e;
            }

            // sleep for 10^8 nanoseconds (0.1 second)
            time_nanosleep(0, pow(10, 8));
        }

        throw $lastException;
    }
}
