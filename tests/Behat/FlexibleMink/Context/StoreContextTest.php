<?php namespace Tests\Behat\FlexibleMink\Context;

use Behat\FlexibleMink\Context\StoreContext;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error_Warning;
use stdClass;
use Tests\Behat\DefaultMocks\MagicMethods;
use TypeError;

class StoreContextTest extends TestCase
{
    use StoreContext;

    /**
     * Creates a simple mock object.
     *
     * @return stdClass A mock object with properties test_property_1/2/3
     */
    private function getMockObject()
    {
        static $obj = null;

        if (is_object($obj)) {
            return $obj;
        }

        $obj = (object) [
            'test_property_1' => 'test_value_1',
            'test_property_2' => 'test_value_2',
            'test_property_3' => 'test_value_3',
        ];

        return $obj;
    }

    /**
     * Tests the StoreContext::injectStoredValues method.
     */
    public function testInjectStoredValues()
    {
        /***********************
         * Set up Mocks
         ***********************/

        $testObj = $this->getMockObject();
        $name = 'testObj';
        $this->put($testObj, $name);


        /***********************
         * Validate First Argument
         ***********************/

        // test empty string and variations
        $this->assertEmpty($this->injectStoredValues(''));
        $this->assertEmpty($this->injectStoredValues(null));

        // test invalid argument for $string
        try {
            $this->injectStoredValues([]);
            $this->expectException(PHPUnit_Framework_Error_Warning::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(PHPUnit_Framework_Error_Warning::class, $e);
        }

        try {
            $this->injectStoredValues(function () {
            });
            $this->expectException(PHPUnit_Framework_Error_Warning::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(PHPUnit_Framework_Error_Warning::class, $e);
        }

        // test reflection of non-matching inputs
        $this->assertEquals(1452, $this->injectStoredValues(1452));
        $this->assertEquals('lol', $this->injectStoredValues('lol'));
        $this->assertEquals('the total_cost of the Order', $this->injectStoredValues('the total_cost of the Order'));
        $this->assertEquals('(the total_cost of the Order', $this->injectStoredValues('(the total_cost of the Order'));
        $this->assertEquals('the total_cost of the Order)', $this->injectStoredValues('the total_cost of the Order)'));
        $this->assertEquals(
            'the (total_cost of the Order)',
            $this->injectStoredValues('the (total_cost of the Order)')
        );
        $this->assertEquals('(the total_cost of Order)', $this->injectStoredValues('(the total_cost of Order)'));

        // test non-existing store key
        $badName = 'FakeObj';
        try {
            $this->injectStoredValues("(the test_property_1 of the $badName)");
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals("Did not find $badName in the store", $e->getMessage());
        }

        // test bad property
        $badProperty = 'bad_property_1';
        try {
            $this->injectStoredValues("(the $badProperty of the $name)");
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals("$name does not have a $badProperty property", $e->getMessage());
        }

        // test valid property and key
        $this->assertEquals(
            $testObj->test_property_1,
            $this->injectStoredValues('(the test_property_1 of the testObj)')
        );


        /***********************
         * Validate Second Argument
         ***********************/

        // test null values
        $this->assertEmpty($this->injectStoredValues('', null));

        // test invalid values
        try {
            $this->injectStoredValues('', '');
            $this->expectException(TypeError::class);
        } catch (TypeError $e) {
            $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
        }

        try {
            $this->injectStoredValues('', 0);
            $this->expectException(TypeError::class);
        } catch (TypeError $e) {
            $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
        }

        try {
            $this->injectStoredValues('', $testObj);
            $this->expectException(TypeError::class);
        } catch (TypeError $e) {
            $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
        }

        // test function with bad arguments
        $badFn = function () {
        };
        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('Method $onGetFn must take one argument!', $e->getMessage());
        }

        $badFn = function ($a, $b) {
        };
        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('Method $onGetFn must take one argument!', $e->getMessage());
        }

        // test function with no return
        $badFn = function ($a) {
            $a = 1;
        };
        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        // test function with bad return
        $badFn = function ($a) {
            return 'bad return';
        };
        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        $badFn = function ($a) {
            return function () {
            };
        };
        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        // test basic reflection
        $goodFn = function ($thing) {
            return $thing;
        };
        $this->assertEquals(
            'test_value_1',
            $this->injectStoredValues('(the test_property_1 of the testObj)', $goodFn)
        );

        // test accessing property after unsetting with callback
        $goodFn = function ($thing) {
            unset($thing->test_property_1);

            return $thing;
        };
        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $goodFn);
            $this->expectException(Exception::class);
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertEquals('testObj does not have a test_property_1 property', $e->getMessage());
        }

        // test accessing property after adding with callback
        $goodFn = function ($thing) {
            $thing->test_property_4 = 'test_value_4';

            return $thing;
        };
        $this->assertEquals(
            'test_value_4',
            $this->injectStoredValues('(the test_property_4 of the testObj)', $goodFn)
        );

        // test overwriting property
        $goodFn = function ($thing) {
            $thing->test_property_1 = 'overwritten';

            return $thing;
        };
        $this->assertEquals(
            'overwritten',
            $this->injectStoredValues('(the test_property_1 of the testObj)', $goodFn)
        );
    }

    /**
     * Tests injectStoredValues using objects with magic properties.
     */
    public function testInjectStoredValuesMagicProperties()
    {
        $name = 'magicMock';
        $mock = $this->getMockBuilder(MagicMethods::class)
            ->setMethods(['__get', '__isset'])
            ->getMock();

        $mock->expects($this->once())
            ->method('__isset')
            ->with('test_property_1')
            ->will($this->returnCallback(function ($prop) {
                return $prop == 'test_property_1';
            }));

        $mock->expects($this->once())
            ->method('__get')
            ->with($this->equalTo('test_property_1'))
            ->will($this->returnValue('test_value_1'));

        $this->put($mock, $name);

        $this->assertEquals('test_value_1', $this->injectStoredValues("(the test_property_1 of the $name)"));
    }

    /**
     * Tests the parseKey function.
     */
    public function testParseKey()
    {
        /***********************
         * Invalid Format Reflects Back
         ***********************/
        $this->assertEquals(['not right', null], $this->parseKey('not right'));
        $this->assertEquals(['not_right', null], $this->parseKey('not_right'));
        $this->assertEquals(['1st_not right', null], $this->parseKey('1st_not right'));
        $this->assertEquals(['not_right_1st', null], $this->parseKey('not_right_1st'));
        $this->assertEquals(['not right 1st', null], $this->parseKey('not right 1st'));


        /***********************
         * Basic 1st, 2nd, 3rd, etc.
         ***********************/
        $this->assertEquals(['University', 1], $this->parseKey('1st University'));
        $this->assertEquals(['University', 2], $this->parseKey('2nd University'));
        $this->assertEquals(['University', 3], $this->parseKey('3rd University'));
        $this->assertEquals(['University', 21], $this->parseKey('21st University'));
        $this->assertEquals(['University', 500], $this->parseKey('500th University'));


        /***********************
         * Strange Key Names
         ***********************/
        $this->assertEquals(['lol$@!@#$', 1], $this->parseKey('1st lol$@!@#$'));
        $this->assertEquals(['%%%%%', 1], $this->parseKey('1st %%%%%'));
        $this->assertEquals(['     ', 42], $this->parseKey('42nd      '));


        /***********************
         * No suffix on numbers
         ***********************/
        $this->assertEquals(['1 University', null], $this->parseKey('1 University'));
        $this->assertEquals(['2 University', null], $this->parseKey('2 University'));
    }
}
