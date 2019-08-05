<?php namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ExpectationException;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * This Class tests the scrollToButton function in FlexibleContext
 */
class AssertScrollToButtonTest extends FlexibleContextTest
{
    /** @var TraversableElement|PHPUnit_Framework_MockObject_MockObject*/
    protected $context;

    /** @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
    protected $element1;

    /** @var NodeElement|PHPUnit_Framework_MockObject_MockObject */
    protected $element2;

    /** @var NodeElement[] */
    protected $expectedElements;

    public function setUp()
    {
        if(in_array('scrollToButton',$this->flexible_context_mocked_methods)) {
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
        $locator = 'button';
        $this->flexible_context->method('fixStepArgument')->willReturn($locator);
        $this->pageMock->method('findAll')->willReturn([]);
        $this->setExpectedException(ExpectationException::class, "No visible button found for '$locator'");
        $this->flexible_context->scrollToButton('button');
    }

    public function testThrowsExceptionWhenButtonIsNotVisibleInContext()
    {
        $locator = 'button';
        $this->flexible_context->method('fixStepArgument')->willReturn($locator);
        $this->initContext();
        $this->context->method('findAll')->willReturn([]);
        $this->setExpectedException(ExpectationException::class, "No visible button found for '$locator'");
        $this->flexible_context->scrollToButton('button', $this->context);
    }

    public function testReturnsFoundButtonInPage()
    {
        $this->initElements();
        $this->flexible_context->method('fixStepArgument')->willReturn('button');
        $this->pageMock->method('findAll')->willReturn($this->expectedElements);
        $this->flexible_context->method('scrollWindowToFirstVisibleElement')->willReturn($this->element1);
        $elements = $this->flexible_context->scrollToButton('button');
        $this->assertEquals($elements, $this->element1);
    }

    public function testReturnsFoundButtonInContext()
    {
        $this->initElements();
        $this->flexible_context->method('fixStepArgument')->willReturn('button');
        $this->initContext();
        $this->context->method('findAll')->willReturn($this->expectedElements);
        $this->flexible_context->method('scrollWindowToFirstVisibleElement')->willReturn($this->element1);
        $elements = $this->flexible_context->scrollToButton('button', $this->context);
        $this->assertEquals($elements, $this->element1);
    }

    protected function initElements()
    {
        $this->element1 = $this->getMock(NodeElement::class, [], ['', $this->sessionMock]);
        $this->element2 = $this->getMock(NodeElement::class, [], ['', $this->sessionMock]);
        $this->expectedElements = [$this->element1, $this->element2];
    }

    protected function initContext()
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