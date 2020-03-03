<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use InvalidArgumentException;

/**
 * Class TableContextInterface.
 */
trait TableContextInterface
{
    // depends
    use StoreContextInterface;

    /**
     * This method asserts if a particular value exists in a cell in the table's BODY.
     *
     * @param string $name  The name of the table
     * @param string $val   The expected value of the cell
     * @param int    $rIdx  The row index of the cell to check (1-indexed)
     * @param int    $cIdx  The column index of the cell to check (1-indexed)
     * @param string $piece The section of the table (one of header/footer/body)
     *
     * @throws ExpectationException if {@paramref $val} does not match the value in the cell
     *
     * @return true
     */
    abstract public function assertCellValue($name, $val, $rIdx, $cIdx, $piece);

    /**
     * This method asserts a set of titles exists in cells in the table header (thead).
     *
     * @param TableNode $attributes A list of titles to search for in the header (ignoring blanks)
     * @param string    $name       The name of the table
     *
     * @throws ExpectationException if a column was found that was not listed in {@paramref $attributes}
     * @throws ExpectationException if any {@paramref $attributes} was not matched to a column
     *
     * @return true
     */
    abstract public function assertTableColumnTitles(TableNode $attributes, $name);

    /**
     * Asserts that a table with the given name exists.
     *
     * @param string $name The name of the table to find
     *
     * @throws ExpectationException If no table is found with id or name matching {@paramref $name}
     *
     * @return NodeElement The matching table with name {@paramref $name}
     */
    abstract public function assertTableExists($name);

    /**
     * Asserts that a table has a certain number of columns.
     *
     * @param string $name The name of the table
     * @param int    $num  The number of columns the table should have
     * @returns true
     *
     * @throws InvalidArgumentException If {@paramref $num} is not an integer
     * @throws ExpectationException     If the number of found columns was not {@paramref $num}
     */
    abstract public function assertTableHasColumns($name, $num = 1);

    /**
     * Asserts that a table has a certain number of BODY (tbody) rows or total rows (including HEAD and FOOT).
     *
     * @param string $name      The name of the table
     * @param int    $num       The number of BODY rows {@paramref $name} table should have
     * @param bool   $fullTable By defualt, only table body rows are used. Setting this to true will count the whole table
     * @returns true
     *
     * @throws InvalidArgumentException If {@paramref $num} is not an integer
     * @throws ExpectationException     If the number of found rows was not {@paramref $num}
     */
    abstract public function assertTableHasRows($name, $num = 1, $fullTable = false);

    /**
     * Reparses the table give by {@paramref $name} ensuring the key store is up to date.
     *
     * @param string $name The name of the table
     *
     * @return array An array of the parsed table as returned by $this->buildTableFromHtml
     */
    abstract public function refreshTable($name);

    /**
     * Ensures there is a table on this page that matches the given table. Cells with * match anything.
     *
     * @return mixed
     */
    abstract public function assertTableWithStructureExists(TableNode $tableNode);

    /**
     * Asserts that the table contains a row with the provided values.
     *
     * @param string    $name      The name of the table
     * @param TableNode $tableNode the list of values to search
     *
     * @throws ExpectationException if the values are not found in the table
     */
    abstract public function assertTableShouldHaveTheFollowingValues($name, TableNode $tableNode);

    /**
     * This method will retrieve a table by its name. If the table is stored in the key store, that will be used,
     * otherwise a fresh parse will be done against the table's HTML. Setting $forceFresh to true will ignore
     * the key store and build the table from HTML.
     *
     * @param string $name       The name of the table to be used in an xpath query
     * @param bool   $forceFresh Setting to true will rebuild the table from HTML and not use the store
     *
     * @return array An array containing parsed rows and cells as returned form $this->buildTableFromHtml
     */
    abstract public function getTableFromName($name, $forceFresh = false);

    /**
     * Asserts that the table contains a row with the provided values.
     *
     * @param string    $name      The name of the table
     * @param TableNode $tableNode the list of values to search
     *
     * @throws ExpectationException if the values are not found in the table
     */
    abstract public function assertTableShouldContainTheFollowingValues($name, TableNode $tableNode);
}
