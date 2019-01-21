<?php

namespace Medology\Behat\Mink;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ElementTextException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\MinkExtension\Context\MinkContext;
use InvalidArgumentException;
use Medology\Behat\Mink\Models\Geometry\Rectangle;
use Medology\Behat\StoreContext;
use Medology\Behat\TypeCaster;
use Medology\Behat\UsesStoreContext;
use Medology\Spinner;
use Medology\SpinnerTimeoutException;
use OutOfBoundsException;
use ReflectionException;
use WebDriver\Exception;
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
     * {@inheritdoc}
     *
     * Overrides the base method to support injecting stored values and matching URLs that include hostname.
     *
     * @throws DriverException          If the driver failed to perform the action.
     * @throws ExpectationException     If the current page is not the expected page.
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     * @throws OutOfBoundsException     If a stored item was referenced in the text and the specified stored item does
     *                                  not have the specified property or key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     */
    public function assertPageAddress($page)
    {
        $page = $this->storeContext->injectStoredValues($page);

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
    }

    /**
     * This method overrides the MinkContext::assertPageContainsText() default behavior for assertPageContainsText to
     * inject stored values into the provided text.
     *
     * @see StoreContext::injectStoredValues()
     * @param  string                   $text Text to be searched in the page.
     * @throws InvalidArgumentException If the string references something that does not exist in the store.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                       and they do not conform to its requirements. This method does not pass
     *                                       closures, so if this happens, there is a problem with the
     *                                       injectStoredValues method.
     * @throws OutOfBoundsException     If a stored item was referenced in the text and the specified stored item does
     *                                       not have the specified property or key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                       This should never happen. If it does, there is a problem with the
     *                                       injectStoredValues method.
     * @throws ResponseTextException    If the text is not found.
     */
    public function assertPageContainsText($text)
    {
        parent::assertPageContainsText($this->storeContext->injectStoredValues($text));
    }

    /**
     * Asserts that the page contains a list of strings.
     *
     * @Then   /^I should (?:|(?P<not>not ))see the following:$/
     * @param  TableNode                $table The list of strings to find.
     * @param  string                   $not   A flag to assert not containing text.
     * @throws InvalidArgumentException If the string references something that does not exist in the store.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                        and they do not conform to its requirements. This method does not pass
     *                                        closures, so if this happens, there is a problem with the
     *                                        injectStoredValues method.
     * @throws OutOfBoundsException     If the specified stored item does not have the specified property or
     *                                        key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                        This should never happen. If it does, there is a problem with the
     *                                        injectStoredValues method.
     * @throws ResponseTextException    If the text is not found.
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
     * @see    StoreContext::injectStoredValues()
     * @param  string                   $text The text that should not be found on the page.
     * @throws InvalidArgumentException If the string references something that does not exist in the store.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                       and they do not conform to its requirements. This method does not pass
     *                                       closures, so if this happens, there is a problem with the
     *                                       injectStoredValues method.
     * @throws OutOfBoundsException     If the specified stored item does not have the specified property or
     *                                       key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                       This should never happen. If it does, there is a problem with the
     *                                       injectStoredValues method.
     * @throws ResponseTextException    if the page does not contain the text
     */
    public function assertPageNotContainsText($text)
    {
        parent::assertPageNotContainsText($this->storeContext->injectStoredValues($text));
    }

    /**
     * {@inheritdoc}
     *
     * Overrides the parent method to support injecting values from the store into the field and value.
     *
     * @throws InvalidArgumentException If the string references something that does not exist in the store.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     If the specified stored item does not have the specified property or
     *                                  key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     */
    public function assertFieldContains($field, $value)
    {
        $field = $this->storeContext->injectStoredValues($field);
        $value = $this->storeContext->injectStoredValues($value);

        parent::assertFieldContains($field, $value);
    }

    /**
     * Asserts that a field is visible or not.
     * @Then   /^the field "(?P<field>[^"]+)" should(?P<not> not|) be visible$/
     *
     * @param  string                           $field The field to be checked
     * @param  bool                             $not   check if field should be visible or not.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if there is more than one matching field found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * This method overrides the MinkContext::assertElementContainsText() default behavior for
     * assertElementContainsText to inject stored values into the provided element and text.
     *
     * @see    StoreContext::injectStoredValues()
     * @param  string|array             $element css element selector
     * @param  string                   $text    expected text
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                          and they do not conform to its requirements. This method does not pass
     *                                          closures, so if this happens, there is a problem with the
     *                                          injectStoredValues method.
     * @throws OutOfBoundsException     If the specified stored item does not have the specified property or
     *                                          key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                          This should never happen. If it does, there is a problem with the
     *                                          injectStoredValues method.
     * @throws ElementTextException     If the element does not contain the text.
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
     * @see    MinkContext::assertElementNotContainsText
     * @param  string|array             $element css element selector.
     * @param  string                   $text    expected text that should not being found.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                          and they do not conform to its requirements. This method does not pass
     *                                          closures, so if this happens, there is a problem with the
     *                                          injectStoredValues method.
     * @throws OutOfBoundsException     If the specified stored item does not have the specified property or
     *                                          key.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                          This should never happen. If it does, there is a problem with the
     *                                          injectStoredValues method.
     * @throws ElementTextException     If the element contains the text.
     */
    public function assertElementNotContainsText($element, $text)
    {
        parent::assertElementNotContainsText(
            $this->storeContext->injectStoredValues($element),
            $this->storeContext->injectStoredValues($text)
        );
    }

    /**
     * {@inheritdoc}
     *
     * Overrides the base method store the resulting element in the store under "element" and return it.
     *
     * @param  string                   $element      The selector to find the element.
     * @param  string                   $selectorType css|xpath selector type to find the element.
     * @throws ElementNotFoundException if the element was not found.
     * @return NodeElement              The element found.
     */
    public function assertElementOnPage($element, $selectorType = 'css')
    {
        $node = $this->assertSession()->elementExists($selectorType, $element);

        $this->storeContext->set('element', $node);

        return $node;
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
        if (!$element = $container->find('xpath', $xpath)) {
            throw new ExpectationException('Nothing found inside element with xpath $xpath', $this->getSession());
        }

        return $element;
    }

    /**
     * Clicks a visible link with specified id|title|alt|text.
     * This method overrides the MinkContext::clickLink() default behavior for clickLink to ensure that only visible
     * links are clicked.
     *
     * @see MinkContext::clickLink
     * @param  string                           $locator The id|title|alt|text of the link to be clicked.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If the specified link is not visible.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                  passed, and they do not conform to its requirements. This method
     *                                                  does not pass closures, so if this happens, there is a problem
     *                                                  with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                  passed. This should never happen. If it does, there is a problem
     *                                                  with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @param  string                           $locator The id|title|alt|text of the option to be clicked.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If the specified option is not visible.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                  passed, and they do not conform to its requirements. This method
     *                                                  does not pass closures, so if this happens, there is a problem
     *                                                  with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                  passed. This should never happen. If it does, there is a problem
     *                                                  with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @param  string                           $field The id|title|alt|text of the field to be filled.
     * @param  string                           $value The value to be set on the field.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If the specified field does not exist.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                passed, and they do not conform to its requirements. This method
     *                                                does not pass closures, so if this happens, there is a problem
     *                                                with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                passed. This should never happen. If it does, there is a problem
     *                                                with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function fillField($field, $value)
    {
        $field = $this->storeContext->injectStoredValues($field);
        $value = $this->storeContext->injectStoredValues($value);
        $this->assertFieldExists($field)->setValue($value);
    }

    /**
     * Un-checks checkbox with specified id|name|label|value.
     *
     * @see MinkContext::uncheckOption
     * @param  string                           $locator The id|title|alt|text of the option to be unchecked.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If the specified option is not visible.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                  passed, and they do not conform to its requirements. This method
     *                                                  does not pass closures, so if this happens, there is a problem
     *                                                  with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                  passed. This should never happen. If it does, there is a problem
     *                                                  with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @param  string                           $locator  The button
     * @param  bool                             $disabled The state of the button
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If button is disabled but shouldn't be.
     * @throws ExpectationException             If button isn't disabled but should be.
     * @throws ExpectationException             If the button can't be found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * Asserts that the specified button exists in the DOM.
     *
     * @Then   I should see a :locator button
     * @param  string                           $locator The id|name|title|alt|value of the button.
     * @throws DriverException                  When the operation cannot be done.
     * @throws ExpectationException             If no button was found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return NodeElement                      The button.
     */
    public function assertButtonExists($locator)
    {
        $locator = $this->fixStepArgument($locator);

        if (!$button = $this->getSession()->getPage()->find('named', ['button', $locator])) {
            throw new ExpectationException("No button found for '$locator'", $this->getSession());
        }

        return $button;
    }

    /**
     * Finds the first matching visible button on the page.
     *
     * Warning: Will return the first button if the driver does not support visibility checks.
     *
     * @param  string                           $locator The button name.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If a visible button was not found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return NodeElement                      The button.
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
     * @param  string                           $locator The link name.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If a visible link was not found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return NodeElement                      The link.
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
     * @param  string                           $locator The option name.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If a visible option was not found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return NodeElement                      The option.
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
     * @param  string                           $fieldName The input name.
     * @param  TraversableElement|null          $context   The context to search in, if not provided defaults to page.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If a visible input field is not found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     * @return NodeElement                      The found input field.
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
     * @param  string                           $labelName The label text used to find the inputs for.
     * @param  TraversableElement               $context   The context to search in.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
                if (!in_array($element, $found)) {
                    array_push($found, $element);
                }
            }
        }

        return $found;
    }

    /**
     * Checks that the page not contain a visible input field.
     *
     * @param  string                           $fieldName The name of the input field.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If a visible input field is found.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @Then   I should see the following lines in order:
     * @param  TableNode                        $table A list of text lines to look for.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if a line is not found, or is found out of order.
     * @throws InvalidArgumentException         if the list of lines has more than one column.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                passed, and they do not conform to its requirements. This method
     *                                                does not pass closures, so if this happens, there is a problem
     *                                                with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                passed. This should never happen. If it does, there is a problem
     *                                                with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @Then   /^I should see the following fields:$/
     * @param  TableNode                        $tableNode The id|name|title|alt|value of the input field
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if any of the fields is not visible in the page.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @Then   /^I should not see the following fields:$/
     * @param  TableNode                        $tableNode The id|name|title|alt|value of the input field
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if any of the fields is visible in the page.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @param  string                           $select    The name of the select
     * @param  string                           $existence The status of the option item
     * @param  string                           $option    The name of the option item
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If the option does/doesn't exist as expected
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function assertSelectContainsOption($select, $existence, $option)
    {
        $select = $this->fixStepArgument($select);
        $option = $this->fixStepArgument($option);
        $selectField = $this->assertFieldExists($select);
        $opt = $selectField->find('named', ['option', $option]);
        if ($existence && $opt) {
            throw new ExpectationException("The option '$option' exist in the select", $this->getSession());
        }
        if (!$existence && !$opt) {
            throw new ExpectationException("The option '$option' does not exist in the select", $this->getSession());
        }
    }

    /**
     * Assert if the options in the select match given options.
     *
     * @Then   /^the "(?P<select>[^"]*)" select should only have the following option(?:|s):$/
     * @param  string                           $select    The name of the select
     * @param  TableNode                        $tableNode The text of the options.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             When there is no option in the select.
     * @throws ExpectationException             When the option(s) in the select not match the option(s) listed.
     * @throws InvalidArgumentException         When no expected options listed in the test step.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                    passed, and they do not conform to its requirements. This
     *                                                    method does not pass closures, so if this happens, there is a
     *                                                    problem with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                    passed. This should never happen. If it does, there is a
     *                                                    problem with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @When   /^(?:|I )set the cookie "(?P<key>(?:[^"]|\\")*)" with value (?P<value>.+)$/
     * @param  string                           $key   the name of the key to set
     * @param  string                           $value the value to set the cookie to
     * @throws DriverException                  When the operation cannot be performed.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function setCookie($key, $value)
    {
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
     * @When   /^(?:|I )delete the cookie "(?P<key>(?:[^"]|\\")*)"$/
     * @param  string                           $key the name of the key to delete.
     * @throws DriverException                  When the operation cannot be performed.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function deleteCookie($key)
    {
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
     * Attaches a local file to field with specified id|name|label|value. This is used when running behat and
     * browser session in different containers.
     *
     * @When   /^(?:|I )attach the local file "(?P<path>[^"]*)" to "(?P<field>(?:[^"]|\\")*)"$/
     * @param  string                           $field The file field to select the file with
     * @param  string                           $path  The local path of the file
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ElementNotFoundException         if the field could not be found.
     * @throws UnsupportedDriverActionException if getWebDriverSession() is not supported by the current driver.
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
     * @throws ExpectationException    if the value of the input does not match expected after the file is
     *                                 attached.
     * @throws SpinnerTimeoutException if the timeout expired before the assertion could be made even once.
     */
    public function attachFileToField($field, $path)
    {
        Spinner::waitFor(function () use ($field, $path) {
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
     * Blurs (unfocuses) selected field.
     *
     * @When   /^(?:I |)(?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @param  string                           $locator The field to blur
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if the specified field does not exist.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function blurField($locator)
    {
        $this->assertFieldExists($locator)->blur();
    }

    /**
     * Focuses and blurs (unfocuses) the selected field.
     *
     * @When   /^(?:I |)focus and (?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @When   /^(?:I |)toggle focus (?:on|of) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @param  string                           $locator The field to focus and blur
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if the specified field does not exist.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function focusBlurField($locator)
    {
        $this->focusField($locator);
        $this->blurField($locator);
    }

    /**
     * Focuses the selected field.
     *
     * @When   /^(?:I |)focus (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @param  string                           $locator The the field to focus
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             if the specified field does not exist.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function focusField($locator)
    {
        $this->assertFieldExists($locator)->focus();
    }

    /**
     * Simulates hitting a keyboard key.
     *
     * @When   /^(?:I |)(?:hit|press) (?:the |)"(?P<key>[^"]+)" key$/
     * @param  string                           $key The key on the keyboard
     * @throws DriverException                  When the operation cannot be performed.
     * @throws InvalidArgumentException         if $key is not recognized as a valid key
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
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
     * @noinspection PhpDocRedundantThrowsInspection exceptions are bubbling up from the waitFor's closure
     *
     * {@inheritdoc}
     *
     * This method overrides the base method to ensure that only visible & enabled buttons are pressed.
     *
     * @param  string                           $locator button id, inner text, value or alt
     * @throws DriverException                  When the operation cannot be performed.
     * @throws ExpectationException             If a visible button field is not found.
     * @throws SpinnerTimeoutException          If the timeout expires and the lambda has thrown a Exception.
     * @throws UnsupportedDriverActionException When operation not supported by the driver.
     */
    public function pressButton($locator)
    {
        /** @var NodeElement $button */
        $button = Spinner::waitFor(function () use ($locator) {
            return $this->assertVisibleButton($locator);
        });

        Spinner::waitFor(function () use ($button, $locator) {
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
     * @throws ExpectationException             If a visible select was not found.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     * @throws SpinnerTimeoutException          If the timeout expires before the assertion can be made even once.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    public function selectOption($select, $option)
    {
        $select = $this->storeContext->injectStoredValues($select);
        $option = $this->storeContext->injectStoredValues($option);

        /** @var NodeElement $field */
        $field = Spinner::waitFor(function () use ($select) {
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
     * Scrolls the window to the top, bottom, left, right (or any valid combination thereof) of the page body.
     *
     * @Given  /^the page is scrolled to the (?P<where>top|bottom)$/
     * @When   /^(?:I |)scroll to the (?P<where>[ a-z]+) of the page$/
     * @param  string                           $where to scroll to. Can be any valid combination of "top", "bottom",
     *                                                 "left" and "right". e.g. "top", "top right", but not "top bottom"
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException When operation not supported by the driver
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
     * This overrides MinkContext::visit() to inject stored values into the URL.
     *
     * @see    MinkContext::visit
     * @param  string                   $page the page to visit
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed, and
     *                                       they do not conform to its requirements. This method does not pass
     *                                       closures, so if this happens, there is a problem with the
     *                                       injectStoredValues method.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                       This should never happen. If it does, there is a problem with the
     *                                       injectStoredValues method.
     */
    public function visit($page)
    {
        parent::visit($this->storeContext->injectStoredValues($page));
    }

    /**
     * This overrides MinkContext::assertCheckboxChecked() to inject stored values into the locator.
     *
     * @param  string                   $checkbox The the locator of the checkbox
     * @throws ExpectationException     If the check box is not checked.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed, and
     *                                           they do not conform to its requirements. This method does not pass
     *                                           closures, so if this happens, there is a problem with the
     *                                           injectStoredValues method.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                           This should never happen. If it does, there is a problem with the
     *                                           injectStoredValues method.
     */
    public function assertCheckboxChecked($checkbox)
    {
        $checkbox = $this->storeContext->injectStoredValues($checkbox);
        parent::assertCheckboxChecked($checkbox);
    }

    /**
     * This overrides MinkContext::assertCheckboxNotChecked() to inject stored values into the locator.
     *
     * @param  string                   $checkbox The the locator of the checkbox
     * @throws ExpectationException     If the check box is checked.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed, and
     *                                           they do not conform to its requirements. This method does not pass
     *                                           closures, so if this happens, there is a problem with the
     *                                           injectStoredValues method.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                           This should never happen. If it does, there is a problem with the
     *                                           injectStoredValues method.
     */
    public function assertCheckboxNotChecked($checkbox)
    {
        $checkbox = $this->storeContext->injectStoredValues($checkbox);
        parent::assertCheckboxNotChecked($checkbox);
    }

    /**
     * Check the radio button.
     *
     * @When   I check radio button :label
     * @param  string                           $label The label of the radio button.
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             if the radio button was not found on the page.
     * @throws ExpectationException             if the radio button was on the page, but was not visible.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                passed, and they do not conform to its requirements. This method
     *                                                does not pass closures, so if this happens, there is a problem
     *                                                with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                passed. This should never happen. If it does, there is a problem
     *                                                with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    public function ensureRadioButtonChecked($label)
    {
        $this->findRadioButton($label)->click();
    }

    /**
     * Assert the radio button is checked.
     *
     * @Then   /^the "(?P<label>(?:[^"]|\\")*)" radio button should be checked$/
     * @param  string                           $label The label of the radio button.
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             When the radio button is not checked.
     * @throws ExpectationException             if the radio button was not found on the page.
     * @throws ExpectationException             if the radio button was on the page, but was not visible.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                passed, and they do not conform to its requirements. This method
     *                                                does not pass closures, so if this happens, there is a problem
     *                                                with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                passed. This should never happen. If it does, there is a problem
     *                                                with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
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
     * @param  string                           $label The label of the radio button.
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             When the radio button is checked.
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                passed, and they do not conform to its requirements. This method
     *                                                does not pass closures, so if this happens, there is a problem
     *                                                with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                passed. This should never happen. If it does, there is a problem
     *                                                with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
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
     * @param  string                           $label The Label of the radio button.
     * @throws ExpectationException             if the radio button was not found on the page.
     * @throws ExpectationException             if the radio button was on the page, but was not visible.
     * @throws DriverException                  When the operation cannot be done
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                                passed, and they do not conform to its requirements. This method
     *                                                does not pass closures, so if this happens, there is a problem
     *                                                with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                                passed. This should never happen. If it does, there is a problem
     *                                                with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
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
     * @param  NodeElement $a one of the two NodeElements to compare.
     * @param  NodeElement $b the other NodeElement to compare.
     * @return int
     */
    public function compareElementsByCoords(NodeElement $a, NodeElement $b)
    {
        /** @var Selenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        if (!($driver instanceof Selenium2Driver) || !method_exists($driver, 'getXpathBoundingClientRect')) {
            // If not supported by driver, just return -1 so the keep the original sort.
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

    /**
     * Waits for the page to be loaded.
     *
     * This does not wait for any particular javascript frameworks to be ready, it only waits for the DOM to be
     * ready. This is done by waiting for the document.readyState to be "complete".
     *
     * @noinspection PhpDocRedundantThrowsInspection exceptions bubble up from waitFor.
     * @throws ExpectationException    If the page did not finish loading before the timeout expired.
     * @throws SpinnerTimeoutException If the timeout expires before the assertion can be made even once.
     */
    public function waitForPageLoad()
    {
        Spinner::waitFor(function () {
            $readyState = $this->getSession()->evaluateScript('document.readyState');
            if ($readyState !== 'complete') {
                throw new ExpectationException("Page is not loaded. Ready state is '$readyState'", $this->getSession());
            }
        });
    }

    /**
     * Checks if a node Element is fully visible in the viewport.
     *
     * @param  NodeElement                      $element the NodeElement to look for in the viewport.
     * @throws UnsupportedDriverActionException If driver does not support the requested action.
     * @throws Exception                        If cannot get the Web Driver
     * @return bool
     */
    public function nodeIsFullyVisibleInViewport(NodeElement $element)
    {
        $driver = $this->assertSelenium2Driver('Checks if a node Element is fully visible in the viewport.');
        if (!$driver->isDisplayed($element->getXpath()) ||
            count(($parents = $this->getListOfAllNodeElementParents($element, 'html'))) < 1
        ) {
            return false;
        }
        $elementViewportRectangle = $this->getElementViewportRectangle($element);
        foreach ($parents as $parent) {
            if (!$parent->isVisible() ||
                !$elementViewportRectangle->isContainedIn($this->getElementViewportRectangle($parent))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a node Element is visible in the viewport.
     *
     * @param  NodeElement                      $element The NodeElement to check for in the viewport
     * @throws UnsupportedDriverActionException if driver does not support the requested action.
     * @throws Exception                        If cannot get the Web Driver
     * @return bool
     */
    public function nodeIsVisibleInViewport(NodeElement $element)
    {
        $driver = $this->assertSelenium2Driver('Checks if a node Element is visible in the viewport.');

        $parents = $this->getListOfAllNodeElementParents($element, 'html');

        if (!$driver->isDisplayed($element->getXpath()) || count($parents) < 1) {
            return false;
        }

        $elementViewportRectangle = $this->getElementViewportRectangle($element);

        foreach ($parents as $parent) {
            if (!$elementViewportRectangle->overlaps($this->getElementViewportRectangle($parent))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a node Element is visible in the document.
     *
     * @param  NodeElement                      $element NodeElement to to check for in the document
     * @throws Exception                        If cannot get the Web Driver
     * @throws UnsupportedDriverActionException If driver is not the selenium 2 driver
     * @return bool
     */
    public function nodeIsVisibleInDocument(NodeElement $element)
    {
        return $this->assertSelenium2Driver('Check if element is displayed')->isDisplayed($element->getXpath());
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
