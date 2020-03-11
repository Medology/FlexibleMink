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
use Exception as GenericException;
use InvalidArgumentException;
use Medology\Behat\HasWaitProxy;
use Medology\Behat\Mink\Models\Geometry\Rectangle;
use Medology\Behat\StoreContext;
use Medology\Behat\TypeCaster;
use Medology\Behat\UsesStoreContext;
use Medology\Spinner;
use OutOfBoundsException;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use ReflectionException;
use WebDriver\Exception;
use ZipArchive;

/**
 * Overwrites some MinkContext step definitions to make them more resilient to failures caused by browser/driver
 * discrepancies and unpredictable load times.
 *
 * @property AsyncMink $wait
 */
class FlexibleContext extends MinkContext
{
    use HasWaitProxy;
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

    /** @var AsyncMink */
    protected $wait;

    public function __construct()
    {
        $this->wait = new AsyncMink($this);
    }

    /**
     * {@inheritdoc}
     *
     * Overrides the base method to support injecting stored values and matching URLs that include hostname.
     *
     * @throws DriverException          if the driver failed to perform the action
     * @throws ExpectationException     If the current page is not the expected page.
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     * @throws OutOfBoundsException     if a stored item was referenced in the text and the specified stored item does
     *                                  not have the specified property or key
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     */
    public function assertPageAddress($page)
    {
        $page = $this->storeContext->injectStoredValues($page);

        /* @noinspection PhpUnhandledExceptionInspection */
        Spinner::waitFor(function () use ($page) {
            // is the page a path, or a full URL?
            if (preg_match('!^https?://!', $page) == 0) {
                // it's just a path. delegate to parents implementation
                parent::assertPageAddress($page);
            } else {
                // it's a full URL, compare manually
                $actual = $this->getSession()->getCurrentUrl();
                if (!strpos($actual, $page) === 0) {
                    throw new ExpectationException(sprintf('Current page is "%s", but "%s" expected.', $actual, $page), $this->getSession());
                }
            }
        });
    }

    /**
     * Checks that current url has the specified query parameters.
     *
     * @Then /^(?:|I )should be on "(?P<page>[^"]+)" with the following query parameters:$/
     *
     * @param string    $page       the current page path of the query parameters
     * @param TableNode $parameters the values of the query parameters
     *
     * @throws DriverException      if the driver failed to perform the action
     * @throws ReflectionException  if injectStoredValues incorrectly believes one or more closures were passed
     * @throws ExpectationException if the current page is not the expected page
     * @throws ExpectationException if the one of the current page params are not set
     * @throws ExpectationException if the one of the current page param values does not match with the expected
     */
    public function assertPageAddressWithQueryParameters($page, TableNode $parameters)
    {
        $this->assertPageAddress($page);
        $parts = parse_url($this->getSession()->getCurrentUrl());
        parse_str($parts['query'], $params);

        foreach ($parameters->getRowsHash() as $param => $value) {
            if (!isset($params[$param])) {
                throw new ExpectationException("Query did not contain a $param parameter", $this->getSession());
            }

            if ($params[$param] != $value) {
                throw new ExpectationException("Expected query parameter $param to be $value, but found " . print_r($params[$param], true), $this->getSession());
            }
        }
    }

    /**
     * This method overrides the MinkContext::assertPageContainsText() default behavior for assertPageContainsText to
     * inject stored values into the provided text.
     *
     * @see StoreContext::injectStoredValues()
     *
     * @param string $text text to be searched in the page
     *
     * @throws InvalidArgumentException if the string references something that does not exist in the store
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     if a stored item was referenced in the text and the specified stored item does
     *                                  not have the specified property or key
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ResponseTextException    if the text is not found
     */
    public function assertPageContainsText($text)
    {
        parent::assertPageContainsText($this->storeContext->injectStoredValues($text));
    }

    /**
     * Asserts that the page contains a list of strings.
     *
     * @Then   /^I should (?:|(?P<not>not ))see the following:$/
     *
     * @param TableNode $table the list of strings to find
     * @param string    $not   a flag to assert not containing text
     *
     * @throws InvalidArgumentException if the string references something that does not exist in the store
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     if the specified stored item does not have the specified property or
     *                                  key
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ResponseTextException    if the text is not found
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
     *
     * @param string $text the text that should not be found on the page
     *
     * @throws InvalidArgumentException if the string references something that does not exist in the store
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     if the specified stored item does not have the specified property or
     *                                  key
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
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
     * @throws InvalidArgumentException if the string references something that does not exist in the store
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     if the specified stored item does not have the specified property or
     *                                  key
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
     *
     * @Then   /^the field "(?P<field>[^"]+)" should(?P<not> not|) be visible$/
     *
     * @param string $field The field to be checked
     * @param bool   $not   check if field should be visible or not
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if there is more than one matching field found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param string|array $element css element selector
     * @param string       $text    expected text
     *
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     if the specified stored item does not have the specified property or
     *                                  key
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ElementTextException     if the element does not contain the text
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
     *
     * @param string|array $element css element selector
     * @param string       $text    expected text that should not being found
     *
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed,
     *                                  and they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws OutOfBoundsException     if the specified stored item does not have the specified property or
     *                                  key
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ElementTextException     if the element contains the text
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
     * @param string $element      the selector to find the element
     * @param string $selectorType css|xpath selector type to find the element
     *
     * @throws ElementNotFoundException if the element was not found
     *
     * @return NodeElement the element found
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
     * @param NodeElement $container the base element to search in
     * @param string      $xpath     the XPath of the element to locate inside the container
     *
     * @throws DriverException      When the operation cannot be done
     * @throws ExpectationException if no element was found
     *
     * @return NodeElement the found element
     */
    public function assertElementInsideElement(NodeElement $container, $xpath)
    {
        if (!$element = $container->find('xpath', $xpath)) {
            throw new ExpectationException('Nothing found inside element with xpath $xpath', $this->getSession());
        }

        return $element;
    }

    /**
     * Checks that elements with specified selector exist.
     *
     * @param string       $element      the element to search for
     * @param string|array $selectorType selector type locator
     *
     * @throws ExpectationException when no element is found
     *
     * @return NodeElement[] all elements found with by the given selector
     */
    public function assertElementsExist($element, $selectorType = 'css')
    {
        $session = $this->getSession();

        /* @noinspection PhpUnhandledExceptionInspection */
        return Spinner::waitFor(function () use ($session, $selectorType, $element) {
            if (!$allElements = $session->getPage()->findAll($selectorType, $element)) {
                throw new ExpectationException("No '$element' was found", $session);
            }

            return $allElements;
        });
    }

    /**
     * Checks that the nth element exists and returns it.
     *
     * @param string       $element      the elements to search for
     * @param int          $nth          this is the nth amount of the element
     * @param string|array $selectorType selector type locator
     *
     * @throws ExpectationException when the nth element is not found
     *
     * @return NodeElement the nth element found
     */
    public function assertNthElement($element, $nth, $selectorType = 'css')
    {
        $allElements = $this->assertElementsExist($element, $selectorType);
        if (!isset($allElements[$nth - 1])) {
            throw new ExpectationException("Element $element $nth was not found", $this->getSession());
        }

        return $allElements[$nth - 1];
    }

    /**
     * Clicks a visible link with specified id|title|alt|text.
     * This method overrides the MinkContext::clickLink() default behavior for clickLink to ensure that only visible
     * links are clicked.
     *
     * @see MinkContext::clickLink
     *
     * @param string $locator the id|title|alt|text of the link to be clicked
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified link is not visible
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function clickLink($locator)
    {
        $locator = $this->storeContext->injectStoredValues($locator);
        $this->wait->scrollToLink($locator)->click();
    }

    /**
     * Clicks a visible checkbox with specified id|title|alt|text.
     *
     * This method overrides the MinkContext::checkOption() default behavior for checkOption to ensure that only visible
     * options are checked and inject stored values into the provided locator.
     *
     * @see StoreContext::injectStoredValues()
     * @see MinkContext::checkOption
     *
     * @param string $locator the id|title|alt|text of the option to be clicked
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified option is not visible
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function checkOption($locator)
    {
        $locator = $this->storeContext->injectStoredValues($locator);
        $this->wait->scrollToOption($locator)->check();
    }

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
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified field does not exist
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function fillField($field, $value)
    {
        $field = $this->storeContext->injectStoredValues($field);
        $value = $this->storeContext->injectStoredValues($value);
        $this->wait->scrollToField($field)->setValue($value);
    }

    /**
     * Un-checks checkbox with specified id|name|label|value.
     *
     * @see MinkContext::uncheckOption
     *
     * @param string $locator the id|title|alt|text of the option to be unchecked
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified option is not visible
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function uncheckOption($locator)
    {
        $locator = $this->storeContext->injectStoredValues($locator);
        $this->wait->scrollToOption($locator)->uncheck();
    }

    /**
     * Checks if the selected button is disabled.
     *
     * @todo   fix Given used with Then (incompatible)
     * @Given  the :locator button is :disabled
     * @Then   the :locator button should be :disabled
     *
     * @param string $locator  The button
     * @param bool   $disabled The state of the button
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if button is disabled but shouldn't be
     * @throws ExpectationException             if button isn't disabled but should be
     * @throws ExpectationException             if the button can't be found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
                throw new ExpectationException("The button, $locator, was disabled, but it should not have been disabled.", $this->getSession());
            }
        } elseif ($disabled) {
            throw new ExpectationException("The button, $locator, was not disabled, but it should have been disabled.", $this->getSession());
        }
    }

    /**
     * Asserts that the specified button exists in the DOM.
     *
     * @Then   I should see a :locator button
     *
     * @param string $locator the id|name|title|alt|value of the button
     *
     * @throws DriverException                  when the operation cannot be done
     * @throws ExpectationException             if no button was found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the button
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
     * @param string $locator the button name
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible button was not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the button
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
     * Finds the first matching visible button on the page, scrolling to it if necessary.
     *
     * @param string             $locator the button name
     * @param TraversableElement $context element on the page to which button belongs
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible button was not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the button
     */
    public function scrollToButton($locator, TraversableElement $context = null)
    {
        $locator = $this->fixStepArgument($locator);

        /** @noinspection PhpUnhandledExceptionInspection */
        return Spinner::waitFor(function () use ($locator, $context) {
          $context = $context ? $context : $this->getSession()->getPage();
          $buttons = $context->findAll('named', ['button', $locator]);

          if (!($element = $this->scrollWindowToFirstVisibleElement($buttons))) {
            throw new ExpectationException("No visible button found for '$locator'", $this->getSession());
          }

          return $element;
        });
    }

    /**
     * Finds the first matching visible link on the page.
     *
     * Warning: Will return the first link if the driver does not support visibility checks.
     *
     * @Given  the :locator link is visible
     * @Then   the :locator link should be visible
     *
     * @param string $locator the link name
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible link was not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the link
     */
    public function assertVisibleLink($locator)
    {
        $links = $this->getLinks($locator);

        $links = array_filter($links, function (NodeElement $link) {
            return $link->isVisible();
        });

        if (empty($links)) {
            throw new ExpectationException("No visible link found for '$locator'", $this->getSession());
        }

        // $links is NOT numerically indexed, so just grab the first element and send it back
        return array_shift($links);
    }

    /**
     * Finds the first matching visible link on the page, scrolling to it if necessary.
     *
     * @param string $locator the link name
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible link was not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the link
     */
    public function scrollToLink($locator)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return Spinner::waitFor(function () use ($locator) {
            $links = $this->getLinks($locator);

            if (!($element = $this->scrollWindowToFirstVisibleElement($links))) {
                throw new ExpectationException("No visible link found for '$locator'", $this->getSession());
            }

            return $element;
        });
    }

    /**
     * Returns a set of links matching the given locator.
     *
     * @param string $locator the link name
     *
     * @return NodeElement[] the links matching the given name
     */
    public function getLinks($locator)
    {
        $locator = $this->fixStepArgument($locator);

        // the link selector in Behat/Min/src/Selector/NamedSelector requires anchor tags have href
        // we don't want that, because some don't, so rip out that section. Ideally we would load our own
        // selector with registerNamedXpath, but I want to re-use the link named selector so we're doing it
        // this way
        $xpath = $this->getSession()->getSelectorsHandler()->selectorToXpath('named', ['link', $locator]);
        $xpath = preg_replace('/\[\.\/@href\]/', '', $xpath);

        /* @var NodeElement[] $links */
        return $this->getSession()->getPage()->findAll('xpath', $xpath);
    }

    /**
     * Finds the first matching visible option on the page.
     *
     * Warning: Will return the first option if the driver does not support visibility checks.
     *
     * @param string $locator the option name
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible option was not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the option
     */
    public function assertVisibleOption($locator)
    {
        $locator = $this->fixStepArgument($locator);

        $options = $this->getSession()->getPage()->findAll('named', ['field', $locator]);

        /* @var NodeElement $option */
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
     * Finds the first matching visible option on the page, scrolling to it if necessary.
     *
     * @param string $locator the option name
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible option was not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the option
     */
    public function scrollToOption($locator)
    {
        $locator = $this->fixStepArgument($locator);

        /** @noinspection PhpUnhandledExceptionInspection */
        return Spinner::waitFor(function () use ($locator) {
          $options = $this->getSession()->getPage()->findAll('named', ['field', $locator]);

          if (!($element = $this->scrollWindowToFirstVisibleElement($options))) {
              throw new ExpectationException("No visible option found for '$locator'", $this->getSession());
          }

          return $element;
        });
    }

    /**
     * Checks that the page contains a visible input field and then returns it.
     *
     * @param string                  $fieldName the input name
     * @param TraversableElement|null $context   the context to search in, if not provided defaults to page
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible input field is not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the found input field
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
     * Checks that the page contains a visible input field and then returns it, scrolling to it if necessary.
     *
     * @param string                  $fieldName the input name
     * @param TraversableElement|null $context   the context to search in, if not provided defaults to page
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible input field is not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return NodeElement the found input field
     */
    public function scrollToField($fieldName, TraversableElement $context = null)
    {
      /** @noinspection PhpUnhandledExceptionInspection */
      return Spinner::waitFor(function () use ($fieldName, $context) {
          $context = $context ?: $this->getSession()->getPage();

          /** @var NodeElement[] $fields */
          $fields = ($context->findAll('named', ['field', $fieldName]) ?: $this->getInputsByLabel($fieldName, $context));

          if (!($element = $this->scrollWindowToFirstVisibleElement($fields))) {
              throw new ExpectationException("No visible input found for '$fieldName'", $this->getSession());
          }

          return $element;
      });
    }

    /**
     * Gets all the inputs that have the label name specified within the context specified.
     *
     * @param string             $labelName the label text used to find the inputs for
     * @param TraversableElement $context   the context to search in
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     * @param string $fieldName the name of the input field
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible input field is found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param TableNode $table a list of text lines to look for
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a line is not found, or is found out of order
     * @throws InvalidArgumentException         if the list of lines has more than one column
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param TableNode $tableNode The id|name|title|alt|value of the input field
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if any of the fields is not visible in the page
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param TableNode $tableNode The id|name|title|alt|value of the input field
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if any of the fields is visible in the page
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param string $select    The name of the select
     * @param string $existence The status of the option item
     * @param string $option    The name of the option item
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             If the option does/doesn't exist as expected
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param string    $select    The name of the select
     * @param TableNode $tableNode the text of the options
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             when there is no option in the select
     * @throws ExpectationException             when the option(s) in the select not match the option(s) listed
     * @throws InvalidArgumentException         when no expected options listed in the test step
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This
     *                                          method does not pass closures, so if this happens, there is a
     *                                          problem with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a
     *                                          problem with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
                throw new ExpectationException('Expecting ' . count($expectedOptTexts) . ' matching option(s), found ' . count($intersect), $this->getSession());
            }

            throw new ExpectationException('Options in select match expected but not in expected order', $this->getSession());
        }
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection exceptions are bubbling up from the waitFor's closure
     *
     * Asserts that the specified option is selected.
     *
     * @Then   the :field drop down should have the :option selected
     *
     * @param string $field  the select field
     * @param string $option the option that should be selected in the select field
     *
     * @throws ExpectationException     if the select dropdown doesn't exist in the view even after waiting
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were
     *                                  passed. This should never happen. If it does, there is a
     *                                  problem with the injectStoredValues method.
     * @throws ElementNotFoundException if the option is not found in the dropdown even after waiting
     * @throws ExpectationException     if the option is not selected from the dropdown even after waiting
     */
    public function assertSelectOptionSelected($field, $option)
    {
        /** @var NodeElement $selectField */
        /** @noinspection PhpUnhandledExceptionInspection */
        $selectField = Spinner::waitFor(function () use ($field) {
            return $this->assertFieldExists($field);
        });

        $option = $this->storeContext->injectStoredValues($option);

        /* @noinspection PhpUnhandledExceptionInspection */
        Spinner::waitFor(function () use ($selectField, $option, $field) {
            $optionField = $selectField->find('named', ['option', $option]);

            if (null === $optionField) {
                throw new ElementNotFoundException($this->getSession(), 'select option field', 'id|name|label|value', $option);
            }

            if (!$optionField->isSelected()) {
                throw new ExpectationException('Select option field with value|text "' . $option . '" is not selected in the select "' . $field . '"', $this->getSession());
            }
        });
    }

    /**
     * Sets a cookie.
     *
     * Note: you must request a page before trying to set a cookie, in order to set the domain.
     *
     * @When   /^(?:|I )set the cookie "(?P<key>(?:[^"]|\\")*)" with value (?P<value>.+)$/
     *
     * @param string $key   the name of the key to set
     * @param string $value the value to set the cookie to
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function setCookie($key, $value)
    {
        $this->getSession()->setCookie($key, $value);
    }

    /**
     * Returns all cookies.
     *
     * @throws Exception                        if the operation failed
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return array key/value pairs of cookie name/value
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
     *
     * @param string $key the name of the key to delete
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function deleteCookie($key)
    {
        $this->getSession()->setCookie($key, null);
    }

    /**
     * Deletes all cookies.
     *
     * @When   /^(?:|I )delete all cookies$/
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws Exception                        if the operation failed
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param string $field The file field to select the file with
     * @param string $path  The local path of the file
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ElementNotFoundException         if the field could not be found
     * @throws UnsupportedDriverActionException if getWebDriverSession() is not supported by the current driver
     */
    public function addLocalFileToField($path, $field)
    {
        $driver = $this->assertSelenium2Driver('Add local file to field');

        $field = $this->fixStepArgument($field);

        if ($this->getMinkParameter('files_path')) {
            $fullPath = rtrim(
                realpath($this->getMinkParameter('files_path')),
                DIRECTORY_SEPARATOR
            ) . DIRECTORY_SEPARATOR . $path;
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
     * @throws ExpectationException if the value of the input does not match expected after the file is
     *                              attached
     */
    public function attachFileToField($field, $path)
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        Spinner::waitFor(function () use ($field, $path) {
            parent::attachFileToField($field, $path);

            $session = $this->getSession();
            $value = $session->getPage()->findField($field)->getValue();

            // Workaround for browser's fake path stuff that obscures the directory of the attached file.
            $fileParts = explode(DIRECTORY_SEPARATOR, $path);
            $filename = end($fileParts); // end() cannot take inline expressions, only variables.

            if (strpos($value, $filename) === false) {
                throw new ExpectationException("Value of $field is '$value', expected to contain '$filename'", $session);
            }
        });
    }

    /**
     * Blurs (unfocuses) selected field.
     *
     * @When   /^(?:I |)(?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     *
     * @param string $locator The field to blur
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified field does not exist
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a
     *                                          problem with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function blurField($locator)
    {
        $this->wait->assertFieldExists($locator)->blur();
    }

    /**
     * Focuses and blurs (unfocuses) the selected field.
     *
     * @When   /^(?:I |)focus and (?:blur|unfocus) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     * @When   /^(?:I |)toggle focus (?:on|of) (?:the |)"(?P<locator>[^"]+)"(?: field|)$/
     *
     * @param string $locator The field to focus and blur
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified field does not exist
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a
     *                                          problem with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     *
     * @param string $locator The the field to focus
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if the specified field does not exist
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a
     *                                          problem with the injectStoredValues method.
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     */
    public function focusField($locator)
    {
        $this->wait->assertFieldExists($locator)->focus();
    }

    /**
     * Simulates hitting a keyboard key.
     *
     * @When   /^(?:I |)(?:hit|press) (?:the |)"(?P<key>[^"]+)" key$/
     *
     * @param string $key The key on the keyboard
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws InvalidArgumentException         if $key is not recognized as a valid key
     * @throws UnsupportedDriverActionException when operation not supported by the driver
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
     * @param string             $locator button id, inner text, value or alt
     * @param TraversableElement $context element on the page to which button belongs
     *
     * @throws DriverException                  when the operation cannot be performed
     * @throws ExpectationException             if a visible button field is not found
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     * @throws ExpectationException             if Button is found but not visible in the viewport
     */
    public function pressButton($locator, TraversableElement $context = null)
    {
        /* @noinspection PhpUnhandledExceptionInspection */
        Spinner::waitFor(function () use ($locator, $context) {
            $button = $this->scrollToButton($locator, $context);

            if ($button->getAttribute('disabled') === 'disabled') {
                throw new ExpectationException("Unable to press disabled button '$locator'.", $this->getSession());
            }

            $this->assertNodeElementVisibleInViewport($button);
            $button->press();
        });
    }

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
    public function assertNodesHaveAttributeValues($locator, array $attributes, $selector = 'named', $occurrences = null)
    {
        /** @var NodeElement[] $links */
        $nodes = $this->getSession()->getPage()->findAll($selector, $locator);
        if (!count($nodes)) {
            throw new ExpectationException("No node elements were found on the page using '$locator'", $this->getSession());
        }

        if ($occurrences && count($nodes) !== $occurrences) {
            throw new ExpectationException("Expected $occurrences nodes with '$locator' but found " . count($nodes), $this->getSession());
        }

        foreach ($nodes as $node) {
            if (!$this->elementHasAttributeValues($node, $attributes)) {
                throw new ExpectationException("Expected  node with '$locator' but found " . print_r($node, true), $this->getSession());
            }
        }
    }

    /**
     * @noinspection PhpDocRedundantThrowsInspection exceptions bubble up from waitFor.
     *
     * {@inheritdoc}
     *
     * Overrides the base method to support injecting stored values and restricting interaction to visible options.
     *
     * @param TraversableElement|null $context the context to find the option within. Defaults to entire page.
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ElementNotFoundException         when the option is not found in the select box
     * @throws ExpectationException             if a visible select was not found
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem with
     *                                          the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    public function selectOption($select, $option, TraversableElement $context = null)
    {
        $select = $this->storeContext->injectStoredValues($select);
        $option = $this->storeContext->injectStoredValues($option);

        $this->wait->assertVisibleOptionField($select, $context)->selectOption($option);
    }

    /**
     * Finds all of the matching selects or radios on the page.
     *
     * @param string                  $locator the id|name|label|value|placeholder of the select or radio
     * @param TraversableElement|null $context the context to find the option within. Defaults to entire page.
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     *
     * @return NodeElement[]
     */
    public function getOptionFields($locator, TraversableElement $context = null)
    {
        $context = $context ?: $this->getSession()->getPage();

        return array_filter(
            $context->findAll('named', ['field', $locator]),
            function (NodeElement $field) {
                return $field->getTagName() == 'select' || $field->getAttribute('type') == 'radio';
            }
        );
    }

    /**
     * Finds the first matching visible select or radio on the page.
     *
     * @param string                  $locator the id|name|label|value|placeholder of the select or radio
     * @param TraversableElement|null $context the context to find the option within. Defaults to entire page.
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             if a visible select was not found
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     *
     * @return NodeElement the select or radio
     */
    public function assertVisibleOptionField($locator, TraversableElement $context = null)
    {
        foreach ($this->getOptionFields($locator, $context) as $field) {
            if ($field->isVisible()) {
                return $field;
            }
        }

        throw new ExpectationException("No visible selects or radios for '$locator' were found", $this->getSession());
    }

    /**
     * Scrolls the window to the top, bottom, left, right (or any valid combination thereof) of the page body.
     *
     * @Given /^the page is scrolled to the (?P<whereToScroll>top|bottom)(?:(?P<useSmoothScroll> smoothly)|)$/
     * @When /^(?:I |)scroll to the (?P<whereToScroll>[ a-z]+) of the page(?:(?P<useSmoothScroll> smoothly)|)$/
     *
     * @param string $whereToScroll   The direction to scroll the page. Can be any valid combination of "top",
     *                                "bottom", "left" and "right". e.g. "top", "top right", but not "top bottom"
     * @param bool   $useSmoothScroll use the smooth scrolling behavior if the browser supports it
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    public function scrollWindowToBody($whereToScroll, $useSmoothScroll = false)
    {
        // horizontal scroll
        $scrollHorizontal = 'window.scrollX';

        if (strpos($whereToScroll, 'left') !== false) {
            $scrollHorizontal = 0;
        } elseif (strpos($whereToScroll, 'right') !== false) {
            $scrollHorizontal = 'document.body.scrollWidth';
        }

        // vertical scroll
        $scrollVertical = 'window.scrollY';

        if (strpos($whereToScroll, 'top') !== false) {
            $scrollVertical = 0;
        } elseif (strpos($whereToScroll, 'bottom') !== false) {
            $scrollVertical = 'document.body.scrollHeight';
        }

        $supportsSmoothScroll = $this->getSession()->evaluateScript("'scrollBehavior' in document.documentElement.style");

        if ($useSmoothScroll && $supportsSmoothScroll) {
            $this->getSession()->executeScript("window.scrollTo({top: $scrollVertical, left: $scrollHorizontal, behavior: 'smooth'})");
        } else {
            $this->getSession()->executeScript("window.scrollTo($scrollHorizontal, $scrollVertical)");
        }
    }

    /**
     * This overrides MinkContext::visit() to inject stored values into the URL.
     *
     * @see    MinkContext::visit
     *
     * @param string $page the page to visit
     *
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed, and
     *                                  they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
     */
    public function visit($page)
    {
        parent::visit($this->storeContext->injectStoredValues($page));
    }

    /**
     * This overrides MinkContext::assertCheckboxChecked() to inject stored values into the locator.
     *
     * @param string $checkbox The the locator of the checkbox
     *
     * @throws ExpectationException     if the check box is not checked
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed, and
     *                                  they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
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
     *
     * @throws ExpectationException     if the check box is checked
     * @throws InvalidArgumentException If injectStoredValues incorrectly believes one or more closures were passed, and
     *                                  they do not conform to its requirements. This method does not pass
     *                                  closures, so if this happens, there is a problem with the
     *                                  injectStoredValues method.
     * @throws ReflectionException      If injectStoredValues incorrectly believes one or more closures were passed.
     *                                  This should never happen. If it does, there is a problem with the
     *                                  injectStoredValues method.
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
     *
     * @param string $label the label of the radio button
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             if the radio button was not found on the page
     * @throws ExpectationException             if the radio button was on the page, but was not visible
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     */
    public function ensureRadioButtonChecked($label)
    {
        $this->wait->findRadioButton($label)->click();
    }

    /**
     * Assert the radio button is checked.
     *
     * @Then   /^the "(?P<label>(?:[^"]|\\")*)" radio button should be checked$/
     *
     * @param string $label the label of the radio button
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             when the radio button is not checked
     * @throws ExpectationException             if the radio button was not found on the page
     * @throws ExpectationException             if the radio button was on the page, but was not visible
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
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
     *
     * @param string $label the label of the radio button
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws ExpectationException             when the radio button is checked
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
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
     * @param NodeElement $node       the node to check the expected attributes against
     * @param array       $attributes an associative array of the expected attributes
     *
     * @throws DriverException                  When the operation cannot be done
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     *
     * @return bool true if the element has the specified attribute values, false if not
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
     * Compares two Elements and determines which is "first".
     *
     * This is for use with usort (and similar) functions, for sorting a list of
     * NodeElements by their coordinates. The typical use case is to determine
     * the order of elements on a page as a viewer would perceive them.
     *
     * @param NodeElement $a one of the two NodeElements to compare
     * @param NodeElement $b the other NodeElement to compare
     *
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
     * @return string the fully qualified directory, with no trailing directory separator
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
     *
     * @throws ExpectationException if the page did not finish loading before the timeout expired
     */
    public function waitForPageLoad()
    {
        /* @noinspection PhpUnhandledExceptionInspection throws ExpectationException, not Exception. */
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
     * @param NodeElement $element the NodeElement to look for in the viewport
     *
     * @throws UnsupportedDriverActionException if driver does not support the requested action
     * @throws Exception                        If cannot get the Web Driver
     *
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
     * Asserts that the node element is visible in the viewport.
     *
     * @param NodeElement $element element expected to be visble in the viewport
     *
     * @throws ExpectationException if the element was not found visible in the viewport
     * @throws GenericException     if the assertion did not pass before the timeout was exceeded
     */
    public function assertNodeElementVisibleInViewport(NodeElement $element)
    {
        if (!$this->nodeIsVisibleInViewport($element)) {
            throw new ExpectationException('The following element was expected to be visible in viewport, but was not: ' . $element->getHtml(), $this->getSession());
        }
    }

    /**
     * Checks if a node Element is visible in the viewport.
     *
     * @param NodeElement $element The NodeElement to check for in the viewport
     *
     * @throws UnsupportedDriverActionException if driver does not support the requested action
     * @throws Exception                        If cannot get the Web Driver
     *
     * @return bool
     */
    public function nodeIsVisibleInViewport(NodeElement $element)
    {
        $driver = $this->assertSelenium2Driver('Checks if a node Element is visible in the viewport.');

        $parents = $this->getListOfAllNodeElementParents($element, 'body');

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
     * @param NodeElement $element NodeElement to to check for in the document
     *
     * @throws Exception                        If cannot get the Web Driver
     * @throws UnsupportedDriverActionException If driver is not the selenium 2 driver
     *
     * @return bool
     */
    public function nodeIsVisibleInDocument(NodeElement $element)
    {
        return $this->assertSelenium2Driver('Check if element is displayed')->isDisplayed($element->getXpath());
    }

    /**
     * Get a rectangle that represents the location of a NodeElements viewport.
     *
     * @param NodeElement $element nodeElement to get the viewport of
     *
     * @throws UnsupportedDriverActionException when operation not supported by the driver
     *
     * @return Rectangle representing the viewport
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
     * Step to assert that the specified element is not covered.
     *
     * @param string $identifier element Id to find the element used in the assertion
     *
     * @throws ExpectationException if element is found to be covered by another
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
     * @param NodeElement $element  the element to assert that is not covered by something else
     * @param int         $leniency percent of leniency when performing each pixel check
     *
     * @throws ExpectationException     if element is found to be covered by another
     * @throws InvalidArgumentException the threshold provided is outside of the 0-100 range accepted
     */
    public function assertElementIsNotCovered(NodeElement $element, $leniency = 20)
    {
        if ($leniency < 0 || $leniency > 99) {
            throw new InvalidArgumentException('The leniency provided is outside of the 0-50 range accepted.');
        }

        $xpath = $element->getXpath();

        /** @var array $coordinates */
        $coordinates = $this->getSession()->evaluateScript(
            <<<JS
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
         * @param int $x      starting X position
         * @param int $y      starting Y position
         * @param int $xLimit width of element
         *
         * @throws ExpectationException if element is found to be covered by another in the row specified
         */
        $assertRow = function ($x, $y, $xLimit) use ($expected, $xSpacing) {
            while ($x < $xLimit) {
                $found = $this->getSession()->evaluateScript("return document.elementFromPoint($x, $y).outerHTML;");
                if (strpos($expected, $found) === false) {
                    throw new ExpectationException('An element is above an interacting element.', $this->getSession());
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
     * @param string $operation the operation that you will attempt to perform that requires
     *                          the Selenium 2 driver
     *
     * @throws UnsupportedDriverActionException if the current driver is not Selenium 2
     *
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

    /**
     * Finds the first visible element in the given set, prioritizing elements in the viewport but scrolling to one if
     * necessary.
     *
     * @param NodeElement[] $elements the elements to look for
     *
     * @return NodeElement the first visible element
     */
    public function scrollWindowToFirstVisibleElement(array $elements)
    {
        foreach ($elements as $field) {
            if ($field->isVisible()) {
                return $field;
            }
        }

        // No fields are visible on the page, so try scrolling to each field and see if they become visible that way.
        foreach ($elements as $field) {
            $this->scrollWindowToElement($field);

            if ($field->isVisible()) {
                $ret = $field;

                break;
            }
        }

        return isset($ret) ? $ret : null;
    }

    /**
     * Scrolls the window to the given element.
     *
     * @param NodeElement $element the element to scroll to
     */
    public function scrollWindowToElement(NodeElement $element)
    {
        $xpath = json_encode($element->getXpath());
        $this->getSession()->evaluateScript(
            <<<JS
            document.evaluate($xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)
                .singleNodeValue
                .scrollIntoView(false)
JS
        );
    }

    /**
     * Locate the radio button by label.
     *
     * @param string $label the Label of the radio button
     *
     * @throws ExpectationException             if the radio button was not found on the page
     * @throws ExpectationException             if the radio button was on the page, but was not visible
     * @throws DriverException                  When the operation cannot be done
     * @throws InvalidArgumentException         If injectStoredValues incorrectly believes one or more closures were
     *                                          passed, and they do not conform to its requirements. This method
     *                                          does not pass closures, so if this happens, there is a problem
     *                                          with the injectStoredValues method.
     * @throws ReflectionException              If injectStoredValues incorrectly believes one or more closures were
     *                                          passed. This should never happen. If it does, there is a problem
     *                                          with the injectStoredValues method.
     * @throws UnsupportedDriverActionException When operation not supported by the driver
     *
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

        usort($radioButtons, [$this, 'compareElementsByCoords']);
        $radioButton = $this->scrollWindowToFirstVisibleElement($radioButtons);

        if (!$radioButton) {
            throw new ExpectationException('No Visible Radio Button was found on the page', $this->getSession());
        }

        return $radioButton;
    }

    /**
     * Get list of of all NodeElement parents.
     *
     * @param string $stopAt html tag to stop at
     *
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
}
