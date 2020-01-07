<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\ContainerContextInterface;
use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\FlexibleMink\PseudoInterface\SpinnerContextInterface;
use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Behat\Mink\Exception\ExpectationException;

trait ContainerContext
{
    // Implements.
    use ContainerContextInterface;
    // Depends.
    use FlexibleContextInterface;
    use SpinnerContextInterface;
    use StoreContextInterface;

    /**
     * {@inheritdoc}
     *
     * @Then /^I should see "([^"]*)" in the "([^"]*)" container$/
     */
    public function assertTextInContainer($text, $containerLabel)
    {
        $this->waitFor(function () use ($text, $containerLabel) {
            $text = $this->injectStoredValues($text);
            $containerLabel = $this->injectStoredValues($containerLabel);
            $node = $this->getSession()->getPage()->find('xpath', "//*[contains(text(),'$containerLabel')]");
            if (!$node) {
                throw new ExpectationException("The '$containerLabel' container was not found", $this->getSession());
            }
            $containerId = $node->getAttribute('data-label-for');
            $container = $this->getSession()->getPage()->findById($containerId);

            if (!$container->find('xpath', "//*[contains(text(),'$text')]")) {
                throw new ExpectationException("'$text' was not found in the '$containerLabel' container", $this->getSession());
            }
        });
    }
}
