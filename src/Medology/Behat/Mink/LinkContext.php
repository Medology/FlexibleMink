<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;
use Exception;
use Medology\Spinner;

/**
 * Context for handling links in the page.
 *
 * Class LinkContext
 */
class LinkContext implements Context
{
    use UsesFlexibleContext;

    /**
     * Asserts that the canonical tag points to the given location.
     *
     * @param  string               $destination The location the link should be pointing to.
     * @throws Exception            if the assertion did not pass before the timeout was exceeded.
     * @throws ExpectationException When the canonical tag does not contain the given destination.
     *
     * @Then the canonical tag should point to :destination
     */
    public function assertCanonicalTagLocation($destination)
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        Spinner::waitFor(function () use ($destination) {
            $this->flexibleContext->assertNodesHaveAttributeValues(
                '//link[@rel="canonical"]',
                ['href' => $destination],
                'xpath',
                1
            );
        });
    }
}
