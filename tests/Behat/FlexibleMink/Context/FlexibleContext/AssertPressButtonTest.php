<?php namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * This Class tests the pressButton function in FlexibleContext.
 */
class AssertPressButtonTest extends FlexibleContextTest
{
    protected $locator = 'button';

    /** @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
    protected $button;

    public function testIfExceptionThrownInScrollToButtonFunctionBubblesUP()
    {
        $this->setExpectedException(ExpectationException::class, "No visible button found for '$this->locator'");
        /** @var ExpectationException $expectation_exception */
        $expectation_exception = $this->getMock(ExpectationException::class, [], ["No visible button found for '$this->locator'", $this->sessionMock]);
        $this->flexible_context->method('scrollToButton')->willThrowException($expectation_exception);
        $this->flexible_context->pressButton($this->locator);
    }

    public function testThrowsExceptionWhenButtonIsDisabled()
    {
        /* @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
        $this->button = $this->getMock(NodeElement::class, ['getAttribute'], ['', $this->sessionMock]);
        $this->flexible_context->method('scrollToButton')->willReturn($this->button);
        $this->button->method('getAttribute')->willReturn('disabled');
        $this->setExpectedException(ExpectationException::class, "Unable to press disabled button '$this->locator'.");
        $this->flexible_context->pressButton($this->locator);
    }

    public function testSuccessfulButtonPress()
    {
        /* @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
        $this->button = $this->getMock(NodeElement::class, ['getAttribute', 'press'], ['', $this->sessionMock]);
        $this->flexible_context->method('scrollToButton')->willReturn($this->button);
        $this->button->method('getAttribute')->willReturn('enabled');
        $this->button->method('press');
        $this->flexible_context->pressButton($this->locator);
    }
}
