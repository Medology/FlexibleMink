<?php namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\FlexibleMink\Context\FlexibleContext;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;
use Exception;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @covers \Behat\FlexibleMink\Context\FlexibleContext::pressButton()
 */
class PressButtonTest extends FlexibleContextTest
{
    /**
     * Create a flexible context with some methods declared for mocking that are used in pressButton.
     *
     * @param  array                      $additional_methods Additional methods not specified that may be needed.
     * @return MockObject|FlexibleContext
     */
    protected function getFlexibleMock(array $additional_methods = [])
    {
        $sessionMock = $this->getMock(Session::class, [], [], '', false);
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

        $button = $this->getMock(NodeElement::class, ['getAttribute', 'press'], [], '', false);
        $button->method('getAttribute')->willReturn('enabled');
        $flexible_context->method('scrollToButton')->willReturn($button);

        $exception = new ExpectationException('test', $this->sessionMock);
        $this->setExpectedException(get_class($exception), $exception->getMessage());

        $flexible_context->method('assertNodeElementVisibleInViewport')
            ->willThrowException($exception);
        $button->expects($this->never())->method('press');

        $flexible_context->pressButton('this is a test');
    }

    public function testAttemptingToPressDisabledButtonThrowsException()
    {
        $flexible_context = $this->getFlexibleMock();
        $button_locator = 'test';
        $button = $this->getMock(NodeElement::class, ['getAttribute', 'press'], [], '', false);

        $button->method('getAttribute')->willReturn('disabled');
        $flexible_context->method('scrollToButton')->willReturn($button);

        $this->setExpectedException(ExpectationException::class, "Unable to press disabled button '$button_locator'.");

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
                'scrollToButton',
                $this->getMock(ExpectationException::class, [], [], '', false),
            ],
            [
                'scrollToButton',
                $this->getMock(UnsupportedDriverActionException::class, [], [], '', false),
            ],
            [
                'assertNodeElementVisibleInViewport',
                $this->getMock(ExpectationException::class, [], [], '', false),
            ],
            [
                'assertNodeElementVisibleInViewport',
                $this->getMock(UnsupportedDriverActionException::class, [], [], '', false),
            ],
            [
                'assertNodeElementVisibleInViewport',
                $this->getMock(Exception::class, [], [], '', false),
            ],
        ];
    }

    /**
     * Asserts that an exception called from FlexibleContext methods bubble up.
     *
     * @dataProvider dataFlexibleContextExceptions
     * @param string    $method    Name of method called on mock being tested.
     * @param Exception $exception Exception that should be have bubbled up.
     */
    public function testExceptionsThrownFromFlexibleContextMethodsShouldBubbleOut($method, Exception $exception)
    {
        $flexible_context = $this->getFlexibleMock();
        $button = $this->getMock(NodeElement::class, ['getAttribute', 'press'], [], '', false);
        $button->method('getAttribute')->willReturn('enabled');

        if ($method != 'scrollToButton') {
            $flexible_context->method('scrollToButton')->willReturn($button);
        }

        $flexible_context->method($method)->willThrowException($exception);
        $this->setExpectedException(get_class($exception));

        $flexible_context->pressButton('dsfaljklkj');
    }

    /**
     * Exceptions thrown when calling specified mock, method combination.
     *
     * @return array
     */
    public function dataButtonExceptions()
    {
        return [
            ['getAttribute', $this->getMock(DriverException::class, [], [], '', false)],
            ['getAttribute', $this->getMock(UnsupportedDriverActionException::class, [], [], '', false)],
            ['press',        $this->getMock(DriverException::class, [], [], '', false)],
            ['press',        $this->getMock(UnsupportedDriverActionException::class, [], [], '', false)],
        ];
    }

    /**
     * Asserts that an exception called from Button methods bubble up.
     *
     * @dataProvider dataButtonExceptions
     * @param string    $method    Name of method called on mock being tested.
     * @param Exception $exception Exception that should be have bubbled up.
     */
    public function testExceptionsThrownFromButtonMethodsShouldBubbleOut($method, Exception $exception)
    {
        $flexible_context = $this->getFlexibleMock();
        $button = $this->getMock(NodeElement::class, ['getAttribute', 'press'], [], '', false);
        $flexible_context->method('scrollToButton')->willReturn($button);

        if ($method != 'getAttribute') {
            $button->method('getAttribute')->willReturn('enabled');
        }

        $button->method($method)->willThrowException($exception);
        $this->setExpectedException(get_class($exception));

        $flexible_context->pressButton('dsfaljklkj');
    }
}
