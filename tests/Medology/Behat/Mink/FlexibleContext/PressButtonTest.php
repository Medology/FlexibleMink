<?php namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Medology\Behat\Mink\FlexibleContext;

/**
 * @covers \Medology\Behat\Mink\FlexibleContext::pressButton()
 */
class PressButtonTest extends FlexibleContextTest
{
    public function testFailingToSeeNodeElementIsVissibleInViewportPreventsButtonFromBeingPressed()
    {
        // Need mock with original constructor.
        $flexible_context = $this->getMockBuilder(FlexibleContext::class)
            ->enableOriginalConstructor()
            ->setMethods(['scrollToButton', 'assertNodeElementVisibleInViewport'])
            ->getMock();

        $button = $this->createPartialMock(NodeElement::class, ['getAttribute', 'press']);
        $button->method('getAttribute')->willReturn('enabled');
        $flexible_context->method('scrollToButton')->willReturn($button);

        self::expectExceptionMessage('test');
        $flexible_context->method('assertNodeElementVisibleInViewport')
            ->willThrowException(new ExpectationException('test', $this->sessionMock));
        $button->expects($this->never())->method('press');

        $flexible_context->pressButton('this is a test');
    }
}
