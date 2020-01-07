<?php

namespace features\Extensions\Assertion;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\UndefinedStepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Result\TestResult;
use Exception;

/**
 * A context trait for testing assertions and checking their results.
 */
trait AssertionContext
{
    /** @var StepTester The handler for running given steps. Injected by the assertion extension. */
    public $stepTester;

    /** @var Environment The testing environment being run. */
    private $env;

    /** @var FeatureNode The current feature being executed. */
    private $feature;

    /** @var ExecutedStepResult The result of the last checked assertion. */
    private $result;

    /**
     * Grabs execution details that step definitions in the context need but can't access themselves.
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function prepareToRunAssertion(BeforeScenarioScope $scope)
    {
        $this->env = $scope->getEnvironment();
        $this->feature = $scope->getFeature();
    }

    /**
     * Runs an assertion step and stores the result for later checking.
     *
     * @When /^I assert that (?P<assertion>.+)$/
     *
     * @param string                      $assertion The step to assert.
     * @param PyStringNode|TableNode|null $argument  An additional argument to pass to the assertion.
     *
     * @throws Exception if the step is not defined or otherwise did not execute.
     */
    public function runAssertion($assertion, $argument = null)
    {
        $arguments = $argument ? [$argument] : [];

        $step = new StepNode('Then', $assertion, $arguments, 1, 'Then');

        $this->result = $this->stepTester->test($this->env, $this->feature, $step, false);

        if ($this->result instanceof UndefinedStepResult) {
            throw new Exception('The given step is not defined');
        }

        if (!$this->result instanceof ExecutedStepResult) {
            throw new Exception('The step was not properly executed');
        }

        if ($this->result->getResultCode() == TestResult::PENDING) {
            // Step threw a PendingException. Pass it along.
            throw $this->result->getException();
        }
    }

    /**
     * Asserts that the assertion passed.
     *
     * @Then the assertion should pass
     *
     * @throws Exception of the assertion if it failed.
     */
    public function assertAssertionPassed()
    {
        if (!$this->result->isPassed()) {
            throw $this->result->getException();
        }
    }

    /**
     * Asserts that the assertion threw an exception with the given type.
     *
     * @Then /^the assertion should throw an? (?P<exceptionType>.+)$/
     *
     * @param string $exceptionType The short classname of the exception to look for.
     *
     * @throws Exception if an Exception was not thrown or was not of the given type.
     */
    public function assertAssertionException($exceptionType)
    {
        if ($this->result->isPassed()) {
            throw new Exception('Assertion did not throw any exception');
        }

        $exception = $this->result->getException();
        $actual = end(explode('\\', get_class($exception)));

        if ($actual != $exceptionType) {
            throw new Exception("Assertion threw $actual but $exceptionType was expected");
        }
    }

    /**
     * Asserts that the assertion failed with the given message.
     *
     * @Then the assertion should fail with the message :message
     *
     * @param string $message The error message the assertion should have thrown.
     *
     * @throws Exception if the assertion did not fail as expected.
     */
    public function assertAssertionFailedMessage($message)
    {
        if ($this->result->isPassed()) {
            throw new Exception('Assertion passed but should not have');
        }

        $actual = $this->result->getException()->getMessage();

        if ($actual != $message) {
            throw new Exception("Expected message '$message', got '$actual'");
        }
    }
}
