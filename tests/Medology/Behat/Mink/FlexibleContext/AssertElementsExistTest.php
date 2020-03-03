<?php

namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Exception\ExpectationException;

/**
 * @covers \Medology\Behat\Mink\FlexibleContext::assertElementsExist()
 */
class AssertElementsExistTest extends FlexibleContextTest
{
    public function testThrowsExpectationExceptionWhenElementDoesntExist()
    {
        $this->pageMock->method('findAll')->willReturn([]);
        $this->expectException(ExpectationException::class);
        $this->expectExceptionMessage('No \'image\' was found');
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
