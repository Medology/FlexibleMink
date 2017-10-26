<?php namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Medology\Behat\GathersContexts;
use Medology\Spinner;
use RuntimeException;
use WebDriver\Exception\NoAlertOpenError;

/**
 * A context for handling JavaScript alerts. Based on a gist by Benjamin Lazarecki with improvements.
 *
 * @link https://gist.github.com/blazarecki/2888851
 */
class AlertContext implements Context, GathersContexts
{
    /** @var FlexibleContext */
    protected $flexibleContext;

    /**
     * {@inheritdoc}
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException(
                'Expected Environment to be ' . InitializedContextEnvironment::class .
                    ', but got ' . get_class($environment)
          );
        }

        if (!$this->flexibleContext = $environment->getContext(FlexibleContext::class)) {
            throw new RuntimeException('Failed to gather FlexibleContext');
        }
    }

    /**
     * Clears out any alerts or prompts that may be open.
     *
     * @AfterScenario @clearAlertsWhenFinished
     * @Given there are no alerts on the page
     * @throws UnsupportedDriverActionException If the current driver does not support cancelling the alert.
     */
    public function clearAlerts()
    {
        if (!$this->flexibleContext->getSession()->getDriver()->isStarted()) {
            return;
        }

        try {
            $this->cancelAlert();
        } catch (NoAlertOpenError $e) {
            // Ok, no alert was open anyway.
        }
    }

    /**
     * Confirms the current JavaScript alert.
     *
     * @When /^(?:|I )confirm the alert$/
     * @throws UnsupportedDriverActionException If the current driver does not support confirming the alert.
     */
    public function confirmAlert()
    {
        $driver = $this->flexibleContext->getSession()->getDriver();

        if (!($driver instanceof Selenium2Driver)) {
            throw new UnsupportedDriverActionException('Confirm Alert is not supported by %s', $driver);
        }

        $driver->getWebDriverSession()->accept_alert();
    }

    /**
     * Cancels the current JavaScript alert.
     *
     * @When   /^(?:|I )cancel the alert$/
     * @throws UnsupportedDriverActionException If the current driver does not support cancelling the alert.
     */
    public function cancelAlert()
    {
        $driver = $this->flexibleContext->getSession()->getDriver();

        if (!($driver instanceof Selenium2Driver)) {
            throw new UnsupportedDriverActionException('Cancel Alert is not supported by %s', $driver);
        }

        $driver->getWebDriverSession()->dismiss_alert();
    }

    /**
     * Asserts that the current JavaScript alert contains the given text.
     *
     * @Then   /^(?:|I )should see an alert containing "(?P<expected>[^"]*)"$/
     * @param  string                           $expected The expected text.
     * @throws ExpectationException             if the given text is not present in the current alert.
     * @throws UnsupportedDriverActionException If the current driver does not support asserting the alert message.
     */
    public function assertAlertMessage($expected)
    {
        $session = $this->flexibleContext->getSession();
        $driver = $session->getDriver();

        if (!($driver instanceof Selenium2Driver)) {
            throw new UnsupportedDriverActionException('Assert Alert message is not supported by %s', $driver);
        }

        Spinner::waitFor(function () use ($expected, $driver, $session) {
            try {
                $actual = $driver->getWebDriverSession()->getAlert_text();
            } catch (NoAlertOpenError $e) {
                throw new ExpectationException('No alert is open', $session);
            }

            if (strpos($actual, $expected) === false) {
                throw new ExpectationException("Text '$expected' not found in alert", $session);
            }
        });
    }

    /**
     * Fills in the given text to the current JavaScript prompt.
     *
     * @When   /^(?:|I )fill "(?P<message>[^"]*)" into the prompt$/
     * @param  string                           $message The text to fill in.
     * @throws UnsupportedDriverActionException If the current driver does not support setting the alert text.
     */
    public function setAlertText($message)
    {
        $driver = $this->flexibleContext->getSession()->getDriver();

        if (!($driver instanceof Selenium2Driver)) {
            throw new UnsupportedDriverActionException('Set Alert text is not supported by %s', $driver);
        }

        $driver->getWebDriverSession()->postAlert_text(['text' => $message]);
    }
}
