<?php

use Behat\FlexibleMink\Context\CsvContext;
use Behat\FlexibleMink\Context\FlexibleContext;
use Behat\FlexibleMink\Context\TypeCaster;
use Behat\FlexibleMink\Context\WebDownloadContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use features\Extensions\Assertion\AssertionContext;

class FeatureContext extends FlexibleContext
{
    // Depends
    use AssertionContext;
    use CsvContext;
    use TypeCaster;
    use WebDownloadContext;

    /**
     * Places an object with the given structure into the store.
     *
     * @Given the following is stored as :key:
     *
     * @param string    $key        the key to put the object into the store under
     * @param TableNode $attributes the attributes of the object to create
     */
    public function putStoreStep($key, TableNode $attributes)
    {
        $this->put((object) ($attributes->getRowsHash()), $key);
    }

    /**
     * Places the given arbitrary value into the store.
     *
     * @Given /^the value (?P<value>.+) is stored as (?P<key>".+")$/
     * @Given the following string is stored as :key:
     *
     * @param mixed  $value the value to put into the store
     * @param string $key   the key to put the value into the store under
     */
    public function putSingleStoreStep($value, $key)
    {
        if ($value instanceof PyStringNode) {
            $value = $value->getRaw();
        }

        $this->put($value, $key);
    }

    /**
     * {@inheritdoc}
     *
     * Decreases the default timeout for the sake of testing failing assertions more quickly.
     */
    public function waitFor(callable $lambda, $timeout = 5)
    {
        return parent::waitFor($lambda, $timeout);
    }

    /**
     * Waits a specific amount of time, and then visits the specified path.
     *
     * @Given I will be on :path in :timeout seconds
     *
     * @param string $path    the path to visit
     * @param int    $timeout the time to wait before visiting the path
     */
    public function visitPathDelayed($path, $timeout)
    {
        $path = json_encode($path); // Quick and painless quotation wrapping + escaping.
        $timeout *= 1000;

        $this->getSession()->executeScript(
<<<JS
            window.setTimeout(function() {
                window.location = $path;
            }, $timeout);
JS
        );
    }

    /**
     * Causes a prompt/alert to pop up in the browser, and stores its return value in the store.
     *
     * @Given /^there is an? (?P<type>alert|confirm|prompt) containing (?P<text>".+")$/
     *
     * @param string $type whether to create an alert, confirmation dialog, or prompt
     * @param string $text the text to show in the popup
     */
    public function ensureAlertExists($type, $text)
    {
        $text = json_encode($text); // Free character escaping, quoting, etc.

        $this->getSession()->executeScript("{$type}_result = $type($text)");
    }

    /**
     * Asserts that the prompt from ensureAlertExists returns the correct value.
     *
     * @Then /^the (?P<type>alert|confirm|prompt) should return (?P<value>.+)$/
     *
     * @param string $type   the type of popup to check results for
     * @param mixed  $result the expected result
     *
     * @throws ExpectationException if the actual result does not match the expected results
     */
    public function assertAlertResult($type, $result)
    {
        $actual = $this->getSession()->evaluateScript("{$type}_result");

        if ($actual !== $result) {
            $expected = json_encode($result);
            $actual = json_encode($actual);

            throw new ExpectationException("Expected $expected, got $actual", $this->getSession());
        }
    }

    /**
     * Asserts that an image finished loading.
     *
     * @Then I should see :imgSrc image in :locator
     *
     * @param string $imgSrc  The source of the image
     * @param string $locator The id of the image tag
     *
     * @throws ExpectationException If the <img> tag is not found
     * @throws ExpectationException If the image is not loaded
     *
     * @return true
     */
    public function assertImageLoaded($imgSrc, $locator)
    {
        $session = $this->getSession();
        $image = $session->getPage()->find('css', "img#$locator");

        if (!$image) {
            throw new ExpectationException("Expected an img tag with id '$locator'. Found none!", $session);
        }

        if ($image->getAttribute('src') != $imgSrc) {
            throw new ExpectationException("Expected src '$imgSrc'. Instead got '" . $image->getAttribute('src') . "'.", $session);
        }

        if (!$this->checkImageLoaded($image->getXpath())) {
            throw new ExpectationException("Expected img '$locator' to load. Instead it did not!", $session);
        }

        return true;
    }

    /**
     * Asserts that an image did NOT load.
     *
     * @Then I should not see an image in :locator
     *
     * @param string $locator The id of the image tag
     *
     * @throws ExpectationException If the <img> tag is not found
     * @throws ExpectationException If the image is loaded
     *
     * @return true
     */
    public function assertImageNotLoaded($locator)
    {
        $image = $this->getSession()->getPage()->find('css', "img#$locator");

        if (!$image) {
            throw new ExpectationException("Expected an img tag with id '$locator'. Found none!", $this->getSession());
        }

        if ($this->checkImageLoaded($image->getXpath())) {
            throw new ExpectationException("Expected img '$locator' to not load. Instead it did load!", $this->getSession());
        }

        return true;
    }
}
