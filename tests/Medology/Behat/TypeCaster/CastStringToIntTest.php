<?php

namespace Tests\Medology\Behat\TypeCaster;

use Medology\Behat\TypeCaster;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\TestCase;

/**
 * Tests numbers are casted properly.
 *
 * @covers \Medology\Behat\TypeCaster::castStringToInt()
 */
class CastStringToIntTest extends TestCase
{
    /** @var PHPUnit_Framework_MockObject_MockObject|TypeCaster  */
    private $typeCasterTraitMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->typeCasterTraitMock = $this->getMockForTrait(TypeCaster::class);
    }

    /**
     * Test numbers are converted properly.
     *
     * @param $input    string The input to the conversion method.
     * @param $expected mixed  The out put of the conversion method.
     *
     * @dataProvider conversionExamples
     */
    public function testNumbersBiggerPhpMaxIntAreCastedToStrings($input, $expected)
    {
        self::assertSame($expected, $this->typeCasterTraitMock->castStringToInt($input));
    }

    /**
     * Conversion examples of expected outputs based on the input.
     */
    public function conversionExamples()
    {
        return [
            'numeric string values more than PHP_INT_MAX are not converted to integers' =>
                ['9223372036854775808', '9223372036854775808'],
            'numeric string values equal to PHP_INT_MAX are converted to integers' =>
                ['9223372036854775807', 9223372036854775807],
            'numeric string values less than PHP_INT_MAX are converted to integers' =>
                ['9223372036854775806', 9223372036854775806],
            'zero string values is converted to a integer' =>
                ['0', 0],
            'low number string value is converted to a integer' =>
                ['1', 1],
            'negative number string value is converted to a integer' =>
                ['-9999', -9999],
            'negative numeric string values less than negative PHP_INT_MAX are not converted to integers' =>
                ['-9223372036854775809', '-9223372036854775809'],
        ];
    }
}
