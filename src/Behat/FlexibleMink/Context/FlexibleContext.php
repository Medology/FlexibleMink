<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Overwrites some MinkContext step definitions to make them more resilient to failures caused by browser/driver
 * discrepancies and unpredictable load times.
 */
class FlexibleContext extends MinkContext
{
    // Implements.
    use FlexibleContextInterface;

    // Depends.
    use ContainerContext;
    use JavaScriptContext;
    use SpinnerContext;
    use StoreContext;
    use TypeCaster;

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsText($text)
    {
        $this->waitFor(function () use ($text) {
            parent::assertPageContainsText($text);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageNotContainsText($text)
    {
        $this->waitFor(function () use ($text) {
            parent::assertPageNotContainsText($text);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function clickLink($locator)
    {
        $this->assertVisibleLink($locator)->click();
    }

    /**
     * {@inheritdoc}
     */
    public function assertVisibleLink($locator)
    {
        $locator = $this->fixStepArgument($locator);

        $links = $this->getSession()->getPage()->findAll(
            'named',
            ['link', $this->getSession()->getSelectorsHandler()->xpathLiteral($locator)]
        );

        /** @var NodeElement $link */
        foreach ($links as $link) {
            try {
                $visible = $link->isVisible();
            } catch (UnsupportedDriverActionException $e) {
                return $link;
            }

            if ($visible) {
                return $link;
            }
        }

        throw new ExpectationException("No visible link found for '$locator'", $this->getSession());
    }

    /**
     * {@inheritdoc}
     */
    public function assertFieldExists($fieldName)
    {
        /** @var NodeElement[] $fields */
        $fields = $this->getSession()->getPage()->findAll('named', ['field', $fieldName]);
        if (count($fields) == 0) {
            // If the field was not found with the usual way above, attempt to find with label name as last resort
            $label = $this->getSession()->getPage()->find('xpath', "//label[contains(text(), '$fieldName')]");
            if (!$label) {
                throw new ExpectationException("No input label '$fieldName' found", $this->getSession());
            }
            $name = $label->getAttribute('for');
            $fields = [$this->getSession()->getPage()->findField($name)];
        }
        if (count($fields) > 0) {
            foreach ($fields as $field) {
                if ($field->isVisible()) {
                    return $field;
                }
            }
        }
        throw new ExpectationException("No visible input found for '$fieldName'", $this->getSession());
    }

    /**
     * {@inheritdoc}
     */
    public function assertFieldNotExists($fieldName)
    {
        /** @var NodeElement[] $fields */
        $fields = $this->getSession()->getPage()->findAll('named', ['field', $fieldName]);
        if (count($fields) == 0) {
            // If the field was not found with the usual way above, attempt to find with label name as last resort
            /* @var NodeElement[] $label */
            $labels = $this->getSession()->getPage()->findAll('xpath', "//label[contains(text(), '$fieldName')]");
            if (count($labels) > 0) {
                foreach ($labels as $item) {
                    /** @var NodeElement $item */
                    if ($item->isVisible()) {
                        throw new ExpectationException("Input label '$fieldName' found", $this->getSession());
                    }
                }
            }
        } else {
            foreach ($fields as $field) {
                /** @var NodeElement $field */
                if ($field->isVisible()) {
                    throw new ExpectationException("Input label '$fieldName' found", $this->getSession());
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^I should see the following fields:$/
     */
    public function assertPageContainsFields(TableNode $tableNode)
    {
        foreach ($tableNode->getRowsHash() as $field => $value) {
            $this->assertFieldExists($field);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^I should not see the following fields:$/
     */
    public function assertPageNotContainsFields(TableNode $tableNode)
    {
        foreach ($tableNode->getRowsHash() as $field => $value) {
            $this->assertFieldNotExists($field);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the (?P<option>.*?) option(?:|(?P<existence> does not?)) exists? in the (?P<select>.*?) select$/
     */
    public function assertSelectContainsOption($select, $existence, $option)
    {
        $select = $this->fixStepArgument($select);
        $option = $this->fixStepArgument($option);
        $selectField = $this->assertFieldExists($select);
        $opt = $selectField->find('named', ['option', $option]);
        if ($existence && $opt) {
            throw new ExpectationException("The option '" . $option . "' exist in the select", $this->getSession());
        }
        if (!$existence && !$opt) {
            throw new ExpectationException("The option '" . $option . "' not exist in the select", $this->getSession());
        }
    }

    /**
     * Adds or replaces a cookie.
     * Note that you must request a page before trying to set a cookie, in order to set the domain.
     *
     * @When /^(?:|I )set the cookie "(?P<key>(?:[^"]|\\")*)" with value (?P<value>.+)$/
     */
    public function addOrReplaceCookie($key, $value)
    {
        // set cookie:
        $this->getSession()->setCookie($key, $value);
    }

    /**
     * Deletes a cookie.
     *
     * @When /^(?:|I )delete the cookie "(?P<key>(?:[^"]|\\")*)"$/
     */
    public function deleteCookie($key)
    {
        // set cookie:
        $this->getSession()->setCookie($key, null);
    }

    /**
     * {@inheritdoc}
     *
     * @When angular rendered the page
     */
    public function waitForAngular()
    {
        // Wait for angular to load
        $this->getSession()->wait(5000, "typeof angular != 'undefined'");
        // Wait for angular to be testable
        $this->getSession()->evaluateScript(
            'angular.getTestability(document.body).whenStable(function() {
                window.__testable = true;
            })'
        );
        $this->getSession()->wait(5000, 'window.__testable == true');
    }
}
