<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Provides functionality for working with CSV data.
 */
trait CsvContextInterface
{
    /**
     * Ensures that the given variable in the store is a CSV containing the given rows.
     * The given rows do not have to contain all columns in the store, but the CSV in the store
     * must contain all of the columns in the expected rows.
     *
     * @Then /^the "(?P<key>[^"]+)" should be CSV data as follows:$/
     *
     * @param string    $key   the key the CSV is stored under
     * @param TableNode $table the rows expected in the CSV
     *
     * @throws ExpectationException if the given rows are not present in the CSV
     */
    abstract public function assertThingIsCSVWithData($key, TableNode $table);

    /**
     * Ensures that the given variable in the store is a CSV with the given column headers.
     * The CSV must contain exactly the rows given, and no more.
     *
     * @param string    $key   the key the CSV is stored under
     * @param TableNode $table a list of headers that the CSV must match
     *
     * @throws ExpectationException if the given headers are not an exact match with the CSV headers
     */
    abstract public function assertThingIsCSVWithRows($key, TableNode $table);
}
