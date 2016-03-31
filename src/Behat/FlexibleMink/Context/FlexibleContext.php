<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Overwrites some MinkContext step definitions to make them more resilient to failures caused by browser/driver
 * discrepancies and unpredictable load times.
 */
class FlexibleContext extends MinkContext
{
    use FlexibleContextInterface;
    use SpinnerContext;
    use StoreContext;

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsText($text)
    {
        $this->waitFor(function () use ($text) {
            parent::assertPageContainsText($text);
        });
    }
}