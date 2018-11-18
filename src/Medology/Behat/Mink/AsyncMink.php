<?php namespace Medology\Behat\Mink;

use Exception;
use Medology\Spinner;

/**
 * Wraps the FlexibleMink context to provide asynchronous assertions.
 *
 * The methods here will wait for either the assertion to pass, or for the timeout to expire.
 *
 * See Spinner::$default_timeout
 *
 * This is purely for use when calling these methods programmatically. This class is NOT a context class and should
 * not be used as such. Async functionality for context classes should be handled by use of the BehatRetryExtension
 * https://github.com/Chekote/BehatRetryExtension.
 *
 * @mixin FlexibleContext
 */
class AsyncMink
{
    /** @var FlexibleContext */
    protected $flexibleContext;

    /**
     * @param FlexibleContext $flexibleContext the instance to wrap.
     */
    public function __construct(FlexibleContext $flexibleContext)
    {
        $this->flexibleContext = $flexibleContext;
    }

    /**
     * Proxy method to handle all calls and relay them to FlexibleContext wrapped in a waitFor.
     *
     * @param  string    $method    the name of the method that was called.
     * @param  array     $arguments the arguments that were passed to the method.
     * @throws Exception if the assertion did not pass before the timeout was exceeded.
     * @return mixed     the result of the lambda if it succeeds.
     */
    public function __call($method, array $arguments)
    {
        return Spinner::waitFor(function () use ($method, $arguments) {
            return call_user_func_array([$this->flexibleContext, $method], $arguments);
        });
    }
}
