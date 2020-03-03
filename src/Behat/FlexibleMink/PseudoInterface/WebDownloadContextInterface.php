<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

/**
 * Provides functionality for working with downloaded files via a web browser.
 */
trait WebDownloadContextInterface
{
    /**
     * Downloads the file references by the link and stores the content under the given key in the store.
     *
     * @param string $locator the id|label of the link
     * @param string $key     the key to store the content under, defaulting to "Download"
     * @param string $headers these are headers that may be needed depending on the item being downloaded
     */
    abstract public function downloadViaLink($locator, $key = 'Download', $headers = '');

    /**
     * Downloads the specified file and stores the content under the given key in the store.
     *
     * @param string $file          the URL for the file to download
     * @param string $key           the key to store the content under, defaulting to "Download"
     * @param string $headersString headers to pass with the curl request
     */
    abstract public function download($file, $key = 'Download', $headersString = '');

    /**
     * Generates a url with a full url.  If none is specified, current url is used.
     *
     * @param string $link url string to determine full url for
     *
     * @throws ExpectationException if Base url could not be generated
     *
     * @return string
     */
    abstract public function getFullUrl($link);

    /**
     * This method checks if the image for an <img> tag actually loaded.
     *
     * @param string $xpath The xpath of the <img> tag to check
     *
     * @throws ElementNotFoundException If an <img> tag was not found at {@paramref $xpath}
     *
     * @return bool True if image loaded, false otherwise
     */
    abstract public function checkImageLoaded($xpath);
}
