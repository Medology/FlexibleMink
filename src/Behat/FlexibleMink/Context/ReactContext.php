<?php

namespace Behat\FlexibleMink\Context;

use Behat\FlexibleMink\PseudoInterface\MinkContextInterface;
use Behat\FlexibleMink\PseudoInterface\ReactContextInterface;
use Behat\FlexibleMink\PseudoInterface\SpinnerContextInterface;
use Exception;

/**
 * {@inheritdoc}
 */
trait ReactContext
{
    // Implements.
    use ReactContextInterface;

    // Depends.
    use MinkContextInterface;
    use SpinnerContextInterface;

    /**
     * {@inheritdoc}
     * @AfterStep
     */
    public function ensureRenderCompletion()
    {
        $session = $this->getSession();
        // If React is used, add the class and attach it to an element to be rendered.
        $session->executeScript(<<<'JS'
if (typeof React === "undefined") {
    renderComplete = true;

    return;
}

var RenderMonitor = React.createClass({
    displayName: "RenderMonitor",

    render: function render() {
        window.renderComplete = true;
        return null;
    }
});

React.render(React.createElement(RenderMonitor, null), document.createElement('div'));

return true;
JS
        );

        // Listen for render completion.
        if (!$session->wait(30, 'renderComplete')) {
            throw new Exception('React rendering did not complete within 30 seconds.');
        }

        // Reset the bool to false for any subsequent iterations.
        $session->executeScript('renderComplete = false;');
    }
}
