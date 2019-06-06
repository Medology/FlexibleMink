<?php namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;
use Exception;
use Medology\Behat\Mink\FlexibleContext;

/**
 * @covers \Medology\Behat\Mink\FlexibleContext::pressButton()
 */
class PressButtonTest extends FlexibleContextTest
{
    protected function getFlexibleMock(array $additional_methods = [])
    {
        $sessionMock = $this->createMock(Session::class);
        $flexible_context = $this->getMockBuilder(FlexibleContext::class)
            ->enableOriginalConstructor()
            ->setMethods(
                array_merge(['scrollToButton', 'assertNodeElementVisibleInViewport', 'getSession'], $additional_methods)
            )
            ->getMock();

        $flexible_context->method('getSession')->willReturn($sessionMock);

        return $flexible_context;
    }

    public function testFailingToSeeNodeElementIsVisibleInViewportPreventsButtonFromBeingPressed()
    {
        // Need mock with original constructor.
        $flexible_context = $this->getFlexibleMock();

        $button = $this->createPartialMock(NodeElement::class, ['getAttribute', 'press']);
        $button->method('getAttribute')->willReturn('enabled');
        $flexible_context->method('scrollToButton')->willReturn($button);

        $exception = new ExpectationException('test', $this->sessionMock);
        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $flexible_context->method('assertNodeElementVisibleInViewport')
            ->willThrowException($exception);
        $button->expects($this->never())->method('press');

        $flexible_context->pressButton('this is a test');
    }

    public function testAttemptingToPressDisabledButtonThrowsException()
    {
        $flexible_context = $this->getFlexibleMock();
        $button_locator = 'test';
        $button = $this->createPartialMock(NodeElement::class, ['getAttribute', 'press']);

        $button->method('getAttribute')->willReturn('disabled');
        $flexible_context->method('scrollToButton')->willReturn($button);

        $this->expectExceptionMessage(ExpectationException::class);
        $this->expectExceptionMessage("Unable to press disabled button '$button_locator'.");

        $button->expects($this->never())->method('press');
        $flexible_context->pressButton($button_locator);
    }

    /**
     * Exceptions thrown when calling specified mock, method combination.
     *
     * @return array
     */
    public function dataFlexibleContextExceptions()
    {
        return [
            [
                'flexible_context',
                'scrollToButton',
                $this->createMock(ExpectationException::class),
            ],
            [
                'flexible_context',
                'scrollToButton',
                $this->createMock(UnsupportedDriverActionException::class),
            ],
            [
                'flexible_context',
                'assertNodeElementVisibleInViewport',
                $this->createMock(ExpectationException::class),
            ],
            [
                'flexible_context',
                'assertNodeElementVisibleInViewport',
                $this->createMock(UnsupportedDriverActionException::class),
            ],
            [
                'flexible_context',
                'assertNodeElementVisibleInViewport',
                $this->createMock(Exception::class),
            ],
            [
                'button',
                'getAttribute',
                $this->createMock(DriverException::class),
            ],
            [
                'button',
                'getAttribute',
                $this->createMock(UnsupportedDriverActionException::class),
            ],
            [
                'button',
                'press',
                $this->createMock(UnsupportedDriverActionException::class),
            ],
            [
                'button',
                'press',
                $this->createMock(DriverException::class),
            ],
        ];
    }

    /**
     * Asserts that an exception called from used methods bubbles up.
     *
     * @param string    $mock      Name of the mock being tested.
     * @param string    $method    Name of method called on mock being tested.
     * @param Exception $exception Exception that should be have bubbled up.
     *
     * @dataProvider dataFlexibleContextExceptions
     */
    public function testExceptionsThrownFromFlexibleContextMethodShouldBubbleOut($mock, $method, Exception $exception)
    {
        $flexible_context = $this->getFlexibleMock();
        $button = $this->createPartialMock(NodeElement::class, ['getAttribute', 'press']);

        if ($mock != 'button' || $method != 'getAttribute') {
            $button->method('getAttribute')->willReturn('enabled');
        }

        if ($mock != 'flexible_context' || $method != 'scrollToButton') {
            $flexible_context->method('scrollToButton')->willReturn($button);
        }

        ${$mock}->method($method)->willThrowException($exception);
        $this->expectException(get_class($exception));

        $flexible_context->pressButton('dsfaljklkj');
    }
}
