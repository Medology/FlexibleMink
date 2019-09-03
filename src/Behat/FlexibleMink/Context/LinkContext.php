<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\FlexibleMink\PseudoInterface\LinkContextInterface;

/**
 * {@inheritdoc}
 */
trait LinkContext
{
    // Implements.
    use LinkContextInterface;
    // Depends.
    use FlexibleContextInterface;

    /**
     * {@inheritdoc}
     *
     * @Then the canonical tag should point to :destination
     */
    public function assertCanonicalTagLocation($destination)
    {
        $this->waitFor(function () use ($destination) {
            $this->assertNodesHaveAttributeValues('//link[@rel="canonical"]', ['href' => $destination], 'xpath', 1);
        });
    }
}
