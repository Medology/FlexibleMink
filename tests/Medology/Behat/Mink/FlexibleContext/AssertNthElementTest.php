<?php namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Exception\ExpectationException;

/**
 * @covers \Medology\Behat\Mink\FlexibleContext::assertNthElement()
 */
class AssertNthElementTest extends FlexibleContextTest
{
    public function testThrowsExpectationExceptionWhenNoElementFound()
    {
        $this->pageMock->method('findAll')->willReturn([]);
        $this->expectException(ExpectationException::class);
        $this->flexible_context->assertNthElement('image', 1);
    }

    public function testThrowsExpectationExceptionWhenNthElementNotFound()
    {
        $this->pageMock->method('findAll')->willReturn(['image1']);
        $this->expectException(ExpectationException::class);
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
