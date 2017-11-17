<?php namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\FlexibleMink\PseudoInterface\TableContextInterface;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class TableContext.
 */
trait TableContext
{
    // Depends.
    use FlexibleContextInterface;
    // Implements.
    use TableContextInterface;

    /**
     * Finds a table with a given data-qa-id, name, or id. data-qa-id is given preference and matched exactly, while
     * name and id are matched partially.
     *
     * @param  string                   $name The name of the table. Will be matched against id or name properties.
     * @throws ElementNotFoundException If not table is found with id or name {@paramref $name}
     * @throws RuntimeException         If a table is found, but is not visible
     * @return NodeElement              The matched table
     */
    private function findNamedTable($name)
    {
        $idPiece = "contains(normalize-space(@id), '$name')";
        $namePiece = "contains(normalize-space(@name), '$name')";

        /** @var NodeElement $table */
        $table = $this->waitFor(function () use ($idPiece, $namePiece) {
            return $this->assertSession()->elementExists('xpath', "//table[$idPiece or $namePiece]");
        });

        if (!$table) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), 'table', 'xpath', 'id, name');
        }

        if (!$table->isVisible()) {
            throw new RuntimeException("Found table '$name', but it is not visible!");
        }

        return $table;
    }

    /**
     * This method will retrieve a table by its name. If the table is stored in the key store, that will be used,
     * otherwise a fresh parse will be done against the table's HTML. Setting {@param $forceFresh} to true will
     * ignore the key store and build the table from HTML.
     *
     * @param  string $name       The name of the table to be used in an xpath query
     * @param  bool   $forceFresh Setting to true will rebuild the table from HTML and not use the store
     * @return array  An array containing parsed rows and cells as returned form $this->buildTableFromHtml
     */
    protected function getTableFromName($name, $forceFresh = false)
    {
        // retrieve table from the store if it exists there
        if ($this->isStored($name) && !$forceFresh) {
            return $this->get($name);
        }

        // find the table node and parse it's contents
        $table = $this->findNamedTable($name);
        $tableData = $this->buildTableFromHtml($table);

        $this->put($tableData, $name);

        return $tableData;
    }

    /**
     * Retrieves a row from the table HEAD (thead) that has no cells with a colspan property that has a value of greater
     * than 1. This row is assumed to be the column "titles".
     *
     * @param  NodeElement              $table The table to find the columns for
     * @throws InvalidArgumentException If {@paramref $table} is not an instance of NodeElement
     * @throws ElementNotFoundException If no HEAD (thead) rows are found in {@paramref $table}
     * @throws ElementNotFoundException If all head rows have a td/th with colspan property with a value greater than 1
     * @throws ElementNotFoundException If the row has no td/th at all
     * @return NodeElement[]            The columns for {@paramref $table}
     */
    private function findHeadColumns($table)
    {
        if (!($table instanceof NodeElement)) {
            throw new InvalidArgumentException('Parameter $table must be an instance of NodeElement.');
        }

        /** @var NodeElement[] $rows */
        $rows = $table->findAll('xpath', '/thead/tr');

        if (!$rows) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), 'tr', 'xpath');
        }

        $colRow = null;
        foreach ($rows as $row) {
            // finds all td|th elements that have a colspan tag with a value greater than 1. We do this because we don't
            // want any HEAD rows when some of the cells span multiple columns
            /** @var NodeElement[] $splitCell */
            $splitCell = $row->findAll('xpath', '/*[@colspan>\'1\' and (self::td or self::th)]');

            if (!$splitCell) {
                $colRow = $row;
                break;
            }
        }

        // we couldn't find a HEAD row that didn't have split columns
        if (!$colRow) {
            throw new ElementNotFoundException(
                $this->getSession()->getDriver(),
                'tr/(td or th)',
                'xpath',
                'not(@colspan>\'1\')'
            );
        }

        // get all the cells in the selected row
        /** @var NodeElement[] $columns */
        $columns = $colRow->findAll('xpath', '/*[self::td or self::th]');

        if (!$columns) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), 'td or th', 'xpath');
        }

        return $columns;
    }

    /**
     * This method parses an HTML table to build a two-dimensional array indexed by [row][column] for each cell.
     *
     * @param  NodeElement $table   The HTML table to parse
     * @param  string      $keyName The name of the table, for storing in the key store
     * @return array       Returns an array with the following form:
     *                             colHeaders => the best "guess" for column titles
     *                             head => [row][column] Cells parsed from the thead section of the table
     *                             body => [row][column] Cells parsed from the tbody section of the table
     *                             foot => [row][column] Cells parsed from the tfoot section of the table
     */
    private function buildTableFromHtml($table, $keyName = '')
    {
        $colTitles = array_map(function ($ele) {
            return trim($ele->getText());
        }, $this->findHeadColumns($table));

        $colTitles = array_filter($colTitles, function ($ele) {
            return (bool) $ele;
        });

        /* @var NodeElement[] $rows */
        $headRows = $table->findAll('xpath', '/thead/tr');
        $bodyRows = $table->findAll('xpath', '/tbody/tr');
        $footRows = $table->findAll('xpath', '/tfoot/tr');

        /*
         * Anonymous function to retrieve cell values from an array of row nodes. Does not support row and colspans!
         *
         * @param NodeElement[] $rows The rows to parse
         * @return array The cell values for the rows numerically indexed as [row][col]
         */
        $parser = function ($rows) {
            $data = [];

            for ($i = 0; $i < count($rows); $i++) {
                $row = $rows[$i];
                /** @var NodeElement[] $cells */
                $cells = $row->findAll('xpath', '/td|/th');

                for ($j = 0; $j < count($cells); $j++) {
                    /** @var NodeElement $cell */
                    $cell = $cells[$j];

                    //Handle select
                    if (($options = $cell->findAll('xpath', '//option'))) {
                        /** @var NodeElement $option */
                        foreach ($options as $option) {
                            if ($option->isSelected()) {
                                $data[$i][$j] = trim($option->getText());

                                break;
                            }
                        }

                        continue;
                    }
                    $data[$i][$j] = trim($cell->getText());
                }
            }

            return $data;
        };

        // build the table array with parsed cells
        $tableData = [
            'colHeaders' => $colTitles,
            'head'       => $headRows ? $parser($headRows) : [],
            'body'       => $parser($bodyRows),
            'foot'       => $footRows ? $parser($footRows) : [],
        ];

        // if a key name was provided, we can store this array for quick access next time
        if ($keyName) {
            $this->put($tableData, $keyName);
        }

        return $tableData;
    }

    /**
     * This method returns the value of a particular cell from a parsed table.
     *
     * @param  array                    $table A table array as returned by $this->buildTableFromHtml
     * @param  int                      $rIdx  The row index of the cell to retrieve
     * @param  int                      $cIdx  The col index of the cell to retrieve
     * @param  string                   $piece Must be one of (head, body, foot). Specifies which section of the table
     *                                         to look in
     * @throws InvalidArgumentException If $piece is not one of head/body/foot
     * @throws InvalidArgumentException If $rIdx is less than 1
     * @throws InvalidArgumentException If $cIdx is less than 1
     * @throws ExpectationException     If $rIdx is out of bounds
     * @throws ExpectationException     If $cIdx is out of bounds
     * @return string                   The value of the cell
     */
    protected function getCellFromTable($table, $rIdx, $cIdx, $piece = 'body')
    {
        if (!in_array($piece, ['head', 'body', 'foot'])) {
            throw new InvalidArgumentException('$piece must be one of (head, body, foot)!');
        }

        if ($rIdx < 1) {
            throw new InvalidArgumentException('$rIdx must be greater than or equal to 1.');
        }

        if ($cIdx < 1) {
            throw new InvalidArgumentException('$cIdx must be greater than or equal to 1.');
        }

        if (count($table[$piece]) < $rIdx) {
            throw new ExpectationException("The row index $rIdx for the table is out of bounds. Table has " .
                count($table[$piece]) . ' rows.',
                $this->getSession());
        }

        if (count($table[$piece][$rIdx - 1]) < $cIdx) {
            throw new ExpectationException("The col index $cIdx for the table is out of bounds. Table has " .
                count($table[$piece][$rIdx - 1]) . ' cols.',
                $this->getSession());
        }

        return $table[$piece][$rIdx - 1][$cIdx - 1];
    }

    /**
     * {@inheritdoc}
     *
     * @Then the table :name is updated
     */
    public function refreshTable($name)
    {
        return $this->getTableFromName($name, true);
    }

    /**
     * {@inheritdoc}
     *
     * @Given I have a table :name
     * @Then I should see table :name
     */
    public function assertTableExists($name)
    {
        try {
            $table = $this->findNamedTable($name);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException("Could not find table with name '$name'.", $this->getSession());
        }

        return $table;
    }

    /**
     * {@inheritdoc}
     *
     * @Given the table :name has 1 row
     * @Given the table :name has :num rows
     * @Then the table :name should have 1 row
     * @Then the table :name should have :num rows
     */
    public function assertTableHasRows($name, $num = 1, $fullTable = false)
    {
        if (!is_numeric($num) || $num < 0 || (int) $num != $num) {
            throw new InvalidArgumentException('Number of rows must be an integer greater than 0.');
        }

        $table = $this->getTableFromName($name);

        $rowCount = count($table['body']);

        if ($fullTable) {
            $rowCount += count($table['head']) + count($table['body']);
        }

        if ($rowCount != $num) {
            throw new ExpectationException("Expected $num row(s) for table '$name'. Instead got $rowCount.", $this->getSession());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @Given the table :name has 1 column
     * @Given the table :name has :num columns
     * @Then the table :name should have 1 column
     * @Then the table :name should have :num columns
     */
    public function assertTableHasColumns($name, $num = 1)
    {
        if (!is_numeric($num) || $num < 0 || (int) $num != $num) {
            throw new InvalidArgumentException('Number of rows must be an integer greater than 0.');
        }

        $table = $this->getTableFromName($name);
        $colCount = count($table['body'][0]);

        if ($colCount != $num) {
            throw new ExpectationException("Expected $num column(s) for table '$name'. Instead got $colCount.", $this->getSession());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @Given the table :name has the following column titles:
     * @Then the table :name should have the following column titles:
     */
    public function assertTableColumnTitles(TableNode $attributes, $name)
    {
        $expectedCols = array_map(function ($ele) {
            return $ele[0];
        }, $attributes->getRows());

        $table = $this->getTableFromName($name);

        $remainingCols = $expectedCols;

        foreach ($table['colHeaders'] as $colText) {
            if ($colText && !in_array($colText, $expectedCols)) {
                throw new ExpectationException("Found column title $colText, but was not expecting it.", $this->getSession());
            }

            $remainingCols = array_diff($remainingCols, [$colText]);
        }

        if ($remainingCols) {
            throw new ExpectationException("Did not find matches for '" . explode($remainingCols, ',') . "'.", $this->getSession());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @Given /^the table (?P<name>"[^"]+") has (?P<val>"[^"]+") at \((?P<rIdx>\d+),(?P<cIdx>\d+)\) in the (?P<piece>header|body|footer)$/
     * @Then /^the table (?P<name>"[^"]+") should have (?P<val>"[^"]+") at \((?P<rIdx>\d+),(?P<cIdx>\d+)\) in the (?P<piece>header|body|footer)$/
     */
    public function assertCellValue($name, $val, $rIdx, $cIdx, $piece)
    {
        $section = 'body';

        if ($piece == 'header') {
            $section = 'head';
        } elseif ($piece == 'footer') {
            $section = 'foot';
        } elseif ($piece != 'body') {
            throw new InvalidArgumentException("\$piece must be on of header/footer/body. Got '$piece'!");
        }

        $table = $this->getTableFromName($name);
        $cellVal = $this->getCellFromTable($table, $rIdx, $cIdx, $section);

        if ($cellVal != $val) {
            throw new ExpectationException(
                "Expected $val at ($rIdx, $cIdx) in table $piece. Instead got $cellVal!",
                $this->getSession()
            );
        }

        return true;
    }

    /**
     * Ensures there is a table on this page that matches the given table. Cells with * match anything.
     *
     * @Then  there should be a table on the page with the following information:
     * @param TableNode $tableNode The table to compare.
     */
    public function assertTableWithStructureExists(TableNode $tableNode)
    {
        $table = array_map(function ($rowData) {
            return array_map([$this, 'injectStoredValues'], array_values($rowData));
        }, $tableNode->getRows());

        $this->waitFor(function () use ($table) {
            $page = $this->getSession()->getPage();

            /** @var NodeElement[] $domTables */
            $domTables = $page->findAll('css', 'table');
            foreach ($domTables as $domTable) {
                /** @var NodeElement[] $domRows */
                $domRows = $domTable->findAll('xpath', '/thead/tr|tbody/tr');

                if (count($domRows) != count($table)) {
                    // This table doesn't have enough rows to match us.
                    continue 1;
                }

                foreach ($table as $rowNum => $row) {
                    $domRow = $domRows[$rowNum];

                    /** @var NodeElement[] $domCells */
                    $domCells = $domRow->findAll('xpath', '/th|td');

                    if (count($domCells) != count($row)) {
                        // This table doesn't have enough columns to match us.
                        continue 2;
                    }

                    foreach ($row as $cellNum => $cell) {
                        $domCell = $domCells[$cellNum];

                        // Time to finally compare!
                        if ($cell != '*' && $cell != $domCell->getText()) {
                            // Whelp, this cell doesn't match. Onward!
                            continue 3;
                        }
                    }
                }

                // We found a match! Return.
                return true;
            }

            // Oh no! No matches.
            throw new ExpectationException(
                'A table matching the supplied structure could not be found.',
                $this->getSession()
            );
        });
    }

    /**
     * {@inheritdoc}
     *
     * @Then  the table :name should have the following values:
     */
    public function assertTableShouldHaveTheFollowingValues($name, TableNode $tableNode)
    {
        $expectedRow = $tableNode->getRowsHash();

        $this->waitFor(function () use ($name, $expectedRow) {
            $actualTable = $this->getTableFromName($name, true);
            $colHeaders = $actualTable['colHeaders'];

            array_walk($actualTable['body'], function (&$row) use ($colHeaders) {
                $row = array_combine($colHeaders, $row);
            });

            $expectedColumnsCount = count($expectedRow);

            foreach ($actualTable['body'] as $row) {
                if (count(array_intersect_assoc($expectedRow, $row)) == $expectedColumnsCount) {
                    return;
                }
            }

            throw new ExpectationException(
                'A row matching the supplied values could not be found.',
                $this->getSession()
            );
        });
    }
}
