<?php namespace Behat\ParallelWorker\Filter;

use Behat\Gherkin\Filter\SimpleFilter;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * A scenario filter which filters on individual scenarios and outlines. Usefull for separating tests onto multiple
 * worker nodes.
 *
 * Based on the behat-partial-runner (http://github.com/m00t/behat-partial-runner) by Anton Serdyuk (m00t).
 *
 * @author Taysir Tayyab
 */
class ParallelWorkerFilter extends SimpleFilter
{
    private $totalNodes;
    private $curNode;
    private $curScenario;
    private $lineMode;

    /**
     * This method takes an example table for a scenario and filters it according to the total number of nodes. Each
     * example is treated like it's own scenario as far as counting goes for the workers.
     *
     * @param  ExampleTableNode $examples The examples of the Scenario Outline
     * @throws RuntimeException If there are no examples in this outline which will run on this node
     * @return ExampleTableNode A filtered table leaving only examples that should run on this node
     */
    private function filterExampleNode(ExampleTableNode $examples)
    {
        $table = $examples->getTable();
        $newExamples = [];

        foreach ($table as $lineNum => $example) {
            // Add the header (first row) automatically, then add the examples that we should run.
            if (!count($newExamples) || $this->curScenario++ % $this->totalNodes == 0) {
                $newExamples[$lineNum] = $example;
            }
        }

        if (count($newExamples) == 1) {
            // All we got was the header.
            throw new RuntimeException('No examples will run on this node!');
        }

        return new ExampleTableNode($newExamples, $examples->getKeyword());
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($curNode = 0, $totalNodes = 1, $lineMode = 0)
    {
        if ($totalNodes <= 0 || $curNode < 0 || $curNode >= $totalNodes) {
            throw new InvalidArgumentException("Received bad arguments for (\$curNode, \$totalNodes): ($curNode, $totalNodes).");
        }

        $this->totalNodes = $totalNodes;
        $this->curNode = $curNode;
        $this->curScenario = $this->totalNodes - $this->curNode;
        $this->lineMode = $lineMode;
    }

    /**
     * {@inheritdoc}
     */
    public function filterFeature(FeatureNode $feature)
    {
        if ($this->lineMode) {
            return $this->handelScenarioAsList($feature);
        }

        $scenarios = [];

        // loop through each scenario in this feature file
        foreach ($feature->getScenarios() as $scenario) {
            // if this is a scenario outline, we need to look at each example
            if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
                try {
                    // filter to just the ones that will run on this node
                    $filteredExampleTable = $this->filterExampleNode($scenario->getExampleTable());
                } catch (RuntimeException $e) {
                    $filteredExampleTable = [];
                }

                if ($filteredExampleTable) {
                    // if there are examples this node can run, recreate the scenario with just the filtered examples
                    $scenario = new OutlineNode(
                        $scenario->getTitle(),
                        $scenario->getTags(),
                        $scenario->getSteps(),
                        $filteredExampleTable,
                        $scenario->getKeyword(),
                        $scenario->getLine()
                    );
                } else {
                    // if there were no examples to run, skip this scenario
                    continue;
                }
            } elseif ($this->curScenario++ % $this->totalNodes != 0) {
                // for regular scenarios, if its not our turn yet, then skip and increment the counter
                continue;
            }

            $scenarios[] = $scenario;
        }

        return new FeatureNode(
            $feature->getTitle(),
            $feature->getDescription(),
            $feature->getTags(),
            $feature->getBackground(),
            $scenarios,
            $feature->getKeyword(),
            $feature->getLanguage(),
            $feature->getFile(),
            $feature->getLine()
        );
    }

    /**
     * When the scenarios is imported by a file like the following:
     * --Start of the file---
     * features/api/v1/coupon.feature:9
     * features/api/v1/coupon.feature:9
     * features/api/v1/coupon.feature:9
     * features/api/v1/coupon.feature:9
     * features/api/v1/coupon.feature:9
     * features/api/v1/coupon.feature:9
     * --End of the file- ----.
     *
     * The scenarios will be executed by behat one by one from exactly the number on the feature file it suffixed to.
     * In this case, we don't have to dig into the feature file and loop thru the scenarios.
     *
     * @param  FeatureNode $feature The feature node that currently filtering.
     * @return FeatureNode
     */
    protected function handelScenarioAsList(FeatureNode $feature)
    {
        if ($this->curScenario++ % $this->totalNodes === 0) {
            return $feature;
        }

        return new FeatureNode(
            $feature->getTitle(),
            $feature->getDescription(),
            $feature->getTags(),
            $feature->getBackground(),
            [],
            $feature->getKeyword(),
            $feature->getLanguage(),
            $feature->getFile(),
            $feature->getLine()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        // we don't want to filter by feature, we want to filter by scenario, so always return false
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        // we do the filtering up in filterFeature, so always return true
        return true;
    }
}
