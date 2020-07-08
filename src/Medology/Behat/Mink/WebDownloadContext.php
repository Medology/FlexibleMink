<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Exception;
use Medology\Behat\UsesStoreContext;
use Medology\Spinner;

/**
 * {@inheritdoc}
 */
class WebDownloadContext implements Context
{
    use UsesFlexibleContext;
    use UsesStoreContext;

    protected static $baseUrlRegExp = '/^((http(s|):[\/]{2}|)([a-zA-Z]+\.|)[a-zA-Z0-9]+\.[a-zA-Z]+(\:[\d]+|)|[a-zA-Z0-9]+)/';

    /**
     * Downloads the file references by the link and stores the content under the given key in the store.
     *
     * @param string $locator the id|label of the link
     * @param string $key     the key to store the content under, defaulting to "Download"
     * @param string $headers these are headers that may be needed depending on the item being downloaded
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
     * @param string $link url string to determine full url for
     *
     * @throws ExpectationException if Base url could not be generated
     *
     * @return string
     */
    public function getFullUrl($link)
    {
        if (!preg_match(self::$baseUrlRegExp, $link)) {
            $currentUrl = $this->flexibleContext->getSession()->getCurrentUrl();

            if (!preg_match(self::$baseUrlRegExp, $currentUrl, $linkParts)) {
                throw new ExpectationException('Could not generate base url from "' . $currentUrl . '"', $this->flexibleContext->getSession());
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
     *
     * @param string $file          the URL for the file to download
     * @param string $key           the key to store the content under, defaulting to "Download"
     * @param string $headersString headers to pass with the curl request
     *
     * @throws Exception if curl could not be initialized for the specified URL
     *
     * @return mixed the curl_exec result of downloading the file
     */
    public function download($file, $key = 'Download', $headersString = '')
    {
        $ch = curl_init($file);
        if ($ch === false) {
            throw new Exception("Could not initialize curl for file $file");
        }

        $headers = [$headersString];

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
     * @param string      $xpath The xpath of the <img> tag to check
     * @param string|null $src   the src value
     *
     * @throws Exception
     *
     * @return bool True if image loaded, false otherwise
     */
    public function checkImageLoaded($xpath, $src = null)
    {
        $driver = $this->flexibleContext->getSession()->getDriver();
        $xpath = str_replace('"', "'", $xpath);

        return Spinner::waitFor(function () use ($driver, $xpath, $src) {
            if (!$driver->find($xpath)) {
                throw new ElementNotFoundException($driver, 'img', 'xpath', $xpath);
            }

            $script = <<<JS
        return {
            complete: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.complete,
            height: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.naturalHeight,
            width: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.naturalWidth,
            src: document.evaluate("{$xpath}", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.currentSrc
.replace(location.protocol.concat("//").concat(window.location.hostname),"")
        }
JS;

            $imgProperties = $driver->evaluateScript($script);

            if (!$imgProperties['complete']) {
                throw new Exception('Image did not finish loading.');
            }

            if (!empty($src) && $imgProperties['src'] !== $src) {
                throw new Exception("The loaded image src is '{$imgProperties['src']}', but expected $src");
            }

            return $imgProperties['width'] !== 0 && $imgProperties['height'] !== 0;
        });
    }
}
