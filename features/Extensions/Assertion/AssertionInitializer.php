<?php

namespace features\Extensions\Assertion;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Behat\Tester\StepTester;

/**
 * Injects the StepTester into contexts that implement the AssertionContext trait.
 */
class AssertionInitializer implements ContextInitializer
{
    /** @var StepTester */
    private $tester;

    /**
     * Stores an injected StepTester to add to AssertionContexts later.
     *
     * @param StepTester $tester the step tester to inject into AssertionContexts
     */
    public function __construct(StepTester $tester)
    {
        $this->tester = $tester;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        /*
         * Checking for traits is difficult; instanceof doesn't support them, and class_uses only goes down one level,
         * so it's not reliable. The best we can do is look for the stepTester property on the context, since
         * AssertionContext contains that field.
         */
        if (property_exists($context, 'stepTester')) {
            $context->stepTester = $this->tester;
        }
    }
}
