<?php namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use PHPUnit_Framework_MockObject_MockObject;

/** This Class tests the pressButton function in FlexibleContext. */
class AssertPressButtonTest extends FlexibleContextTest
{
    protected $locator = 'button';

    /** @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
    protected $button;

    /** @var ExpectationException $expectation_exception */
    protected $expectation_exception;

    public function testIfExceptionThrownInScrollToButtonFunctionBubblesUP()
    {
        $exceptionMessage = "No visible button found for '$this->locator'";
        $this->mockAndSetExpectationException($exceptionMessage);
        $this->flexible_context->method('scrollToButton')->willThrowException($this->expectation_exception);
        $this->flexible_context->pressButton($this->locator);
    }

    public function testThrowsExceptionWhenButtonIsDisabled()
    {
        $this->initCommonSteps(['getAttribute'], 'disabled');
        $this->setExpectedException(ExpectationException::class, "Unable to press disabled button '$this->locator'.");
        $this->flexible_context->pressButton($this->locator);
    }

    public function testThrowsExceptionWhenButtonIsNotVisibleInViewPort()
    {
        $this->initCommonSteps(['getAttribute'], null);
        $exceptionMessage = 'The following element was expected to be visible in viewport, but was not:';
        $this->mockAndSetExpectationException($exceptionMessage);
        $this->flexible_context->method('assertNodeElementVisibleInViewport')->willThrowException($this->expectation_exception);
        $this->flexible_context->pressButton($this->locator);
    }

    public function testSuccessfulButtonPress()
    {
        $this->initCommonSteps(['getAttribute', 'press'], null);
        $this->flexible_context->method('assertNodeElementVisibleInViewport');
        $this->button->method('press');
        $this->flexible_context->pressButton($this->locator);
    }

    /**
     * This method will mock and set the ExpectationException.
     *
     * @param string $exceptionMessage The message while the exception is thrown
     */
    protected function mockAndSetExpectationException($exceptionMessage)
    {
        $this->expectation_exception = $this->getMock(ExpectationException::class, [], [$exceptionMessage, $this->sessionMock]);
        $this->setExpectedException(ExpectationException::class, $exceptionMessage);
    }

    /**
     * This method initializes the common steps to run the tests in this class.
     *
     * @param array  $nodeElementMockMethods   The methods to be mocked
     * @param string $getAttributeReturnValue  The value that need to be returned when getAttribute method is mocked
     */
    protected function initCommonSteps(array $nodeElementMockMethods, $getAttributeReturnValue)
    {
        $this->button = $this->getMock(NodeElement::class, $nodeElementMockMethods, ['', $this->sessionMock]);
        $this->flexible_context->method('scrollToButton')->willReturn($this->button);
        $this->button->method('getAttribute')->willReturn($getAttributeReturnValue);
    }
}
