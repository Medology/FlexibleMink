<?php

namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;
use Exception;
use Medology\Behat\Mink\FlexibleContext;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @covers \Medology\Behat\Mink\FlexibleContext::pressButton()
 */
class PressButtonTest extends FlexibleContextTest
{
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
            ['scrollToButton',                     $this->createMock(ExpectationException::class)],
            ['scrollToButton',                     $this->createMock(UnsupportedDriverActionException::class)],
            ['assertNodeElementVisibleInViewport', $this->createMock(ExpectationException::class)],
            ['assertNodeElementVisibleInViewport', $this->createMock(UnsupportedDriverActionException::class)],
            ['assertNodeElementVisibleInViewport', $this->createMock(Exception::class)],
        ];
    }

    /**
     * Asserts that an exception called from FlexibleContext methods bubble up.
     *
     * @dataProvider dataFlexibleContextExceptions
     *
     * @param string    $method    name of method called on mock being tested
     * @param Exception $exception exception that should be have bubbled up
     */
    public function testExceptionsThrownFromFlexibleContextMethodsShouldBubbleOut($method, Exception $exception)
    {
        $flexible_context = $this->getFlexibleMock();
        $button = $this->createPartialMock(NodeElement::class, ['getAttribute', 'press']);
        $button->method('getAttribute')->willReturn('enabled');

        if ($method != 'scrollToButton') {
            $flexible_context->method('scrollToButton')->willReturn($button);
        }

        $flexible_context->method($method)->willThrowException($exception);
        $this->expectException(get_class($exception));

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
            ['getAttribute', $this->createMock(DriverException::class)],
            ['getAttribute', $this->createMock(UnsupportedDriverActionException::class)],
            ['press',        $this->createMock(DriverException::class)],
            ['press',        $this->createMock(UnsupportedDriverActionException::class)],
        ];
    }

    /**
     * Asserts that an exception called from Button methods bubble up.
     *
     * @dataProvider dataButtonExceptions
     *
     * @param string    $method    name of method called on mock being tested
     * @param Exception $exception exception that should be have bubbled up
     */
    public function testExceptionsThrownFromButtonMethodsShouldBubbleOut($method, Exception $exception)
    {
        $flexible_context = $this->getFlexibleMock();
        $button = $this->createPartialMock(NodeElement::class, ['getAttribute', 'press']);
        $flexible_context->method('scrollToButton')->willReturn($button);

        if ($method != 'getAttribute') {
            $button->method('getAttribute')->willReturn('enabled');
        }

        $button->method($method)->willThrowException($exception);
        $this->expectException(get_class($exception));

        $flexible_context->pressButton('dsfaljklkj');
    }

    /**
     * Create a flexible context with some methods declared for mocking that are used in pressButton.
     *
     * @param array $additional_methods additional methods not specified that may be needed
     *
     * @return MockObject|FlexibleContext
     */
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
}
