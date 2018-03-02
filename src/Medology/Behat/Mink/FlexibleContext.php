<?php

namespace Medology\Behat\Mink;

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
use InvalidArgumentException;
use Medology\Behat\StoreContext;
use Medology\Behat\TypeCaster;
use Medology\Behat\UsesStoreContext;
use ZipArchive;

/**
 * Overwrites some MinkContext step definitions to make them more resilient to failures caused by browser/driver
 * discrepancies and unpredictable load times.
 */
class FlexibleContext extends MinkContext
{
    use TypeCaster;
    use UsesStoreContext;

    /** @var array map of common key names to key codes */
    protected static $keyCodes = [
        'down arrow' => 40,
        'enter'      => 13,
        'return'     => 13,
        'shift tab'  => 2228233,
        'tab'        => 9,
    ];

    /**
     * This method overrides the MinkContext::assertPageContainsText() default behavior for assertPageContainsText to
     * inject stored values into the provided text.
     *
     * @see StoreContext::injectStoredValues()
     * @param string $text Text to be searched in the page.
     */
    public function assertPageContainsText($text)
    {
        parent::assertPageContainsText($this->storeContext->injectStoredValues($text));
    }

    /**
     * Asserts that the page contains a list of strings.
     *
     * @Then   /^I should (?:|(?P<not>not ))see the following:$/
     * @param  TableNode             $table The list of strings to find.
     * @param  string                $not   A flag to assert not containing text.
     * @throws ResponseTextException If the text is not found.
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
     * This method overrides the MinkContext::assertPageNotContainsText() default behavior for assertPageNotContainsText
     * to inject stored values into the provided text.
     *
     * @see StoreContext::injectStoredValues()
     * @param string $text The text that should not be found on the page.
     */
    public function assertPageNotContainsText($text)
    {
        parent::assertPageNotContainsText($this->storeContext->injectStoredValues($text));
    }

    /**
     * This method overrides the MinkContext::assertElementContainsText() default behavior for
     * assertElementContainsText to inject stored values into the provided element and text.
     *
     * @see StoreContext::injectStoredValues()
     * @param string|array $element css element selector
     * @param string       $text    expected text
     */
    public function assertElementContainsText($element, $text)
    {
        parent::assertElementContainsText(
            $this->storeContext->injectStoredValues($element),
            $this->storeContext->injectStoredValues($text)
        );
    }

    /**
     * Checks, that element with specified CSS doesn't contain specified text.
     *
     * @see MinkContext::assertElementNotContainsText
     * @param string|array $element css element selector.
     * @param string       $text    expected text that should not being found.
     */
    public function assertElementNotContainsText($element, $text)
    {
        parent::assertElementNotContainsText(
            $this->storeContext->injectStoredValues($element),
            $this->storeContext->injectStoredValues($text)
        );
    }

    /**
     * Clicks a visible link with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::clickLink() default behavior for clickLink to ensure that only visible
     * links are clicked.
     * @see MinkContext::clickLink
     * @param string $locator The id|title|alt|text of the link to be clicked.
     */
    public function clickLink($locator)
    {
        $locator = $this->storeContext->injectStoredValues($locator);
        $this->assertVisibleLink($locator)->click();
    }

    /**
     * Clicks a visible checkbox with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::checkOption() default behavior for checkOption to ensure that only visible
     * options are checked and inject stored values into the provided locator.
     *
     * @see StoreContext::injectStoredValues()
     * @see MinkContext::checkOption
     * @param string $locator The id|title|alt|text of the option to be clicked.
     */
    public function checkOption($locator)
    {
        $locator = $this->storeContext->injectStoredValues($locator);
        $this->assertVisibleOption($locator)->check();
    }

    /**
     * Clicks a visible field with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::fillField() default behavior for fill a field to ensure that only visible
     * field is filled.
     *
     * @see MinkContext::fillField
     * @param string $field The id|title|alt|text of the field to be filled.
     * @param string $value The value to be set on the field.
     */
    public function fillField($field, $value)
    {
        $field = $this->storeContext->injectStoredValues($field);
        $this->assertFieldExists($field)->setValue($value);
    }

    /**
     * Un-checks checkbox with specified id|name|label|value.
     *
     * @see MinkContext::uncheckOption
     * @param string $locator The id|title|alt|text of the option to be unchecked.
     */
    public function uncheckOption($locator)
    {
        $locator = $this->storeContext->injectStoredValues($locator);
        $this->assertVisibleOption($locator)->uncheck();
    }

    /**
     * Checks if the selected button is disabled.
     *
     * @Given  the :locator button is :disabled
     * @Then   the :locator button should be :disabled
     * @param  string               $locator  The button
     * @param  bool                 $disabled The state of the button
     * @throws ExpectationException If button is disabled but shouldn't be.
     * @throws ExpectationException If button isn't disabled but should be.
     * @throws ExpectationException If the button can't be found.
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
     * Finds the first matching visible button on the page.
     *
     * Warning: Will return the first button if the driver does not support visibility checks.
     *
     * @param  string               $locator The button name.
     * @throws ExpectationException If a visible button was not found.
     * @return NodeElement          The button.
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
     * Finds the first matching visible link on the page.
     *
     * Warning: Will return the first link if the driver does not support visibility checks.
     *
     * @Given  the :locator link is visible
     * @Then   the :locator link should be visible
     * @param  string               $locator The link name.
     * @throws ExpectationException If a visible link was not found.
     * @return NodeElement          The link.
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
     * Finds the first matching visible option on the page.
     *
     * Warning: Will return the first option if the driver does not support visibility checks.
     *
     * @param  string               $locator The option name.
     * @throws ExpectationException If a visible option was not found.
     * @return NodeElement          The option.
     */
    public function assertVisibleOption($locator)
    {
        $locator = $this->fixStepArgument($locator);

        $options = $this->getSession()->getPage()->findAll('named', ['field', $locator]);

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
     * Checks that the page contains a visible input field and then returns it.
     *
     * @param  string                  $fieldName The input name.
     * @param  TraversableElement|null $context   The context to search in, if not provided defaults to page.
     * @throws ExpectationException    If a visible input field is not found.
     * @return NodeElement             The found input field.
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
     * Gets all the inputs that have the label name specified within the context specified.
     *
     * @param string             $labelName The label text used to find the inputs for.
     * @param TraversableElement $context   The context to search in.
     *
     * @return NodeElement[]
     */
    public function getInputsByLabel($labelName, TraversableElement $context)
    {
        /** @var NodeElement[] $labels */
        $labels = $context->findAll('xpath', "//label[contains(text(), '$labelName')]");
        $found = [];

        foreach ($labels as $label) {
            $inputName = $label->getAttribute('for');

            foreach ($context->findAll('named', ['field', $inputName]) as $element) {
                $found[$inputName] = $element;
            }
        }

        return array_values($found);
    }

    /**
     * Checks that the page not contain a visible input field.
     *
     * @param  string               $fieldName The name of the input field.
     * @throws ExpectationException If a visible input field is found.
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
     * Checks that the page contains the given lines of text in the order specified.
     *
     * @Then I should see the following lines in order:
     * @param  TableNode                $table A list of text lines to look for.
     * @throws ExpectationException     if a line is not found, or is found out of order.
     * @throws InvalidArgumentException if the list of lines has more than one column.
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
            $line = $this->storeContext->injectStoredValues($line);

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
     * This method will check if all the fields exists and visible in the current page.
     *
     * @Then /^I should see the following fields:$/
     * @param  TableNode            $tableNode The id|name|title|alt|value of the input field
     * @throws ExpectationException if any of the fields is not visible in the page
     */
    public function assertPageContainsFields(TableNode $tableNode)
    {
        foreach ($tableNode->getRowsHash() as $field => $value) {
            $this->assertFieldExists($field);
        }
    }

    /**
     * This method will check if all the fields not exists or not visible in the current page.
     *
     * @Then /^I should not see the following fields:$/
     * @param  TableNode            $tableNode The id|name|title|alt|value of the input field
     * @throws ExpectationException if any of the fields is visible in the page
     */
    public function assertPageNotContainsFields(TableNode $tableNode)
    {
        foreach ($tableNode->getRowsHash() as $field => $value) {
            $this->assertFieldNotExists($field);
        }
    }

    /**
     * Assert if the option exist/not exist in the select.
     *
     * @Then   /^the (?P<option>.*?) option(?:|(?P<existence> does not?)) exists? in the (?P<select>.*?) select$/
     * @param  string                   $select    The name of the select
     * @param  string                   $existence The status of the option item
     * @param  string                   $option    The name of the option item
     * @throws ElementNotFoundException If the select is not found in the page
     * @throws ExpectationException     If the option is exist/not exist as expected
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
     * Assert if the options in the select match given options.
     *
     * @Then   /^the "(?P<select>[^"]*)" select should only have the following option(?:|s):$/
     * @param  string                   $select    The name of the select
     * @param  TableNode                $tableNode The text of the options.
     * @throws ExpectationException     When there is no option in the select.
     * @throws ExpectationException     When the option(s) in the select not match the option(s) listed.
     * @throws InvalidArgumentException When no expected options listed in the test step.
     */
    public function assertSelectContainsExactOptions($select, TableNode $tableNode)
    {
        if (count($tableNode->getRow(0)) > 1) {
            throw new InvalidArgumentException('Arguments must be a single-column list of items');
        }

        $expectedOptTexts = array_map([$this->storeContext, 'injectStoredValues'], $tableNode->getColumn(0));

        $select = $this->fixStepArgument($select);
        $select = $this->storeContext->injectStoredValues($select);
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
    }

    /**
     * Sets a cookie.
     *
     * Note: you must request a page before trying to set a cookie, in order to set the domain.
     *
     * @When /^(?:|I )set the cookie "(?P<key>(?:[^"]|\\")*)" with value (?P<value>.+)$/
     * @param string $key   the name of the key to set
     * @param string $value the value to set the cookie to
     */
    public function setCookie($key, $value)
    {
        $this->getSession()->setCookie($key, $value);
    }

    /**
     * Deletes a cookie.
     *
     * @When  /^(?:|I )delete the cookie "(?P<key>(?:[^"]|\\")*)"$/
     * @param string $key the name of the key to delete.
     */
    public function deleteCookie($key)
    {
        $this->getSession()->setCookie($key, null);
    }

    /**
     * Attaches a local file to field with specified id|name|label|value. This is used when running behat and
     * browser session in different containers.
     *
     * @When   /^(?:|I )attach the local file "(?P<path>[^"]*)" to "(?P<field>(?:[^"]|\\")*)"$/
     * @param  string                           $field The file field to select the file with
     * @param  string                           $path  The local path of the file
     * @throws UnsupportedDriverActionException if getWebDriverSession() is not supported by the current driver.
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

        $driver = $this->getSession()->getDriver();
        if (!($driver instanceof Selenium2Driver)) {
            throw new UnsupportedDriverActionException('getWebDriverSession() is not supported by %s', $driver);
        }

        /** @noinspection PhpUndefinedMethodInspection file() method annotation is missing from WebDriver\Session */
        $remotePath = $driver->getWebDriverSession()->file([
            'file' => base64_encode(file_get_contents($tempZip)),
        ]);

        $this->attachFileToField($field, $remotePath);

        unlink($tempZip);
    }

    /**
     * Blurs (unfocuses) selected field.
     *
     * @When /^(?:I |)(?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @param string $locator The field to blur
     */
    public function blurField($locator)
    {
        $this->assertFieldExists($locator)->blur();
    }

    /**
     * Focuses and blurs (unfocuses) the selected field.
     *
     * @When /^(?:I |)focus and (?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @When /^(?:I |)toggle focus (?:on|of) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @param string $locator The field to focus and blur
     */
    public function focusBlurField($locator)
    {
        $this->focusField($locator);
        $this->blurField($locator);
    }

    /**
     * Focuses the selected field.
     *
     * @When /^(?:I |)focus (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @param string $locator The the field to focus
     */
    public function focusField($locator)
    {
        $this->assertFieldExists($locator)->focus();
    }

    /**
     * Simulates hitting a keyboard key.
     *
     * @When   /^(?:I |)(?:hit|press) (?:the |)"(?P<key>[^"]+)" key$/
     * @param  string                   $key The key on the keyboard
     * @throws InvalidArgumentException if $key is not recognized as a valid key
     */
    public function hitKey($key)
    {
        if (!array_key_exists($key, self::$keyCodes)) {
            throw new InvalidArgumentException("The key '$key' is not defined.");
        }

        $script = "jQuery.event.trigger({ type : 'keypress', which : '" . self::$keyCodes[$key] . "' });";
        $this->getSession()->evaluateScript($script);
    }

    /**
     * Presses the visible button with specified id|name|title|alt|value.
     *
     * This method overrides the MinkContext::pressButton() default behavior for pressButton to ensure that only visible
     * buttons are pressed.
     *
     * @see MinkContext::pressButton
     * @param  string               $locator button id, inner text, value or alt
     * @throws ExpectationException If a visible button field is not found.
     */
    public function pressButton($locator)
    {
        $this->assertVisibleButton($locator)->press();
    }

    /**
     * Scrolls the window to the top or bottom of the page body.
     *
     * @Given /^the page is scrolled to the (?P<where>top|bottom)$/
     * @When /^(?:I |)scroll to the (?P<where>top|bottom) of the page$/
     * @param  string                           $where to scroll to. Must be either "top" or "bottom".
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     * @throws DriverException                  When the operation cannot be done
     */
    public function scrollWindowToBody($where)
    {
        $x = ($where == 'top') ? '0' : 'document.body.scrollHeight';

        $this->getSession()->executeScript("window.scrollTo(0, $x)");
    }

    /**
     * This overrides MinkContext::visit() to inject stored values into the URL.
     *
     * @see MinkContext::visit
     * @param string $page the page to visit
     */
    public function visit($page)
    {
        parent::visit($this->storeContext->injectStoredValues($page));
    }

    /**
     * This overrides MinkContext::assertCheckboxChecked() to inject stored values into the locator.
     *
     * @param string $checkbox The the locator of the checkbox
     */
    public function assertCheckboxChecked($checkbox)
    {
        $checkbox = $this->storeContext->injectStoredValues($checkbox);
        parent::assertCheckboxChecked($checkbox);
    }

    /**
     * This overrides MinkContext::assertCheckboxNotChecked() to inject stored values into the locator.
     *
     * @param string $checkbox The the locator of the checkbox
     */
    public function assertCheckboxNotChecked($checkbox)
    {
        $checkbox = $this->storeContext->injectStoredValues($checkbox);
        parent::assertCheckboxNotChecked($checkbox);
    }

    /**
     * Check the radio button.
     *
     * @When  I check radio button :label
     * @param string $label The label of the radio button.
     */
    public function ensureRadioButtonChecked($label)
    {
        $this->findRadioButton($label)->click();
    }

    /**
     * Assert the radio button is checked.
     *
     * @Then   /^the "(?P<label>(?:[^"]|\\")*)" radio button should be checked$/
     * @param  string               $label The label of the radio button.
     * @throws ExpectationException When the radio button is not checked.
     */
    public function assertRadioButtonChecked($label)
    {
        if (!$this->findRadioButton($label)->isChecked()) {
            throw new ExpectationException("Radio button \"$label\" is not checked, but it should be.", $this->getSession());
        }
    }

    /**
     * Assert the radio button is not checked.
     *
     * @Then   /^the "(?P<label>(?:[^"]|\\")*)" radio button should not be checked$/
     * @param  string               $label The label of the radio button.
     * @throws ExpectationException When the radio button is checked.
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
     * @param  string               $label The Label of the radio button.
     * @throws ExpectationException if the radio button was not found on the page.
     * @throws ExpectationException if the radio button was on the page, but was not visible.
     * @return NodeElement
     */
    protected function findRadioButton($label)
    {
        $label = $this->storeContext->injectStoredValues($label);
        $this->fixStepArgument($label);

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
     * Provides the directory that test artifacts should be stored to.
     *
     * This should be overridden when FlexibleMink is used in a project.
     *
     * @return string the fully qualified directory, with no trailing directory separator.
     */
    public function getArtifactsDir()
    {
        return realpath(__DIR__ . '/../../../../artifacts');
    }
}
