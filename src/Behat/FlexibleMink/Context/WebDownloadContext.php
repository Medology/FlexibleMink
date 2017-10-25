<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Behat\FlexibleMink\PseudoInterface\WebDownloadContextInterface;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Exception;
use Medology\Spinner;

/**
 * {@inheritdoc}
 */
trait WebDownloadContext
{
    // Implements
    use WebDownloadContextInterface;
    // Depends
    use FlexibleContextInterface;
    use StoreContextInterface;

    protected static $baseUrlRegExp = '/^((http(s|):[\/]{2}|)([a-zA-Z]+\.|)[a-zA-Z0-9]+\.[a-zA-Z]+(\:[\d]+|)|[a-zA-Z0-9]+)/';

    /**
     * {@inheritdoc}
     */
    public function downloadViaLink($locator, $key = 'Download', $headers = '')
    {
        $this->download($this->getFullUrl($this->assertVisibleLink($locator)->getAttribute('href')), $key, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getFullUrl($link)
    {
        if (!preg_match(self::$baseUrlRegExp, $link)) {
            $currentUrl = $this->getSession()->getCurrentUrl();

            if (!preg_match(self::$baseUrlRegExp, $currentUrl, $linkParts)) {
                throw new ExpectationException('Could not generate base url from "' . $currentUrl . '"', $this->getSession());
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
     * {@inheritdoc}
     *
     * @When I download the file :file
     * @When I download the file :file to :key
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
        $this->put($response, $key);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function checkImageLoaded($xpath)
    {
        $driver = $this->getSession()->getDriver();
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
