<?php namespace Tests\Behat\FlexibleMink\Context;

use Behat\FlexibleMink\Context\StoreContext;
use DateTime;
use Exception;
use PHPUnit_Framework_Error;
use PHPUnit_Framework_TestCase;
use stdClass;
use TypeError;

class StoreContextTest extends PHPUnit_Framework_TestCase
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
            'date_prop'       => new DateTime('2028-10-28 15:30:10'),
        ];

        return $obj;
    }

    /**
     * Expects the correct type error exception depending on the php version.
     *
     * @throws Exception When a unsupported version of PHP is being used.
     */
    protected function expectTypeErrorException()
    {
        list($majorVersion, $minorVersion) = explode('.', PHP_VERSION, 3);

        if ($majorVersion >= 7) {
            $this->setExpectedException(TypeError::class);
        } elseif ($majorVersion == 5 && $minorVersion == 6) {
            $this->setExpectedException(PHPUnit_Framework_Error::class);
        } else {
            throw new Exception('This php version is not supported. PHP version must be >= 5.6');
        }
    }

    /**
     * Asserts that a function throws a type error that contains a string.
     *
     * @param  callable  $fn              A closure expected to throw the exception.
     * @param  string    $expectedMessage The message expected to be found in the exception message.
     * @throws Exception When a unsupported version of PHP is being used.
     */
    protected function assertFunctionThrowsTypeErrorThatContainsMessage(callable $fn, $expectedMessage)
    {
        $this->expectTypeErrorException();

        try {
            $fn();
        } catch (TypeError $e) {
            $this->assertContains($expectedMessage, $e->getMessage());

            throw $e;
        } catch (PHPUnit_Framework_Error $e) {
            $this->assertContains($expectedMessage, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Tests that an error is thrown when second argument of injectStoredValues is an empty string.
     *
     * @throws Exception When a unsupported version of PHP is being used.
     */
    public function testErrorIsThrownWhenSecondArgumentOfInjectStoredValuesIsAnEmptyString()
    {
        $this->assertFunctionThrowsTypeErrorThatContainsMessage(function () {
            $this->injectStoredValues('', '');
        }, 'injectStoredValues() must be callable');
    }

    /**
     * Tests that an error is thrown when second argument of injectStoredValues is an empty string.
     *
     * @throws Exception When a unsupported version of PHP is being used.
     */
    public function testErrorIsThrownWhenSecondArgumentOfInjectStoredValuesIsAnInteger()
    {
        $this->assertFunctionThrowsTypeErrorThatContainsMessage(function () {
            $this->injectStoredValues('', 0);
        }, 'injectStoredValues() must be callable');
    }

    /**
     * Tests that an error is thrown when second argument of injectStoredValues is an empty string.
     *
     * @throws Exception When a unsupported version of PHP is being used.
     */
    public function testErrorIsThrownWhenSecondArgumentOfInjectStoredValuesIsAnObject()
    {
        $this->assertFunctionThrowsTypeErrorThatContainsMessage(function () {
            $this->injectStoredValues('', $this->getMockObject());
        }, 'injectStoredValues() must be callable');
    }

    /**
     * Test that a non-callable has value throws appropriate error.
     *
     * @dataProvider nonCallableValuesProvider
     *
     * @param  mixed     $nonCallable Non-callable variable from data provider.
     * @throws Exception When a unsupported version of PHP is being used.
     */
    public function testNonCallableHasValueThrowsAppropriateError($nonCallable)
    {
        $this->assertFunctionThrowsTypeErrorThatContainsMessage(function () use ($nonCallable) {
            $this->injectStoredValues('', null, $nonCallable);
        }, 'injectStoredValues() must be callable');
    }

    /**
     * Returns a list of non-callable values.
     *
     * @return array
     */
    public function nonCallableValuesProvider()
    {
        return [[''], [0],  [$this->getMockObject()]];
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
            $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        } catch (Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning', $e);
        }

        try {
            $this->injectStoredValues(function () {
            });
            $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        } catch (Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning', $e);
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
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals("Did not find $badName in the store", $e->getMessage());
        }

        // test bad property
        $badProperty = 'bad_property_1';

        try {
            $this->injectStoredValues("(the $badProperty of the $name)");
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
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

        // test function with bad arguments
        $badFn = function () {
        };

        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('TypeError');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('Method $onGetFn must take one argument!', $e->getMessage());
        }

        $badFn = function ($a, $b) {
        };

        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('Method $onGetFn must take one argument!', $e->getMessage());
        }

        // test function with no return
        $badFn = function ($a) {
            $a = 1;
        };

        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        // test function with bad return
        $badFn = function ($a) {
            return 'bad return';
        };

        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        $badFn = function ($a) {
            return function () {
            };
        };

        try {
            $this->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
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
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
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

        /******************************
         * Validate $hasValue argument
         *****************************/

        // Null $hasValue should default to using isset
        $this->assertEmpty($this->injectStoredValues('', null, null));

        // Lambda without two args throws appropriate error
        $wrongArgCounts = [
            function () {
            },
            function ($a) {
            },
            function ($a, $b, $c) {
            },
        ];
        foreach ($wrongArgCounts as $wrongArgCount) {
            try {
                $this->injectStoredValues('(the test_property_1 of the testObj)', null, $wrongArgCount);
                $this->setExpectedException('Exception');
            } catch (Exception $e) {
                $this->assertInstanceOf('Exception', $e);
                $this->assertEquals('Lambda $hasValue must take two arguments!', $e->getMessage());
            }
        }

        // Lambda with wrong return type throws appropriate error
        $wrongReturnTypes = [
            function ($a, $b) {
            },
            function ($a, $b) {
                return '';
            },
            function ($a, $b) {
                return function () {
                };
            },
        ];
        foreach ($wrongReturnTypes as $wrongReturnType) {
            try {
                $this->injectStoredValues('(the test_property_1 of the testObj)', null, $wrongReturnType);
                $this->setExpectedException('Exception');
            } catch (Exception $e) {
                $this->assertInstanceOf('Exception', $e);
                $this->assertEquals('$hasValue lambda must return a boolean!', $e->getMessage());
            }
        }

        // Correct error is thrown when property does not exist
        try {
            $this->injectStoredValues(
                '(the test_property_1 of the testObj)',
                null,
                function ($a, $b) {
                    return false;
                }
            );
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('testObj does not have a test_property_1 property', $e->getMessage());
        }

        // Property is injected correctly when property exists
        $this->assertEquals(
            'overwritten',
            $this->injectStoredValues(
                '(the test_property_1 of the testObj)',
                null,
                function ($thing, $property) {
                    return isset($thing->$property);
                }
            )
        );

        /******************************
         * Formatted as
         *****************************/

        // DateTime is formatted with default format when no format is specified
        $this->assertEquals('2028-10-28T15:30:10+0000', $this->injectStoredValues('(the date_prop of the testObj)'));

        // DateTime is formatted with specified format
        $this->assertEquals(
            '10/28/2028',
            $this->injectStoredValues('(the date_prop of the testObj formatted as a US date)')
        );

        // DateTime is formatted as per host object format
        $testObj->dateFormat = 'm/d/Y H:i';
        $this->assertEquals(
            '10/28/2028 15:30',
            $this->injectStoredValues('(the date_prop of the testObj)')
        );

        // DateTime is formatted as specified format, even if host object has format
        $this->assertEquals(
            '10/28/2028 at 3:30 PM',
            $this->injectStoredValues('(the date_prop of the testObj formatted as a US date and 12hr time)')
        );
    }

    /**
     * Tests injectStoredValues using objects with magic properties.
     */
    public function testInjectStoredValuesMagicProperties()
    {
        $name = 'magicMock';
        $mock = $this->getMockBuilder('Tests\Behat\DefaultMocks\MagicMethods')
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
            ->willReturn('test_value_1');

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

    /**
     * Tests the parseKeyNested function.
     */
    public function testParseKeyNested()
    {
        $this->assertEquals(['Object', ['property']], $this->parseKeyNested("Object's property"));
        $this->assertEquals(['Object', ['ChildObject', 'property']],
            $this->parseKeyNested("Object's ChildObject's property"));
        $this->assertEquals(['Object', ['ChildObject', 'property']],
            $this->parseKeyNested('Object.ChildObject.property'));
        $this->assertEquals(['Object', ['ChildObject', 'property']],
            $this->parseKeyNested('Object.ChildObject.property'));
    }
}
