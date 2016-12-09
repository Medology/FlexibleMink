<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\AlertContextInterface;
use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\Mink\Exception\ExpectationException;
use WebDriver\Exception\NoAlertOpenError;

/**
 * {@inheritdoc}
 */
trait AlertContext
{
    // Implements.
    use AlertContextInterface;
    // Depends.
    use FlexibleContextInterface;

    /**
     * Clears out any alerts or prompts that may be open.
     *
     * @AfterScenario
     * @Given there are no alerts on the page
     */
    public function clearAlerts()
    {
        try {
            $this->cancelAlert();
        } catch (NoAlertOpenError $e) {
            // Ok, no alert was open anyway.
        }
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:|I )confirm the alert$/
     */
    public function confirmAlert()
    {
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:|I )cancel the alert$/
     */
    public function cancelAlert()
    {
        $this->getSession()->getDriver()->getWebDriverSession()->dismiss_alert();
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^(?:|I )should see an alert containing "(?P<expected>[^"]*)"$/
     */
    public function assertAlertMessage($expected)
    {
        try {
            $actual = $this->getSession()->getDriver()->getWebDriverSession()->getAlert_text();
        } catch (NoAlertOpenError $e) {
            throw new ExpectationException('No alert is open', $this->getSession());
        }

        if (strpos($actual, $expected) === false) {
            throw new ExpectationException("Text '$expected' not found in alert", $this->getSession());
        }
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:|I )fill "(?P<message>[^"]*)" into the prompt$/
     */
    public function setAlertText($message)
    {
        $this->getSession()->getDriver()->getWebDriverSession()->postAlert_text(['text' => $message]);
    }
}
