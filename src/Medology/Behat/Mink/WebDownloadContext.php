<?php namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Exception;
use Medology\Behat\GathersContexts;
use Medology\Behat\StoreContext;
use Medology\Spinner;
use RuntimeException;

/**
 * {@inheritdoc}
 */
class WebDownloadContext implements Context, GathersContexts
{
    protected static $baseUrlRegExp = '/^((http(s|):[\/]{2}|)([a-zA-Z]+\.|)[a-zA-Z0-9]+\.[a-zA-Z]+(\:[\d]+|)|[a-zA-Z0-9]+)/';

    /** @var FlexibleContext */
    protected $flexibleContext;

    /** @var StoreContext */
    protected $storeContext;

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

        if (!$this->storeContext = $environment->getContext(StoreContext::class)) {
            throw new RuntimeException('Failed to gather StoreContext');
        }

        if (!$this->flexibleContext = $environment->getContext(FlexibleContext::class)) {
            throw new RuntimeException('Failed to gather FlexibleContext');
        }
    }

    /**
     * Downloads the file references by the link and stores the content under the given key in the store.
     *
     * @param string $locator The id|label of the link.
     * @param string $key     The key to store the content under, defaulting to "Download".
     * @param string $headers These are headers that may be needed depending on the item being downloaded.
     */
    public function downloadViaLink($locator, $key = 'Download', $headers = '')
    {
        $this->download(
            $this->getFullUrl($this->flexibleContext->assertVisibleLink($locator)->getAttribute('href')),
            $key,
            $headers
        );
    }

    /**
     * Generates a url with a full url.  If none is specified, current url is used.
     *
     * @param  string               $link url string to determine full url for.
     * @throws ExpectationException If Base url could not be generated.
     * @return string
     */
    public function getFullUrl($link)
    {
        if (!preg_match(self::$baseUrlRegExp, $link)) {
            $currentUrl = $this->flexibleContext->getSession()->getCurrentUrl();

            if (!preg_match(self::$baseUrlRegExp, $currentUrl, $linkParts)) {
                throw new ExpectationException(
                    'Could not generate base url from "' . $currentUrl . '"',
                    $this->flexibleContext->getSession()
                );
            }

            // Checks if URL is relative.
            if (strpos($link, '/') === 0) {
                // Append to base URL
                $link = $linkParts[0] . $link;
            } else {
                // Resolve the relative URL to a fully qualified URL
                $link = substr($currentUrl, 0, strpos($currentUrl, '/')) . $link;
            }
        }

        return $link;
    }

    /**
     * Downloads the specified file and stores the content under the given key in the store.
     *
     * @When   I download the file :file
     * @When   I download the file :file to :key
     * @param  string $file          The URL for the file to download.
     * @param  string $key           The key to store the content under, defaulting to "Download".
     * @param  string $headersString Headers to pass with the curl request.
     * @return mixed  the curl_exec result of downloading the file
     */
    public function download($file, $key = 'Download', $headersString = '')
    {
        $ch = curl_init($file);
        $headers[] = $headersString;

        curl_setopt_array($ch, [
            CURLOPT_HEADER         => 0,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_BINARYTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $response = curl_exec($ch);

        // Put response into object store.
        $this->storeContext->set($key, $response);

        return $response;
    }

    /**
     * This method checks if the image for an <img> tag actually loaded.
     *
     * @param  string                   $xpath The xpath of the <img> tag to check
     * @throws ElementNotFoundException If an <img> tag was not found at $xpath
     * @return bool                     True if image loaded, false otherwise
     */
    public function checkImageLoaded($xpath)
    {
        $driver = $this->flexibleContext->getSession()->getDriver();
        $xpath = str_replace('"', "'", $xpath);

        $result = Spinner::waitFor(function () use ($driver, $xpath) {
            if (!$driver->find($xpath)) {
                throw new ElementNotFoundException($driver, 'img', 'xpath', $xpath);
            }

            $script = <<<JS
return {
    complete: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.complete,
    height: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.naturalHeight,
    width: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.naturalWidth
}
JS;

            $imgProperties = $driver->evaluateScript($script);

            if (!$imgProperties['complete']) {
                throw new Exception('Image did not finish loading.');
            }

            return $imgProperties;
        });

        return $result['width'] !== 0 && $result['height'] !== 0;
    }
}
