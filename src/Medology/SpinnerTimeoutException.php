<?php namespace Medology;

use Exception;

/**
 * Thrown when the Spinner did not execute a single attempt of the closure before the timeout expired.
 */
class SpinnerTimeoutException extends Exception
{
    public function __construct()
    {
        parent::__construct('Timeout expired before a single try could be attempted. Is your timeout too short?');
    }
}
