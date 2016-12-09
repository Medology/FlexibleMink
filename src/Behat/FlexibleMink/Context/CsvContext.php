<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\CsvContextInterface;
use Behat\FlexibleMink\PseudoInterface\FlexibleContextInterface;
use Behat\FlexibleMink\PseudoInterface\StoreContextInterface;
use Behat\Gherkin\Node\TableNode;
use Exception;

/**
 * {@inheritdoc}
 */
trait CsvContext
{
    // Implements
    use CsvContextInterface;
    // Depends
    use FlexibleContextInterface;
    use StoreContextInterface;

    /**
     * {@inheritdoc}
     *
     * @Then /^the "(?P<key>[^"]+)" should be CSV data as follows:$/
     */
    public function assertThingIsCSVWithData($key, TableNode $table)
    {
        $expectedData = $table->getRows();

        // Use str_getcsv to first split the CSV data into rows, then again to process each row.
        $actualData = array_map('str_getcsv', str_getcsv($this->get($key), "\n"));

        if (($expectedCount = count($expectedData)) !== ($actualCount = count($actualData))) {
            throw new Exception("Expected $expectedCount rows, but found $actualCount");
        }

        // record the column numbers for the expected and actual headers so we can
        // compare similarly named columns even if they are not in the same order.
        $expectedHeaders = array_flip($expectedData[0]);
        $actualHeaders = array_flip($actualData[0]);
        unset($expectedData[0]);
        unset($actualData[0]);

        // iterate over each expected row
        foreach ($expectedData as $rowNum => $expectedRow) {
            $actualRow = $actualData[$rowNum];

            // iterate over each expected column
            foreach ($expectedHeaders as $name => $colNum) {
                if (!isset($actualHeaders[$name])) {
                    throw new Exception("Column $name does not exist, but was expected to");
                }

                $expectedValue = isset($expectedRow[$colNum]) ? $expectedRow[$colNum] : null;
                $actualValue = isset($actualRow[$actualHeaders[$name]]) ? $actualRow[$actualHeaders[$name]] : null;

                // check values match
                if ($expectedValue != $actualValue) {
                    throw new Exception(
                        "Expected '$expectedValue' for '$name' in row $rowNum, but found '$actualValue'"
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @Then /^the "(?P<key>[^"]+)" should be CSV data with the following headers:$/
     */
    public function assertThingIsCSVWithRows($key, TableNode $table)
    {
        $expectedHeaders = $table->getColumn(0);

        $actualHeaders = str_getcsv(str_getcsv($this->get($key), "\n")[0]);

        if ($diff = array_diff($expectedHeaders, $actualHeaders)) {
            $missing = implode("', '", $diff);
            throw new Exception("CSV '$key' is missing headers '$missing'");
        }

        if ($diff = array_diff($actualHeaders, $expectedHeaders)) {
            $extra = implode("', '", $diff);
            throw new Exception("CSV '$key' contains extra headers '$extra'");
        }
    }
}
