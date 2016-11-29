<?php namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Behat\Hook\Scope\AfterStepScope;

/**
 * Pseudo interface for tracking the methods of the ScreenShotContext.
 *
 * Note: Only works with drivers that support the getScreenshot() method.
 */
trait ScreenShotContextInterface
{
    // Depends
    use MinkContextInterface;
    use TestArtifactContextInterface;

    /**
     * Captures a screenshot and saves it to the artifacts directory.
     *
     * @When  /^(?:I )?take a screenshot(?: named "(?P<name>(?:[^"]|\\")*)")$/
     * @param string $name the name for the screenshot file (excluding path and extension)
     */
    abstract public function takeScreenShot($name = 'screenshot');

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
    abstract public function takeScreenShotAfterFailedStep(AfterStepScope $scope);
}
