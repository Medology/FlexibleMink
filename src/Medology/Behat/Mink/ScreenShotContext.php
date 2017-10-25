<?php namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\FlexibleMink\Context\FlexibleContext;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Testwork\Tester\Result\TestResult;
use Medology\Behat\GathersContexts;
use RuntimeException;

/**
 * Context for capturing screenshots of the web browser.
 *
 * Note: Only works with Mink drivers that support the getScreenshot() method.
 */
class ScreenShotContext implements Context, GathersContexts
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
     * Captures a screenshot and saves it to the artifacts directory.
     *
     * @When  /^(?:I )?take a screenshot(?: named "(?P<name>(?:[^"]|\\")*)")$/
     * @param string $name the name for the screenshot file (excluding path and extension)
     */
    public function takeScreenShot($name = 'screenshot')
    {
        $fileName = date('Ymd-His-') . uniqid('', true) . '.png';
        file_put_contents(
            $this->flexibleContext->getArtifactsDir() . '/' . $name . '-' . $fileName,
            $this->flexibleContext->getSession()->getDriver()->getScreenshot()
        );
    }

    /**
     * Captures a screenshot if the provided scopes result code is failed.
     *
     * Note : If you wish to enable this in your tests, just add a method with an
     *        `AfterStep` annotation to your Context as follows:
     *
     *        public function onFailedStep(AfterStepScope $scope) {
     *          $this->takeScreenShotAfterFailedStep($scope);
     *        }
     *
     * @param AfterStepScope $scope
     */
    public function takeScreenShotAfterFailedStep(AfterStepScope $scope)
    {
        if (TestResult::FAILED === $scope->getTestResult()->getResultCode()) {
            $name = str_replace(' ', '_', $scope->getFeature()->getTitle() . '-' . $scope->getStep()->getText());

            try {
                $this->takeScreenShot($name);
            } catch (UnsupportedDriverActionException $e) {
                // Silently ignore.
            }
        }
    }
}
