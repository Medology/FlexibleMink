<?php namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Pseudo interface for tracking the methods of the MinkContext.
 *
 * @see MinkContext.
 */
trait MinkContextInterface
{
    /**
     * Checks that page contains the specified text.
     *
     * @param string $text The text that should be found on the page.
     */
    abstract public function assertPageContainsText($text);

    /**
     * Checks the checkbox with specified id|name|label|value.
     *
     * @param string $option The identifier of the option
     */
    abstract public function checkOption($option);

    /**
     * Fills in form input with specified id|name|label|value.
     *
     * @param string $field the input id, name or label
     * @param string $value the value to fill
     */
    abstract public function fillField($field, $value);

    /**
     * Fills in form fields with provided table.
     *
     * @param TableNode $fields Pairs of fields and values to fill. Example:
     *                          | username | bruceWayne |
     *                          | password | iLoveBats123 |
     */
    abstract public function fillFields(TableNode $fields);

    /**
     * Selects option in select field with specified id|name|label|value.
     *
     * @param string $select The select field
     * @param string $option The option to select in the field
     */
    abstract public function selectOption($select, $option);

    /**
     * Returns the Mink session.
     *
     * @param  string|null $name name of the session OR active session will be used
     * @return Session
     */
    abstract public function getSession($name = null);

    /**
     * Presses the button with specified id|name|title|alt|value.
     *
     * @param string             $button  button id, value or alt
     * @param TraversableElement $context Element on the page to which button belongs.
     */
    abstract public function pressButton($button, TraversableElement $context = null);

    /**
     * Opens specified page.
     *
     * @param string $page The URL to visit.
     */
    abstract public function visit($page);

    /**
     * Checks, that current page PATH is equal to specified.
     *
     * @param string $page The path of the path to get asserted.
     */
    abstract public function assertPageAddress($page);
}
