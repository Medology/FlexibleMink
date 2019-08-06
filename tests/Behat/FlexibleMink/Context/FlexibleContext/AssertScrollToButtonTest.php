<?php namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ExpectationException;
use PHPUnit_Framework_MockObject_MockObject;

/** This Class tests the scrollToButton function in FlexibleContext. */
class AssertScrollToButtonTest extends FlexibleContextTest
{
    /** @var TraversableElement|PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
    protected $element1;

    /** @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
    protected $element2;

    /** @var NodeElement[] */
    protected $expectedElements;

    protected $locator = 'button';

    public function setUp()
    {
        if (in_array('scrollToButton', $this->flexible_context_mocked_methods)) {
            unset(
                $this->flexible_context_mocked_methods[
                    array_search(
                        'scrollToButton',
                        $this->flexible_context_mocked_methods
                    )
                ]);
        }
        parent::setUp();
    }

    public function testThrowsExceptionWhenButtonIsNotVisibleInPage()
    {
        $this->flexible_context->method('fixStepArgument')->willReturn($this->locator);
        $this->pageMock->method('findAll')->willReturn([]);
        $this->setExpectedException(ExpectationException::class, "No visible button found for '$this->locator'");
        $this->flexible_context->scrollToButton($this->locator);
    }

    public function testThrowsExceptionWhenButtonIsNotVisibleInContext()
    {
        $this->flexible_context->method('fixStepArgument')->willReturn($this->locator);
        $this->mockContext();
        $this->context->method('findAll')->willReturn([]);
        $this->setExpectedException(ExpectationException::class, "No visible button found for '$this->locator'");
        $this->flexible_context->scrollToButton('$this->locator', $this->context);
    }

    public function testReturnsFoundButtonInPage()
    {
        $this->initElements();
        $this->flexible_context->method('fixStepArgument')->willReturn($this->locator);
        $this->pageMock->method('findAll')->willReturn($this->expectedElements);
        $this->flexible_context->method('scrollWindowToFirstVisibleElement')->willReturn($this->element1);
        $elements = $this->flexible_context->scrollToButton($this->locator);
        $this->assertEquals($elements, $this->element1);
    }

    public function testReturnsFoundButtonInContext()
    {
        $this->initElements();
        $this->flexible_context->method('fixStepArgument')->willReturn($this->locator);
        $this->mockContext();
        $this->context->method('findAll')->willReturn($this->expectedElements);
        $this->flexible_context->method('scrollWindowToFirstVisibleElement')->willReturn($this->element1);
        $elements = $this->flexible_context->scrollToButton($this->locator, $this->context);
        $this->assertEquals($elements, $this->element1);
    }

    /** This method Initializes the elements to be used as return values. */
    protected function initElements()
    {
        $this->element1 = $this->getMock(NodeElement::class, [], ['', $this->sessionMock]);
        $this->element2 = $this->getMock(NodeElement::class, [], ['', $this->sessionMock]);
        $this->expectedElements = [$this->element1, $this->element2];
    }

    /** This method mocks the Context to be passed as an parameter to scrollToButton function. */
    protected function mockContext()
    {
        $this->context = $this->getMockForAbstractClass(
            TraversableElement::class, [$this->sessionMock],
            '',
            true,
            true,
            true,
            ['findAll']
        );
    }
}
