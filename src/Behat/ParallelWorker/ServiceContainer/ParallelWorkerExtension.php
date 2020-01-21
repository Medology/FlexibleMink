<?php

namespace Behat\ParallelWorker\ServiceContainer;

use Behat\Behat\Gherkin\ServiceContainer\GherkinExtension;
use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads the ParallelRunner filter for Geherkin.
 *
 * Based on the behat-partial-runner (http://github.com/m00t/behat-partial-runner) by Anton Serdyuk (m00t).
 *
 * @author Taysir Tayyab
 */
class ParallelWorkerExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $definition = new Definition('Behat\ParallelWorker\Controller\ParallelWorkerController', [
            new Reference(GherkinExtension::MANAGER_ID),
        ]);
        $definition->addTag(CliExtension::CONTROLLER_TAG);
        $container->setDefinition(CliExtension::CONTROLLER_TAG . '.parallel_worker', $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'parallel_worker';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
