<?php

namespace Behat\FlexibleMink\PseudoInterface;

use Exception;

/**
 * Pseudo interface used for tracking the methods of the ReactContextInterface.
 */
trait ReactContextInterface
{
    /**
     * If react is present, ensures that after every step any pending DOM updates have completed. This is accomplished
     * by creating a new element to be rendered that, when rendered, causes a global to be set to true.
     *
     * @throws Exception If render was not completed in the allotted time.
     */
    abstract public function ensureRenderCompletion();
}
