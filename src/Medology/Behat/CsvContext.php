<?php namespace Medology\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\FlexibleMink\Context\FlexibleContext;
use Behat\Gherkin\Node\TableNode;
use Exception;
use RuntimeException;

/**
 * Provides functionality for working with CSV data.
 */
class CsvContext implements Context, GathersContexts
{
    /** @var FlexibleContext */
    protected $flexibleContext;

    /** @var StoreContext */
    protected $storeContext;

    /**
     * {@inheritdoc}
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        if (!($environment instanceof InitializedContextEnvironment)) {
            throw new RuntimeException(
                'Expected Environment to be ' . InitializedContextEnvironment::class .
                    ', but got ' . get_class($environment)
          );
        }

        if (!$this->flexibleContext = $environment->getContext(FlexibleContext::class)) {
            throw new RuntimeException('Failed to gather FlexibleContext');
        }

        if (!$this->storeContext = $environment->getContext(StoreContext::class)) {
            throw new RuntimeException('Failed to gather StoreContext');
        }
    }

    /**
     * Ensures that the given variable in the store is a CSV containing the given rows.
     * The given rows do not have to contain all columns in the store, but the CSV in the store
     * must contain all of the columns in the expected rows.
     *
     * @Then   /^the "(?P<key>[^"]+)" should be CSV data as follows:$/
     * @param  string    $key   The key the CSV is stored under.
     * @param  TableNode $table The rows expected in the CSV.
     * @throws Exception if the given rows are not present in the CSV.
     */
    public function assertThingIsCSVWithData($key, TableNode $table)
    {
        $expectedData = $table->getRows();

        // Use str_getcsv to first split the CSV data into rows, then again to process each row.
        $actualData = array_map('str_getcsv', str_getcsv($this->storeContext->get($key), "\n"));

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
     * Ensures that the given variable in the store is a CSV with the given column headers.
     * The CSV must contain exactly the rows given, and no more.
     *
     * @Then   /^the "(?P<key>[^"]+)" should be CSV data with the following headers:$/
     * @param  string    $key   The key the CSV is stored under.
     * @param  TableNode $table A list of headers that the CSV must match.
     * @throws Exception if the given headers are not an exact match with the CSV headers.
     */
    public function assertThingIsCSVWithRows($key, TableNode $table)
    {
        $expectedHeaders = $table->getColumn(0);

        $actualHeaders = str_getcsv(str_getcsv($this->storeContext->get($key), "\n")[0]);

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
