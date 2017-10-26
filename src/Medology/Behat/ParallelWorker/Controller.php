<?php namespace Medology\Behat\ParallelWorker;

use Behat\Gherkin\Gherkin;
use Behat\Testwork\Cli\Controller as BaseController;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Configures the CLI options for the ParallelWorker extension.
 *
 * Based on the behat-partial-runner (http://github.com/m00t/behat-partial-runner) by Anton Serdyuk (m00t).
 *
 * @author Taysir Tayyab
 */
class Controller implements BaseController
{
    private $gherkin;

    /**
     * {@inheritdoc}
     */
    public function __construct(Gherkin $gherkin)
    {
        $this->gherkin = $gherkin;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Command $command)
    {
        $command
            ->addOption(
                '--total-workers',
                null,
                InputOption::VALUE_REQUIRED,
                'The total number of test nodes.',
                1
            )->addOption(
                '--current-worker',
                null,
                InputOption::VALUE_REQUIRED,
                'The number of the current test node (0-indexed).',
                0
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $total = $input->getOption('total-workers');
        $curr = $input->getOption('current-worker');

        if ($total < 0 || $curr < 0) {
            throw new InvalidArgumentException("--current-worker ($curr) and --total-workers($total) must be greater than 0. ");
        }

        if ($curr >= $total) {
            throw new InvalidArgumentException("--current-worker ($curr) must be less than --total-workers($total). ");
        }

        $this->gherkin->addFilter(new Filter($curr, $total));
    }
}
