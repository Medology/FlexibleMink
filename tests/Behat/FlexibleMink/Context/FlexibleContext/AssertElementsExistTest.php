<?php

namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\Mink\Exception\ExpectationException;

/**
 * @covers \Behat\FlexibleMink\Context\FlexibleContext::assertElementsExist()
 */
class AssertElementsExistTest extends FlexibleContextTest
{
    public function testThrowsExpectationExceptionWhenElementDoesntExist()
    {
        $this->pageMock->method('findAll')->willReturn([]);
        $this->setExpectedException(ExpectationException::class, "No 'image' was not found");
        $this->flexible_context->assertElementsExist('image');
    }

    public function testReturnsAllFoundElements()
    {
        $expectedElements = ['element1', 'element2'];
        $this->pageMock->method('findAll')->willReturn($expectedElements);
        $elements = $this->flexible_context->assertElementsExist('image');
        $this->assertEquals($expectedElements, $elements);
    }

    public function testReturnsTheFoundElements()
    {
        $expectedElements = ['element1'];
        $this->pageMock->method('findAll')->willReturn($expectedElements);
        $elements = $this->flexible_context->assertElementsExist('image');
        $this->assertEquals($expectedElements, $elements);
    }
}
