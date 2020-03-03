<?php

namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\Mink\Exception\ExpectationException;

/**
 * @covers \Behat\FlexibleMink\Context\FlexibleContext::assertNthElement()
 */
class AssertNthElementTest extends FlexibleContextTest
{
    public function testThrowsExpectationExceptionWhenNoElementFound()
    {
        $this->pageMock->method('findAll')->willReturn([]);
        $this->setExpectedException(ExpectationException::class, "No 'image' was not found");
        $this->flexible_context->assertNthElement('image', 1);
    }

    public function testThrowsExpectationExceptionWhenNthElementNotFound()
    {
        $this->pageMock->method('findAll')->willReturn(['image1']);
        $this->setExpectedException(ExpectationException::class, 'Element image 2 was not found');
        $this->flexible_context->assertNthElement('image', 2);
    }

    public function testReturnsFoundNthElements()
    {
        $expectedElements = ['element1', 'element2'];
        $this->pageMock->method('findAll')->willReturn($expectedElements);
        $elements = $this->flexible_context->assertNthElement('image', 2);
        $this->assertEquals($expectedElements[1], $elements);
    }
}
