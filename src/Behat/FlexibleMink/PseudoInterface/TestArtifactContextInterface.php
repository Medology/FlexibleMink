<?php

namespace Behat\FlexibleMink\PseudoInterface;

/**
 * Pseudo interface for Contexts that need to save test artifacts such as logs or screenshots.
 */
trait TestArtifactContextInterface
{
    /**
     * Provides the directory that test artifacts should be stored to.
     *
     * @return string the fully qualified directory, with no trailing directory separator
     */
    abstract public function getArtifactsDir();
}
