<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\Models\Geometry\Rectangle;
use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use InvalidArgumentException;
use JMS\Serializer\Tests\Fixtures\Node;
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
     */
    public function assertCheckboxChecked($checkbox)
    {
        $checkbox = $this->injectStoredValues($checkbox);
        parent::assertCheckboxChecked($checkbox);
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

        return $aRect['top'] - $bRect['top'];
    }

    /**
     * Asserts that a qaId is fully visible.
     *
     * @Then /^"(?P<qaId>[^"]+)" should(?P<not> not|) be fully visible$/
     *
     * @param  string                           $qaId The qaId of the dom element to find
     * @param  bool                             $not  Asserts qaId is partially or not visible in the viewport.
     * @throws ExpectationException
     * @throws UnsupportedDriverActionException
     */
    public function assertQaIDIsFullyVisible($qaId, $not = false)
    {
        $qaId = $this->injectStoredValues($qaId);

        $driver = $this->getSession()->getDriver();

        $nodeElement = $this->waitFor(function () use ($qaId) {
            return $this->getSession()->getPage()->find('xpath', '//*[@data-qa-id="' . $qaId . '"]');
        });

        if (!$nodeElement instanceof NodeElement && !$not) {
            throw new ExpectationException(
                "Couldn't find node element by qaId in " . __FUNCTION__,
                $driver
            );
        } elseif (!$nodeElement instanceof NodeElement && $not) {
            return;
        }

        try {
            $this->assertNodeIsFullyVisible($nodeElement, $not);
        } catch (ExpectationException $ExpectationException) {
            throw new ExpectationException(
                str_replace(['Node', 'node'], $qaId, $ExpectationException->getMessage()),
                $driver
            );
        }
    }

    /**
     * Asserts that a NodeElement is fully visible.
     *
     * @param  NodeElement                      $element
     * @param  bool                             $not     Asserts NodeElement is partially or not visible in the viewport.
     * @throws ExpectationException
     * @throws UnsupportedDriverActionException
     */
    public function assertNodeIsFullyVisible(NodeElement $element, $not = false)
    {
        $driver = $this->getSession()->getDriver();

        if (!$element instanceof NodeElement) {
            throw new ExpectationException('Invalid node sent to ' . __FUNCTION__, $driver);
        }

        if (!$driver instanceof Selenium2Driver) {
            throw new UnsupportedDriverActionException('%s does not support assertNodeIsFullyVisibleInViewPort', $driver);
        }

        if (!$element->isVisible()) {
            if (!$not) {
                throw new ExpectationException(
                    'The element is not visible', $driver
                );
            }

            return;
        }

        $allAreIn = true;

        $parents = $this->getListOfAllNodeElementParents($element, 'html', true);

        if (count($parents) < 1) {
            throw new ExpectationException('Invalid number of node elements', $driver);
        }

        $elementViewportRectangle = $this->getElementViewportRectangle($element);

        foreach ($parents as $parent) {
            if (!$parent->isVisible()) {
                if (!$not) {
                    throw new ExpectationException(
                        'One of the node elements parents is not visible', $driver
                    );
                }

                return;
            }

            $isIn = $elementViewportRectangle->isFullyIn($this->getElementViewportRectangle($parent), $not);

            $allAreIn = $allAreIn && !$isIn;

            if (!$not && !$isIn) {
                throw new ExpectationException(
                    'Node is not fully visible in the viewport.', $driver
                );
            }
        }

        if ($not && $allAreIn) {
            throw new ExpectationException(
                'Node is fully visible in the viewport.', $driver
            );
        }
    }

    /**
     * Get a rectangle that represents the location of a NodeElements viewport.
     *
     * @param  NodeElement $element NodeElement to get the viewport of.
     * @return Rectangle   representing the viewport
     */
    public function getElementViewportRectangle(NodeElement $element)
    {
        $dimensions = $this->getSession()->getDriver()->getXpathElementDimensions($element->getXpath());

        $YScrollBarWidth = 0;
        $XScrollBarHeight = 0;

        if ($dimensions['clientWidth'] > 0) {
            $YScrollBarWidth = $dimensions['width'] - $dimensions['clientWidth'];
        }

        if ($dimensions['clientHeight'] > 0) {
            $XScrollBarHeight = $dimensions['height'] - $dimensions['clientHeight'];
        }

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
     * @param  NodeElement $NodeElement
     * @param  string      $stopAt       html tag to stop at
     * @param  bool        $reverseOrder list parents in reverse order (root element will be at index 0)
     * @return array       of nodeElements
     */
    private function getListOfAllNodeElementParents(NodeElement $NodeElement, $stopAt, $reverseOrder)
    {
        $NodeElements = [];

        while ($NodeElement->getParent() instanceof NodeElement) {
            $NodeElements[] = ($NodeElement = $NodeElement->getParent());

            if (strtolower($NodeElement->getTagName()) === strtolower($stopAt)) {
                break;
            }
        }

        if ($reverseOrder) {
            $NodeElements = array_reverse($NodeElements);
        }

        return $NodeElements;
    }
}
