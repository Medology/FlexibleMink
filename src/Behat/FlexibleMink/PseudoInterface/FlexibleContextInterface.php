<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;
use Exception;
use InvalidArgumentException;

/**
 * Pseudo interface for tracking the methods of the FlexibleContext.
 */
trait FlexibleContextInterface
{
    /**
     * This method overrides the MinkContext::assertPageContainsText() default behavior for assertPageContainsText to
     * ensure that it waits for the text to be available with a max time limit.
     *
     * @see MinkContext::assertPageContainsText
     *
     * @param string $text text to be searched in the page
     */
    abstract public function assertPageContainsText($text);

    /**
     * Asserts that the page contains a list of strings.
     *
     * @param TableNode $table the list of strings to find
     * @param string    $not   a flag to assert not containing text
     *
     * @throws ResponseTextException if the text is not found
     */
    abstract public function assertPageContainsTexts(TableNode $table, $not = null);

    /**
     * This method overrides the MinkContext::assertPageAddress() default behavior by adding a waitFor to ensure that
     * Behat waits for the page to load properly before failing out.
     *
     * @param string $page The address of the page to load
     */
    abstract public function assertPageAddress($page);

    /**
     * Asserts that a field is visible or not.
     *
     * @param string $field The field to be checked
     * @param bool   $not   check if field should be visible or not
     *
     * @throws ExpectationException
     */
    abstract public function assertFieldVisibility($field, $not);

    /**
     * This method overrides the MinkContext::assertPageContainsText() default behavior for assertFieldContains to
     * ensure that it waits for the text to be available with a max time limit.
     *
     * @see MinkContext::assertFieldContains
     *
     * @throws ExpectationException If the field can't be found
     * @throws ExpectationException If the field doesn't match the value
     */
    abstract public function assertFieldContains($field, $value);

    /**
     * Returns Mink session.
     *
     * @param string|null $name name of the session OR active session will be used
     *
     * @return Session
     */
    abstract public function getSession($name = null);

    /**
     * This method overrides the MinkContext::assertPageNotContainsText() default behavior for assertPageNotContainsText
     * to ensure that it waits for the item to not be available with a max time limit.
     *
     * @see MinkContext::assertPageNotContainsText
     *
     * @param string $text the text that should not be found on the page
     */
    abstract public function assertPageNotContainsText($text);

    /**
     * This method will wait to see the text specified for 15 seconds, and then wait another 15 seconds for the text
     * to no longer appear on the page.
     *
     * @see assertPageContainsText()
     * @see assertPageNotContainsText()
     *
     * @param string $text the text to wait on to not show up on the page anymore
     *
     * @throws ResponseTextException if the text is not found initially or if the text was still visible after seeing
     *                               it and waiting for 15 seconds
     */
    abstract public function assertPageContainsTextTemporarily($text);

    /**
     * This method overrides the MinkContext::assertElementContainsText() default behavior for
     * assertElementContainsText to ensure that it waits for the item to be available with a max time limit.
     *
     * @see MinkContext::assertElementContainsText
     *
     * @param string|array $element css element selector
     * @param string       $text    expected text
     */
    abstract public function assertElementContainsText($element, $text);

    /**
     * Checks that elements with specified selector exist.
     *
     * @param string       $elementsSelector the element to search from
     * @param string|array $selectorType     selector type locator
     *
     * @throws ExpectationException when no element is found
     *
     * @return NodeElement[] all elements found with by the given selector
     */
    abstract public function assertElementsExist($elementsSelector, $selectorType = 'css');

    /**
     * Checks that the nth element exists and returns it.
     *
     * @param string       $elementSelector the elements to search from
     * @param int          $nth             this is the nth amount of the element
     * @param string|array $selectorType    selector type locator
     *
     * @throws ExpectationException When there is no Nth element found
     *
     * @return NodeElement the nth element found
     */
    abstract public function assertNthElement($elementSelector, $nth, $selectorType = 'css');

    /**
     * Checks, that element with specified CSS doesn't contain specified text.
     *
     * @see MinkContext::assertElementNotContainsText
     *
     * @param string|array $element css element selector
     * @param string       $text    expected text that should not being found
     */
    abstract public function assertElementNotContainsText($element, $text);

    /**
     * Asserts that all nodes have the specified attribute value.
     *
     * @param string $locator     the attribute locator of the node element
     * @param array  $attributes  A key value paid of the attribute and value the nodes
     *                            should contain
     * @param string $selector    the selector to use to find the node
     * @param null   $occurrences the number of time the node element should be found
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             If the nodes attributes do not match
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    abstract public function assertNodesHaveAttributeValues($locator, array $attributes, $selector = 'named', $occurrences = null);

    /**
     * Clicks a visible link with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::clickLink() default behavior for clickLink to ensure that only visible
     * links are clicked.
     *
     * @see MinkContext::clickLink
     *
     * @param string $locator the id|title|alt|text of the link to be clicked
     */
    abstract public function clickLink($locator);

    /**
     * Clicks a visible checkbox with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::checkOption() default behavior for checkOption to ensure that only visible
     * options are checked and that it waits for the option to be available with a max time limit.
     *
     * @see MinkContext::checkOption
     *
     * @param string $locator the id|title|alt|text of the option to be clicked
     */
    abstract public function checkOption($locator);

    /**
     * Clicks a visible field with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::fillField() default behavior for fill a field to ensure that only visible
     * field is filled.
     *
     * @see MinkContext::fillField
     *
     * @param string $field the id|title|alt|text of the field to be filled
     * @param string $value the value to be set on the field
     */
    abstract public function fillField($field, $value);

    /**
     * Unchecks checkbox with specified id|name|label|value.
     *
     * @see MinkContext::uncheckOption
     *
     * @param string $locator the id|title|alt|text of the option to be unchecked
     */
    abstract public function uncheckOption($locator);

    /**
     * Checks if the selected button is disabled.
     *
     * @param string $locator  The button
     * @param bool   $disabled The state of the button
     *
     * @throws ExpectationException if button is disabled but shouldn't be
     * @throws ExpectationException if button isn't disabled but should be
     * @throws ExpectationException if the button can't be found
     */
    abstract public function assertButtonDisabled($locator, $disabled = true);

    /**
     * Finds the first matching visible button on the page, scrolling to one if necessary.
     *
     * Warning: Will return the first button if the driver does not support visibility checks.
     *
     * @param string             $locator the button name
     * @param TraversableElement $context element on the page to which button belongs
     *
     * @throws ExpectationException if a visible button was not found
     *
     * @return NodeElement the button
     */
    abstract public function scrollToButton($locator, TraversableElement $context = null);

    /**
     * Finds the first matching visible button on the page.
     *
     * Warning: Will return the first button if the driver does not support visibility checks.
     *
     * @param string $locator the button name
     *
     * @throws ExpectationException if a visible button was not found
     *
     * @return NodeElement the button
     */
    abstract public function assertVisibleButton($locator);

    /**
     * Finds the first matching visible link on the page, scrolling to it if necessary.
     *
     * Warning: Will return the first link if the driver does not support visibility checks.
     *
     * @param string $locator the link name
     *
     * @throws ExpectationException if a visible link was not found
     *
     * @return NodeElement the link
     */
    abstract public function scrollToLink($locator);

    /**
     * Finds the first matching visible link on the page.
     *
     * Warning: Will return the first link if the driver does not support visibility checks.
     *
     * @param string $locator the link name
     *
     * @throws ExpectationException if a visible link was not found
     *
     * @return NodeElement the link
     */
    abstract public function assertVisibleLink($locator);

    /**
     * Finds the first matching visible option on the page, scrolling to it if necessary.
     *
     * Warning: Will return the first option if the driver does not support visibility checks.
     *
     * @param string $locator the option name
     *
     * @throws ExpectationException if a visible option was not found
     *
     * @return NodeElement the option
     */
    abstract public function scrollToOption($locator);

    /**
     * Finds the first matching visible option on the page.
     *
     * Warning: Will return the first option if the driver does not support visibility checks.
     *
     * @param string $locator the option name
     *
     * @throws ExpectationException if a visible option was not found
     *
     * @return NodeElement the option
     */
    abstract public function assertVisibleOption($locator);

    /**
     * Checks that the page contains a visible input field, scrolls to it if it's not in the viewport, then returns it.
     *
     * @param string                  $fieldName the input name
     * @param TraversableElement|null $context   the context to search in, if not provided defaults to page
     *
     * @throws ExpectationException if a visible input field is not found
     *
     * @return NodeElement the found input field
     */
    abstract public function scrollToField($fieldName, TraversableElement $context = null);

    /**
     * Checks that the page contains a visible input field and then returns it.
     *
     * @param string                  $fieldName the input name
     * @param TraversableElement|null $context   the context to search in, if not provided defaults to page
     *
     * @throws ExpectationException if a visible input field is not found
     *
     * @return NodeElement the found input field
     */
    abstract public function assertFieldExists($fieldName, TraversableElement $context = null);

    /**
     * Gets all the inputs that have the label name specified within the context specified.
     *
     * @param string             $labelName the label text used to find the inputs for
     * @param TraversableElement $context   the context to search in
     *
     * @return NodeElement[]
     */
    abstract public function getInputsByLabel($labelName, TraversableElement $context);

    /**
     * Checks that the page not contain a visible input field.
     *
     * @param string $fieldName the name of the input field
     *
     * @throws ExpectationException if a visible input field is found
     */
    abstract public function assertFieldNotExists($fieldName);

    /**
     * Checks that the page contains the given lines of text in the order specified.
     *
     * @param TableNode $table a list of text lines to look for
     *
     * @throws ExpectationException     if a line is not found, or is found out of order
     * @throws InvalidArgumentException if the list of lines has more than one column
     */
    abstract public function assertLinesInOrder(TableNode $table);

    /**
     * This method will check if all the fields exists and visible in the current page.
     *
     * @param TableNode $tableNode The id|name|title|alt|value of the input field
     *
     * @throws ExpectationException if any of the fields is not visible in the page
     */
    abstract public function assertPageContainsFields(TableNode $tableNode);

    /**
     * This method will check if all the fields not exists or not visible in the current page.
     *
     * @param TableNode $tableNode The id|name|title|alt|value of the input field
     *
     * @throws ExpectationException if any of the fields is visible in the page
     */
    abstract public function assertPageNotContainsFields(TableNode $tableNode);

    /**
     * Assert if the option exist/not exist in the select.
     *
     * @param string $select    The name of the select
     * @param string $existence The status of the option item
     * @param string $option    The name of the option item
     *
     * @throws ElementNotFoundException If the select is not found in the page
     * @throws ExpectationException     If the option is exist/not exist as expected
     */
    abstract public function assertSelectContainsOption($select, $existence, $option);

    /**
     * Assert if the options in the select match given options.
     *
     * @param string    $select    The name of the select
     * @param TableNode $tableNode the text of the options
     *
     * @throws ExpectationException     when there is no option in the select
     * @throws ExpectationException     when the option(s) in the select not match the option(s) listed
     * @throws InvalidArgumentException when no expected options listed in the test step
     */
    abstract public function assertSelectContainsExactOptions($select, TableNode $tableNode);

    /**
     * Asserts that the specified option is selected.
     *
     * @Then   the :field drop down should have the :option selected
     *
     * @param string $field  the select field
     * @param string $option the option that should be selected in the select field
     *
     * @throws ExpectationException     if the select dropdown doesn't exist in the view
     * @throws ElementNotFoundException if the option is not found in the dropdown
     * @throws ExpectationException     if the option is not selected from the dropdown
     * @throws Exception                if the string references something that does not exist in the store
     */
    abstract public function assertSelectOptionSelected($field, $option);

    /**
     * Attaches a local file to field with specified id|name|label|value. This is used when running behat and
     * browser session in different containers.
     *
     * @param string $field The file field to select the file with
     * @param string $path  The local path of the file
     */
    abstract public function addLocalFileToField($field, $path);

    /**
     * Blurs (unfocuses) selected field.
     *
     * @param string $locator The field to blur
     */
    abstract public function blurField($locator);

    /**
     * Focuses and blurs (unfocuses) the selected field.
     *
     * @param string $locator The field to focus and blur
     */
    abstract public function focusBlurField($locator);

    /**
     * Focuses the selected field.
     *
     * @param string $locator The the field to focus
     */
    abstract public function focusField($locator);

    /**
     * Simulates hitting a keyboard key.
     *
     * @param string $key The key on the keyboard
     */
    abstract public function hitKey($key);

    /**
     * Presses the visible button with specified id|name|title|alt|value.
     *
     * This method overrides the MinkContext::pressButton() default behavior for pressButton to ensure that only visible
     * buttons are pressed and that it waits for the button to be available with a max time limit.
     *
     * @see MinkContext::pressButton
     *
     * @param string             $button  button id, inner text, value or alt
     * @param TraversableElement $context element on the page to which button belongs
     *
     * @throws ExpectationException if a visible button field is not found
     * @throws ExpectationException if Button is found but not visible in the viewport
     */
    abstract public function pressButton($button, TraversableElement $context = null);

    /**
     * Scrolls the window to the top, bottom, left, right (or any valid combination thereof) of the page body.
     *
     * @param string $whereToScroll   The direction to scroll the page. Can be any valid combination of "top", "bottom",
     *                                "left" and "right". e.g. "top", "top right", but not "top bottom"
     * @param bool   $useSmoothScroll use the smooth scrolling behavior if the browser supports it
     */
    abstract public function scrollWindowToBody($whereToScroll, $useSmoothScroll = false);

    /**
     * Finds the first visible element in the given set, prioritizing elements in the viewport but scrolling to one if
     * necessary.
     *
     * @param NodeElement[] $elements the elements to look for
     *
     * @return NodeElement the first visible element
     */
    abstract public function scrollWindowToFirstVisibleElement(array $elements);

    /**
     * Scrolls the window to the given element.
     *
     * @param NodeElement $element the element to scroll to
     */
    abstract public function scrollWindowToElement(NodeElement $element);

    /**
     * This overrides MinkContext::visit() to inject stored values into the URL.
     *
     * @see MinkContext::visit
     */
    abstract public function visit($page);

    /**
     * This overrides MinkContext::assertCheckboxChecked() to inject stored values into the locator.
     *
     * @param string $checkbox The the locator of the checkbox
     */
    abstract public function assertCheckboxChecked($checkbox);

    /**
     * This overrides MinkContext::assertCheckboxNotChecked() to inject stored values into the locator.
     *
     * @param string $checkbox The the locator of the checkbox
     */
    abstract public function assertCheckboxNotChecked($checkbox);

    /**
     * Check the radio button.
     *
     * @param string $label the label of the radio button
     */
    abstract public function ensureRadioButtonChecked($label);

    /**
     * Assert the radio button is checked.
     *
     * @param string $label the label of the radio button
     *
     * @throws ExpectationException when the radio button is not checked
     */
    abstract public function assertRadioButtonChecked($label);

    /**
     * Assert the radio button is not checked.
     *
     * @param string $label the label of the radio button
     *
     * @throws ExpectationException when the radio button is checked
     */
    abstract public function assertRadioButtonNotChecked($label);

    /**
     * Asserts that the node element is visible in the viewport.
     *
     * @param NodeElement $element element expected to be visble in the viewport
     *
     * @throws ExpectationException if the element was not found visible in the viewport
     * @throws Exception            if the assertion did not pass before the timeout was exceeded
     */
    abstract public function assertNodeElementVisibleInViewport(NodeElement $element);
}
