<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use InvalidArgumentException;
use ZipArchive;

/**
 * Overwrites some MinkContext step definitions to make them more resilient to failures caused by browser/driver
 * discrepancies and unpredictable load times.
 */
class FlexibleContext extends MinkContext
{
    // Implements.
    use FlexibleContextInterface;

    // Depends.
    use AlertContext;
    use ContainerContext;
    use JavaScriptContext;
    use SpinnerContext;
    use StoreContext;
    use TableContext;
    use TypeCaster;

    /** @var array map of common key names to key codes */
    protected static $keyCodes = [
        'down arrow' => 40,
        'enter'      => 13,
        'return'     => 13,
        'shift tab'  => 2228233,
        'tab'        => 9,
    ];

    /**
     * {@inheritdoc}
     */
    public function assertFieldContains($field, $value)
    {
        $this->waitFor(function () use ($field, $value) {
            parent::assertFieldContains($field, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageAddress($page)
    {
        $this->waitFor(function () use ($page) {
            parent::assertPageAddress($page);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsText($text)
    {
        $text = $this->injectStoredValues($text);

        $this->waitFor(function () use ($text) {
            parent::assertPageContainsText($text);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @Then I should see the following:
     */
    public function assertPageContainsTexts(TableNode $table)
    {
        if (count($table->getRow(0)) > 1) {
            throw new InvalidArgumentException('Arguments must be a single-column list of items');
        }

        foreach ($table->getRows() as $text) {
            $this->assertPageContainsText($text[0]);
        }
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
     *
     * @Then I should see :text appear, then disappear
     */
    public function assertPageContainsTextTemporarily($text)
    {
        $text = $this->injectStoredValues($text);

        $this->waitFor(function () use ($text) {
            parent::assertPageContainsText($text);
        }, 15);

        try {
            $this->waitFor(function () use ($text) {
                parent::assertPageNotContainsText($text);
            }, 15);
        } catch (ExpectationException $e) {
            throw new ResponseTextException(
                "Timed out waiting for '$text' to no longer appear.", $this->getSession()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementContainsText($element, $text)
    {
        $this->waitFor(function () use ($element, $text) {
            parent::assertElementContainsText($element, $text);
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
    public function checkOption($locator)
    {
        $element = $this->waitFor(function() use($locator) {
           return $this->assertVisibleOption($locator);
        });

        $element->check();
    }

    /**
     * {@inheritdoc}
     * @Given the :locator button is :disabled
     * @Then the :locator button should be :disabled
     */
    public function assertButtonDisabled($locator, $disabled = true)
    {
        if (is_string($disabled)) {
            $disabled = 'disabled' == $disabled;
        }

        $button = $this->getSession()->getPage()->findButton($locator);

        if (!$button) {
            throw new ExpectationException("Could not find button for $locator", $this->getSession());
        }

        if ($button->hasAttribute('disabled')) {
            if (!$disabled) {
                throw new ExpectationException(
                    "The button, $locator, was disabled, but it should not have been disabled.",
                    $this->getSession()
                );
            }
        } elseif ($disabled) {
            throw new ExpectationException(
                "The button, $locator, was not disabled, but it should have been disabled.",
                $this->getSession()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertVisibleButton($locator)
    {
        $locator = $this->fixStepArgument($locator);

        $buttons = $this->getSession()->getPage()->findAll('named', ['button', $locator]);

        /** @var NodeElement $button */
        foreach ($buttons as $button) {
            try {
                if ($button->isVisible()) {
                    return $button;
                }
            } catch (UnsupportedDriverActionException $e) {
                return $button;
            }
        }

        throw new ExpectationException("No visible button found for '$locator'", $this->getSession());
    }

    /**
     * {@inheritdoc}
     *
     * @Given the :locator link is visible
     * @Then the :locator link should be visible
     */
    public function assertVisibleLink($locator)
    {
        $locator = $this->fixStepArgument($locator);

        // the link selector in Behat/Min/src/Selector/NamedSelector requires anchor tags have href
        // we don't want that, because some don't, so rip out that section. Ideally we would load our own
        // selector with registerNamedXpath, but I want to re-use the link named selector so we're doing it
        // this way
        $xpath = $this->getSession()->getSelectorsHandler()->selectorToXpath('named', ['link', $locator]);
        $xpath = preg_replace('/\[\.\/@href\]/', '', $xpath);

        /** @var NodeElement[] $links */
        $links = array_filter($this->getSession()->getPage()->findAll('xpath', $xpath), function ($link) {
            /* @var NodeElement $link */
            return $link->isVisible();
        });

        if (empty($links)) {
            throw new ExpectationException("No visible link found for '$locator'", $this->getSession());
        }

        // $links is NOT numerically indexed, so just grab the first element and send it back
        return array_shift($links);
    }

    /**
     * {@inheritdoc}
     */
    public function assertVisibleOption($locator)
    {
        $locator = $this->fixStepArgument($locator);

        $options = $this->getSession()->getPage()->findAll(
            'named',
            ['field', $this->getSession()->getSelectorsHandler()->xpathLiteral($locator)]
        );

        /** @var NodeElement $option */
        foreach ($options as $option) {
            try {
                $visible = $option->isVisible();
            } catch (UnsupportedDriverActionException $e) {
                return $option;
            }

            if ($visible) {
                return $option;
            }
        }

        throw new ExpectationException("No visible option found for '$locator'", $this->getSession());
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
     * @Then I should see the following lines in order:
     */
    public function assertLinesInOrder(TableNode $table)
    {
        if (count($table->getRow(0)) > 1) {
            throw new InvalidArgumentException('Arguments must be a single-column list of items');
        }

        $session = $this->getSession();
        $page = $session->getPage()->getText();

        $lines = $table->getColumn(0);
        $lastPosition = -1;

        foreach ($lines as $line) {
            $line = $this->injectStoredValues($line);

            $position = strpos($page, $line);

            if ($position === false) {
                throw new ExpectationException("Line '$line' was not found on the page", $session);
            }

            if ($position < $lastPosition) {
                throw new ExpectationException("Line '$line' came before its expected predecessor", $session);
            }

            $lastPosition = $position;
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
     * @When /^(?:|I )attach the local file "(?P<path>[^"]*)" to "(?P<field>(?:[^"]|\\")*)"$/
     */
    public function addLocalFileToField($path, $field)
    {
        $field = $this->fixStepArgument($field);

        if ($this->getMinkParameter('files_path')) {
            $fullPath = rtrim(realpath($this->getMinkParameter('files_path')),
                    DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
            if (is_file($fullPath)) {
                $path = $fullPath;
            }
        }

        $tempZip = tempnam('', 'WebDriverZip');
        $zip = new ZipArchive();
        $zip->open($tempZip, ZipArchive::CREATE);
        $zip->addFile($path, basename($path));
        $zip->close();

        $remotePath = $this->getSession()->getDriver()->getWebDriverSession()->file([
            'file' => base64_encode(file_get_contents($tempZip)),
        ]);

        $this->attachFileToField($field, $remotePath);

        unlink($tempZip);
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:I |)(?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     */
    public function blurField($locator)
    {
        $this->assertFieldExists($locator)->blur();
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:I |)focus and (?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @When /^(?:I |)toggle focus (?:on|of) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     */
    public function focusBlurField($locator)
    {
        $this->focusField($locator);
        $this->blurField($locator);
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:I |)focus (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     */
    public function focusField($locator)
    {
        $this->assertFieldExists($locator)->focus();
    }

     /**
      * {@inheritdoc}
      *
      * @When /^(?:I |)(?:hit|press) (?:the |)"(?P<key>[^"]+)" key$/
      */
     public function hitKey($key)
     {
         if (!array_key_exists($key, self::$keyCodes)) {
             throw new ExpectationException("The key '$key' is not defined.", $this->getSession());
         }

         $script = "jQuery.event.trigger({ type : 'keypress', which : '" . self::$keyCodes[$key] . "' });";
         $this->getSession()->evaluateScript($script);
     }

    /**
     * {@inheritdoc}
     */
    public function pressButton($locator)
    {
        $element = $this->waitFor(function () use ($locator) {
            return $this->assertVisibleButton($locator);
        });

        $element->press();
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:I |)scroll to the (?P<where>top|bottom) of the page$/
     * @Given /^the page is scrolled to the (?P<where>top|bottom)$/
     */
    public function scrollWindowToBody($where)
    {
        $x = ($where == 'top') ? '0' : 'document.body.scrollHeight';

        $this->getSession()->executeScript("window.scrollTo(0, $x)");
    }

    /**
     * {@inheritdoc}
     */
    public function visit($page)
    {
        parent::visit($this->injectStoredValues($page));
    }
}
