<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\Models\Geometry\Rectangle;
use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use Exception;
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
    use QualityAssurance;

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
        $field = $this->injectStoredValues($field);
        $value = $this->injectStoredValues($value);

        $this->waitFor(function () use ($field, $value) {
            parent::assertFieldContains($field, $value);
        });
    }

    /**
     * {@inheritdoc}
     *
     * Overrides the base method to support injecting stored values and matching URLs that include hostname.
     */
    public function assertPageAddress($page)
    {
        $page = $this->injectStoredValues($page);

        $this->waitFor(function () use ($page) {
            // is the page a path, or a full URL?
            if (preg_match('!^https?://!', $page) == 0) {
                // it's just a path. delegate to parents implementation
                parent::assertPageAddress($page);
            } else {
                // it's a full URL, compare manually
                $actual = $this->getSession()->getCurrentUrl();

                if (!strpos($actual, $page) === 0) {
                    throw new ExpectationException(
                        sprintf('Current page is "%s", but "%s" expected.', $actual, $page),
                        $this->getSession()
                    );
                }
            }
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
     * @Then /^I should (?:|(?P<not>not ))see the following:$/
     */
    public function assertPageContainsTexts(TableNode $table, $not = null)
    {
        if (count($table->getRow(0)) > 1) {
            throw new InvalidArgumentException('Arguments must be a single-column list of items');
        }

        foreach ($table->getRows() as $text) {
            if ($not) {
                $this->assertPageNotContainsText($text[0]);
            } else {
                $this->assertPageContainsText($text[0]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageNotContainsText($text)
    {
        $text = $this->injectStoredValues($text);
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
     * @Then /^the field "(?P<field>[^"]+)" should(?P<not> not|) be visible$/
     */
    public function assertFieldVisibility($field, $not)
    {
        $locator = $this->fixStepArgument($field);

        $fields = $this->getSession()->getPage()->findAll(
          'named',
          ['field', $this->getSession()->getSelectorsHandler()->xpathLiteral($locator)]
        );

        if (count($fields) > 1) {
            throw new ExpectationException("The field '$locator' was found more than one time", $this->getSession());
        }

        $shouldBeVisible = !$not;
        if (($shouldBeVisible && !$fields[0]->isVisible()) || (!$shouldBeVisible && $fields[0]->isVisible())) {
            throw new ExpectationException("The field '$locator' was " . (!$not ? 'not ' : '') . 'visible or not found', $this->getSession());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementContainsText($element, $text)
    {
        $element = $this->injectStoredValues($element);
        $text = $this->injectStoredValues($text);

        $this->waitFor(function () use ($element, $text) {
            parent::assertElementContainsText($element, $text);
        });
    }

    /**
     * Asserts that an element with the given XPath is present in the container, and returns it.
     *
     * @param  NodeElement          $container The base element to search in.
     * @param  string               $xpath     The XPath of the element to locate inside the container.
     * @throws DriverException      When the operation cannot be done
     * @throws ExpectationException if no element was found.
     * @return NodeElement          The found element.
     */
    public function assertElementInsideElement(NodeElement $container, $xpath)
    {
        return $this->waitFor(function () use ($container, $xpath) {
            if (!$element = $container->find('xpath', $xpath)) {
                throw new ExpectationException('Nothing found inside element with xpath $xpath', $this->getSession());
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function assertElementNotContainsText($element, $text)
    {
        $element = $this->injectStoredValues($element);
        $text = $this->injectStoredValues($text);

        $this->waitFor(function () use ($element, $text) {
            parent::assertElementNotContainsText($element, $text);
        });
    }

    /**
     * {@inheritdoc}
     *
     * Overrides the base method to wait for the assertion to pass, and store
     * the resulting element in the store under "element".
     *
     * @throws ElementNotFoundException if the element was not found.
     * @return NodeElement              The element found.
     */
    public function assertElementOnPage($element, $selectorType = 'css')
    {
        $node = $this->waitFor(function () use ($element, $selectorType) {
            return $this->assertSession()->elementExists($selectorType, $element);
        });

        $this->put($node, 'element');

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function clickLink($locator)
    {
        $locator = $this->injectStoredValues($locator);
        $element = $this->waitFor(function () use ($locator) {
            return $this->assertVisibleLink($locator);
        });

        $element->click();
    }

    /**
     * {@inheritdoc}
     */
    public function checkOption($locator)
    {
        $locator = $this->injectStoredValues($locator);
        $element = $this->waitFor(function () use ($locator) {
            return $this->assertVisibleOption($locator);
        });

        $element->check();
    }

    /**
     * {@inheritdoc}
     */
    public function fillField($field, $value)
    {
        $field = $this->injectStoredValues($field);
        $value = $this->injectStoredValues($value);
        $element = $this->waitFor(function () use ($field) {
            return $this->assertFieldExists($field);
        });

        $element->setValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function uncheckOption($locator)
    {
        $locator = $this->injectStoredValues($locator);
        $element = $this->waitFor(function () use ($locator) {
            return $this->assertVisibleOption($locator);
        });

        $element->uncheck();
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

        $this->waitFor(function () use ($locator, $disabled) {
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
        });
    }

    /**
     * Asserts that the specified button exists in the DOM.
     *
     * @noinspection PhpDocRedundantThrowsInspection exceptions bubble up from waitFor
     * @Then   I should see a :locator button
     * @param  string                           $locator The id|name|title|alt|value of the button.
     * @throws Exception                        If the timeout expired before the assertion could be ran even once.
     * @throws DriverException                  When the operation cannot be done.
     * @throws ExpectationException             If no button was found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return NodeElement                      The button.
     */
    public function assertButtonExists($locator)
    {
        return $this->waitFor(function () use ($locator) {
            $locator = $this->fixStepArgument($locator);

            if (!$button = $this->getSession()->getPage()->find('named', ['button', $locator])) {
                throw new ExpectationException("No button found for '$locator'", $this->getSession());
            }

            return $button;
        });
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
    public function assertFieldExists($fieldName, TraversableElement $context = null)
    {
        $context = $context ?: $this->getSession()->getPage();

        /** @var NodeElement[] $fields */
        $fields = ($context->findAll('named', ['field', $fieldName]) ?: $this->getInputsByLabel($fieldName, $context));

        foreach ($fields as $field) {
            if ($field->isVisible()) {
                return $field;
            }
        }

        throw new ExpectationException("No visible input found for '$fieldName'", $this->getSession());
    }

    /**
     * {@inheritdoc}
     */
    public function getInputsByLabel($labelName, TraversableElement $context)
    {
        /** @var NodeElement[] $labels */
        $labels = $context->findAll('xpath', "//label[contains(text(), '$labelName')]");
        $found = [];

        foreach ($labels as $label) {
            $inputName = $label->getAttribute('for');

            foreach ($context->findAll('named', ['field', $inputName]) as $element) {
                if (!in_array($element, $found)) {
                    array_push($found, $element);
                }
            }
        }

        return $found;
    }

    /**
     * {@inheritdoc}
     */
    public function assertFieldNotExists($fieldName)
    {
        try {
            $this->assertFieldExists($fieldName);
        } catch (ExpectationException $e) {
            return;
        }

        throw new ExpectationException("Input label '$fieldName' found", $this->getSession());
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
            throw new ExpectationException("The option '" . $option . "' does not exist in the select", $this->getSession());
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the "(?P<select>[^"]*)" select should only have the following option(?:|s):$/
     */
    public function assertSelectContainsExactOptions($select, TableNode $tableNode)
    {
        if (count($tableNode->getRow(0)) > 1) {
            throw new InvalidArgumentException('Arguments must be a single-column list of items');
        }

        $expectedOptTexts = array_map([$this, 'injectStoredValues'], $tableNode->getColumn(0));
        $select = $this->fixStepArgument($select);
        $select = $this->injectStoredValues($select);

        $this->waitFor(function () use ($expectedOptTexts, $select) {
            $selectField = $this->assertFieldExists($select);
            $actualOpts = $selectField->findAll('xpath', '//option');

            if (count($actualOpts) == 0) {
                throw new ExpectationException('No option found in the select', $this->getSession());
            }

            $actualOptTexts = array_map(function ($actualOpt) {
                /* @var NodeElement $actualOpt */
                return $actualOpt->getText();
            }, $actualOpts);

            if (count($actualOptTexts) > count($expectedOptTexts)) {
                throw new ExpectationException('Select has more option then expected', $this->getSession());
            }

            if (count($actualOptTexts) < count($expectedOptTexts)) {
                throw new ExpectationException('Select has less option then expected', $this->getSession());
            }

            if ($actualOptTexts != $expectedOptTexts) {
                $intersect = array_intersect($actualOptTexts, $expectedOptTexts);

                if (count($intersect) < count($expectedOptTexts)) {
                    throw new ExpectationException(
                        'Expecting ' . count($expectedOptTexts) . ' matching option(s), found ' . count($intersect),
                        $this->getSession()
                    );
                }

                throw new ExpectationException(
                    'Options in select match expected but not in expected order',
                    $this->getSession()
                );
            }
        });
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
     * Returns all cookies.
     *
     * @throws Exception                        If the operation failed.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return array                            Key/value pairs of cookie name/value.
     */
    public function getCookies()
    {
        $driver = $this->assertSelenium2Driver('Get all cookies');

        $cookies = [];
        foreach ($driver->getWebDriverSession()->getAllCookies() as $cookie) {
            $cookies[$cookie['name']] = urldecode($cookie['value']);
        }

        return $cookies;
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
     * Deletes all cookies.
     *
     * @When   /^(?:|I )delete all cookies$/
     * @throws DriverException                  When the operation cannot be performed.
     * @throws Exception                        If the operation failed.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function deleteCookies()
    {
        $this->assertSelenium2Driver('Delete all cookies')->getWebDriverSession()->deleteAllCookies();
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:|I )attach the local file "(?P<path>[^"]*)" to "(?P<field>(?:[^"]|\\")*)"$/
     */
    public function addLocalFileToField($path, $field)
    {
        $driver = $this->assertSelenium2Driver('Add local file to field');

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

        /** @noinspection PhpUndefinedMethodInspection file() method annotation is missing from WebDriver\Session */
        $remotePath = $driver->getWebDriverSession()->file([
            'file' => base64_encode(file_get_contents($tempZip)),
        ]);

        $this->attachFileToField($field, $remotePath);

        unlink($tempZip);
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection Exceptions bubble up from waitFor.
     *
     * {@inheritdoc}
     *
     * @throws Exception            if the timeout expired before a single try could be attempted.
     * @throws ExpectationException if the value of the input does not match expected after the file is attached.
     */
    public function attachFileToField($field, $path)
    {
        $this->waitFor(function () use ($field, $path) {
            parent::attachFileToField($field, $path);

            $session = $this->getSession();
            $value = $session->getPage()->findField($field)->getValue();

            // Workaround for browser's fake path stuff that obscures the directory of the attached file.
            $fileParts = explode(DIRECTORY_SEPARATOR, $path);
            $filename = end($fileParts); // end() cannot take inline expressions, only variables.

            if (strpos($value, $filename) === false) {
                throw new ExpectationException(
                    "Value of $field is '$value', expected to contain '$filename'",
                    $session
                );
            }
        });
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
        /** @var NodeElement $button */
        $button = $this->waitFor(function () use ($locator) {
            return $this->assertVisibleButton($locator);
        });

        $this->waitFor(function () use ($button, $locator) {
            if ($button->getAttribute('disabled') === 'disabled') {
                throw new ExpectationException("Unable to press disabled button '$locator'.", $this->getSession());
            }
        });

        $button->press();
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection exceptions bubble up from waitFor.
     *
     * {@inheritdoc}
     *
     * Overrides the base method to support injecting stored values and restricting interaction to visible options.
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ElementNotFoundException         when the option is not found in the select box
     * @throws Exception                        If the string references something that does not exist in the store.
     * @throws Exception                        If the timeout expires and the lambda has thrown a Exception.
     * @throws ExpectationException             If a visible select was not found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    public function selectOption($select, $option)
    {
        $select = $this->injectStoredValues($select);
        $option = $this->injectStoredValues($option);

        /** @var NodeElement $field */
        $field = $this->waitFor(function () use ($select) {
            return $this->assertVisibleOptionField($select);
        });

        $field->selectOption($option);
    }

    /**
     * Finds all of the matching selects or radios on the page.
     *
     * @param  string                           $locator The id|name|label|value|placeholder of the select or radio.
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @return NodeElement[]
     */
    public function getOptionFields($locator)
    {
        return array_filter(
            $this->getSession()->getPage()->findAll('named', ['field', $locator]),
            function (NodeElement $field) {
                return $field->getTagName() == 'select' || $field->getAttribute('type') == 'radio';
            }
        );
    }

    /**
     * Finds the first matching visible select or radio on the page.
     *
     * @param  string                           $locator The id|name|label|value|placeholder of the select or radio.
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             If a visible select was not found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @return NodeElement                      The select or radio.
     */
    public function assertVisibleOptionField($locator)
    {
        foreach ($this->getOptionFields($locator) as $field) {
            if ($field->isVisible()) {
                return $field;
            }
        }

        throw new ExpectationException("No visible selects or radios for '$locator' were found", $this->getSession());
    }

    /**
     * {@inheritdoc}
     *
     * @When /^(?:I |)scroll to the (?P<where>[ a-z]+) of the page$/
     * @Given /^the page is scrolled to the (?P<where>top|bottom)$/
     */
    public function scrollWindowToBody($where)
    {
        // horizontal scroll
        if (strpos($where, 'left') !== false) {
            $x = 0;
        } elseif (strpos($where, 'right') !== false) {
            $x = 'document.body.scrollWidth';
        } else {
            $x = 'window.scrollX';
        }

        // vertical scroll
        if (strpos($where, 'top') !== false) {
            $y = 0;
        } elseif (strpos($where, 'bottom') !== false) {
            $y = 'document.body.scrollHeight';
        } else {
            $y = 'window.scrollY';
        }

        $this->getSession()->executeScript("window.scrollTo($x, $y)");
    }

    /**
     * {@inheritdoc}
     */
    public function visit($page)
    {
        parent::visit($this->injectStoredValues($page));
    }

    /**
     * {@inheritdoc}
     *
     * Overrides the base method to inject stored values into the argument, and wait for the assertion to pass.
     */
    public function assertCheckboxChecked($checkbox)
    {
        $checkbox = $this->injectStoredValues($checkbox);

        $this->waitFor(function () use ($checkbox) {
            parent::assertCheckboxChecked($checkbox);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function assertCheckboxNotChecked($checkbox)
    {
        $checkbox = $this->injectStoredValues($checkbox);
        parent::assertCheckboxNotChecked($checkbox);
    }

    /**
     * {@inheritdoc}
     *
     * @When I check radio button :label
     */
    public function ensureRadioButtonChecked($label)
    {
        $this->findRadioButton($label)->click();
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the "(?P<label>(?:[^"]|\\")*)" radio button should be checked$/
     */
    public function assertRadioButtonChecked($label)
    {
        if (!$this->findRadioButton($label)->isChecked()) {
            throw new ExpectationException("Radio button \"$label\" is not checked, but it should be.", $this->getSession());
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the "(?P<label>(?:[^"]|\\")*)" radio button should not be checked$/
     */
    public function assertRadioButtonNotChecked($label)
    {
        if ($this->findRadioButton($label)->isChecked()) {
            throw new ExpectationException("Radio button \"$label\" is checked, but it should not be.", $this->getSession());
        }
    }

    /**
     * Checks if a node has the specified attribute values.
     *
     * @param  NodeElement                      $node       The node to check the expected attributes against.
     * @param  array                            $attributes An associative array of the expected attributes.
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @return bool                             true if the element has the specified attribute values, false if not.
     */
    public function elementHasAttributeValues(NodeElement $node, array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if ($node->getAttribute($name) != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Locate the radio button by label.
     *
     * @param  string      $label The Label of the radio button.
     * @return NodeElement
     */
    protected function findRadioButton($label)
    {
        $label = $this->injectStoredValues($label);
        $this->fixStepArgument($label);

        $radioButton = $this->waitFor(function () use ($label) {
            /** @var NodeElement[] $radioButtons */
            $radioButtons = $this->getSession()->getPage()->findAll('named', ['radio', $label]);

            if (!$radioButtons) {
                throw new ExpectationException('Radio Button was not found on the page', $this->getSession());
            }

            $radioButtons = array_filter($radioButtons, function (NodeElement $radio) {
                return $radio->isVisible();
            });

            if (!$radioButtons) {
                throw new ExpectationException('No Visible Radio Button was found on the page', $this->getSession());
            }

            usort($radioButtons, [$this, 'compareElementsByCoords']);

            return $radioButtons[0];
        });

        return $radioButton;
    }

    /**
     * Compares two Elements and determines which is "first".
     *
     * This is for use with usort (and similar) functions, for sorting a list of
     * NodeElements by their coordinates. The typical use case is to determine
     * the order of elements on a page as a viewer would perceive them.
     *
     * @param  NodeElement                      $a one of the two NodeElements to compare.
     * @param  NodeElement                      $b the other NodeElement to compare.
     * @throws UnsupportedDriverActionException If the current driver does not support getXpathBoundingClientRect.
     * @return int
     */
    protected function compareElementsByCoords(NodeElement $a, NodeElement $b)
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        if (!($driver instanceof Selenium2Driver) || !method_exists($driver, 'getXpathBoundingClientRect')) {
            // If not supported by driver, just return true so the keep the original sort.
            return -1;
        }

        /* @noinspection PhpUndefinedMethodInspection */
        $aRect = $driver->getXpathBoundingClientRect($a->getXpath());
        /* @noinspection PhpUndefinedMethodInspection */
        $bRect = $driver->getXpathBoundingClientRect($b->getXpath());

        if ($aRect['top'] == $bRect['top']) {
            return 0;
        }

        return ($aRect['top'] < $bRect['top']) ? -1 : 1;
    }

    /**
     * Waits for the page to be loaded.
     *
     * This does not wait for any particular javascript frameworks to be ready, it only waits for the DOM to be
     * ready. This is done by waiting for the document.readyState to be "complete".
     */
    public function waitForPageLoad($timeout = 120)
    {
        $this->waitFor(function () {
            $readyState = $this->getSession()->evaluateScript('document.readyState');
            if ($readyState !== 'complete') {
                throw new ExpectationException("Page is not loaded. Ready state is '$readyState'", $this->getSession());
            }
        }, $timeout);
    }

    /**
     * Returns true is $element is fully visible in the viewport, otherwise false.
     *
     * @param  NodeElement                      $element the NodeElement to look for in the viewport.
     * @throws UnsupportedDriverActionException if driver does not support the requested action.
     * @return bool                             True is $element is fully visible in the viewport, otherwise false.
     */
    public function nodeIsFullyVisibleInViewport(NodeElement $element)
    {
        if (!$element->isVisible() ||
            count(($parents = $this->getListOfAllNodeElementParents($element, 'html'))) < 1
        ) {
            return false;
        }
        $elementViewportRectangle = $this->getElementViewportRectangle($element);
        foreach ($parents as $parent) {
            if (!$parent->isVisible() ||
                !$elementViewportRectangle->isFullyIn($this->getElementViewportRectangle($parent))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Asserts that a NodeElement is visible in viewport.
     *
     * @param  NodeElement                      $element The NodeElement to check for in the viewport
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException if driver does not support the requested action.
     * @return bool
     */
    public function nodeIsVisibleInViewport(NodeElement $element)
    {
        $driver = $this->getSession()->getDriver();

        if (
            !$driver->isDisplayed($element->getXpath()) ||
            count($parents = $this->getListOfAllNodeElementParents($element, 'html')) < 1
        ) {
            return false;
        }

        $elementViewportRectangle = $this->getElementViewportRectangle($element);

        foreach ($parents as $parent) {
            if (
                !$driver->isDisplayed($parent->getXpath()) ||
                !$elementViewportRectangle->isIn($this->getElementViewportRectangle($parent))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Asserts that a NodeElement is visible in document.
     *
     * @param  NodeElement $element The NodeElement to check for in the document
     * @return bool
     */
    public function nodeIsVisibleInDocument(NodeElement $element)
    {
        $driver = $this->getSession()->getDriver();

        if (!$driver->isDisplayed($element->getXpath())) {
            return false;
        }

        return true;
    }

    /**
     * Get a rectangle that represents the location of a NodeElements viewport.
     *
     * @param  NodeElement                      $element NodeElement to get the viewport of.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return Rectangle                        representing the viewport
     */
    public function getElementViewportRectangle(NodeElement $element)
    {
        $driver = $this->assertSelenium2Driver('Get XPath Element Dimensions');
        $dimensions = $driver->getXpathElementDimensions($element->getXpath());
        $YScrollBarWidth = $dimensions['clientWidth'] > 0 ? $dimensions['width'] - $dimensions['clientWidth'] : 0;
        $XScrollBarHeight = $dimensions['clientHeight'] > 0 ? $dimensions['height'] - $dimensions['clientHeight'] : 0;

        return new Rectangle(
            $dimensions['left'],
            $dimensions['top'],
            $dimensions['right'] - $YScrollBarWidth,
            $dimensions['bottom'] - $XScrollBarHeight
        );
    }

    /**
     * Get list of of all NodeElement parents.
     *
     * @param  NodeElement   $nodeElement
     * @param  string        $stopAt      html tag to stop at
     * @return NodeElement[]
     */
    private function getListOfAllNodeElementParents(NodeElement $nodeElement, $stopAt)
    {
        $nodeElements = [];
        while ($nodeElement->getParent() instanceof NodeElement) {
            $nodeElements[] = ($nodeElement = $nodeElement->getParent());
            if (strtolower($nodeElement->getTagName()) === strtolower($stopAt)) {
                break;
            }
        }

        return $nodeElements;
    }

    /**
     * Step to assert that the specified element is not covered.
     *
     * @param  string               $identifier Element Id to find the element used in the assertion.
     * @throws ExpectationException If element is found to be covered by another.
     *
     * @Then the :identifier element should not be covered by another
     */
    public function assertElementIsNotCoveredByIdStep($identifier)
    {
        /** @var NodeElement $element */
        $element = $this->getSession()->getPage()->find('css', "#$identifier");

        $this->assertElementIsNotCovered($element);
    }

    /**
     * Asserts that the specified element is not covered by another element.
     *
     * Keep in mind that at the moment, this method performs a check in a square area so this may not work
     * correctly with elements of different shapes.
     *
     * @param  NodeElement              $element  The element to assert that is not covered by something else.
     * @param  int                      $leniency Percent of leniency when performing each pixel check.
     * @throws ExpectationException     If element is found to be covered by another.
     * @throws InvalidArgumentException The threshold provided is outside of the 0-100 range accepted.
     */
    public function assertElementIsNotCovered(NodeElement $element, $leniency = 20)
    {
        if ($leniency < 0 || $leniency > 99) {
            throw new InvalidArgumentException('The leniency provided is outside of the 0-50 range accepted.');
        }

        $xpath = $element->getXpath();

        /** @var array $coordinates */
        $coordinates = $this->getSession()->evaluateScript(<<<JS
          return document.evaluate("$xpath", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)
            .singleNodeValue.getBoundingClientRect();
JS
        );

        $width = $coordinates['width'] - 1;
        $height = $coordinates['height'] - 1;
        $right = $coordinates['right'];
        $bottom = $coordinates['bottom'];

        // X and Y are the starting points.
        $x = $coordinates['left'];
        $y = $coordinates['top'];

        $xSpacing = ($width * ($leniency / 100)) ?: 1;
        $ySpacing = ($height * ($leniency / 100)) ?: 1;

        $expected = $element->getOuterHtml();

        /**
         * Asserts that each point checked on the row isn't covered by an element that doesn't match the expected.
         *
         * @param  int                  $x      Starting X position.
         * @param  int                  $y      Starting Y position.
         * @param  int                  $xLimit Width of element.
         * @throws ExpectationException If element is found to be covered by another in the row specified.
         */
        $assertRow = function ($x, $y, $xLimit) use ($expected, $xSpacing) {
            while ($x < $xLimit) {
                $found = $this->getSession()->evaluateScript("return document.elementFromPoint($x, $y).outerHTML;");
                if (strpos($expected, $found) === false) {
                    throw new ExpectationException(
                        'An element is above an interacting element.',
                        $this->getSession()
                    );
                }

                $x += $xSpacing;
            }
        };

        // Go through each row in the square area found.
        while ($y < $bottom) {
            $assertRow($x, $y, $right);
            $y += $ySpacing;
        }
    }

    /**
     * Asserts that the current driver is Selenium 2 in preparation for performing an action that requires it.
     *
     * @param  string                           $operation the operation that you will attempt to perform that requires
     *                                                     the Selenium 2 driver.
     * @throws UnsupportedDriverActionException if the current driver is not Selenium 2.
     * @return Selenium2Driver
     */
    public function assertSelenium2Driver($operation)
    {
        $driver = $this->getSession()->getDriver();
        if (!($driver instanceof Selenium2Driver)) {
            throw new UnsupportedDriverActionException($operation . ' is not supported by %s', $driver);
        }

        return $driver;
    }
}
