<?php

namespace Tests\Medology\Behat;

use DateTime;
use Exception;
use Medology\Behat\StoreContext;
use PHPUnit_Framework_Error;
use PHPUnit_Framework_TestCase;
use stdClass;
use TypeError;

class StoreContextTest extends PHPUnit_Framework_TestCase
{
    /** @var StoreContext */
    protected $storeContext;

    /**
     * Sets up the environment before each test.
     */
    public function setUp()
    {
        $this->storeContext = new StoreContext();
    }

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
        list($majorVersion, $minorVersion, $releaseVersion) = explode('.', PHP_VERSION, 4);
        $releaseVersion = explode('-', $releaseVersion, 2)[0];

        if ($majorVersion >= 7) {
            $this->setExpectedException('TypeError');
        } elseif ($majorVersion == 5 && $minorVersion == 6 && $releaseVersion >= 40) {
            $this->setExpectedException('PHPUnit_Framework_Error');
        } else {
            throw new Exception('This php version is not supported. PHP version must be >= 5.6.40');
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
            $this->storeContext->injectStoredValues('', '');
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
            $this->storeContext->injectStoredValues('', 0);
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
            $this->storeContext->injectStoredValues('', $this->getMockObject());
        }, 'injectStoredValues() must be callable');
    }

    /**
     * Test that a non-callable has value throws appropriate error.
     *
     * @throws Exception When a unsupported version of PHP is being used.
     */
    public function testNonCallableHasValueThrowsAppropriateError()
    {
        foreach (['', 0, $this->getMockObject()] as $nonCallable) {
            $this->assertFunctionThrowsTypeErrorThatContainsMessage(function () use ($nonCallable) {
                $this->storeContext->injectStoredValues('', null, $nonCallable);
            }, 'injectStoredValues() must be callable');
        }
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
        $this->storeContext->set($name, $testObj);

        /***********************
         * Validate First Argument
         ***********************/

        // test empty string and variations
        $this->assertEmpty($this->storeContext->injectStoredValues(''));
        $this->assertEmpty($this->storeContext->injectStoredValues(null));

        // test invalid argument for $string
        try {
            /* @noinspection PhpParamsInspection intentional wrong argument type */
            $this->storeContext->injectStoredValues([]);
            $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        } catch (Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning', $e);
        }

        try {
            $this->storeContext->injectStoredValues(function () {
            });
            $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        } catch (Exception $e) {
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning', $e);
        }

        // test reflection of non-matching inputs
        $this->assertEquals(1452, $this->storeContext->injectStoredValues(1452));
        $this->assertEquals('lol', $this->storeContext->injectStoredValues('lol'));
        $this->assertEquals('the total_cost of the Order', $this->storeContext->injectStoredValues('the total_cost of the Order'));
        $this->assertEquals('(the total_cost of the Order', $this->storeContext->injectStoredValues('(the total_cost of the Order'));
        $this->assertEquals('the total_cost of the Order)', $this->storeContext->injectStoredValues('the total_cost of the Order)'));
        $this->assertEquals(
            'the (total_cost of the Order)',
            $this->storeContext->injectStoredValues('the (total_cost of the Order)')
        );
        $this->assertEquals('(the total_cost of Order)', $this->storeContext->injectStoredValues('(the total_cost of Order)'));

        // test non-existing store key
        $badName = 'FakeObj';

        try {
            $this->storeContext->injectStoredValues("(the test_property_1 of the $badName)");
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals("Entry '$badName' was not found in the store.", $e->getMessage());
        }

        // test bad property
        $badProperty = 'bad_property_1';

        try {
            $this->storeContext->injectStoredValues("(the $badProperty of the $name)");
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals("$name does not have a $badProperty property", $e->getMessage());
        }

        // test valid property and key
        $this->assertEquals(
            $testObj->test_property_1,
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)')
        );

        /***********************
         * Validate Second Argument
         ***********************/

        // test null values
        $this->assertEmpty($this->storeContext->injectStoredValues('', null));

        // test function with bad arguments
        $badFn = function () {
        };

        try {
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('TypeError');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('Method $onGetFn must take one argument!', $e->getMessage());
        }

        $badFn = function ($a, $b) {
        };

        try {
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('Method $onGetFn must take one argument!', $e->getMessage());
        }

        // test function with no return
        $badFn = function (/* @noinspection PhpUnusedParameterInspection */ $a) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $a = 1;
        };

        try {
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        // test function with bad return
        $badFn = function (/* @noinspection PhpUnusedParameterInspection */ $a) {
            return 'bad return';
        };

        try {
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
            $this->setExpectedException('Exception');
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals('The $onGetFn method must return an object or an array!', $e->getMessage());
        }

        $badFn = function (/* @noinspection PhpUnusedParameterInspection */ $a) {
            return function () {
            };
        };

        try {
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $badFn);
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
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $goodFn)
        );

        // test accessing property after un-setting with callback
        $goodFn = function ($thing) {
            unset($thing->test_property_1);

            return $thing;
        };

        try {
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $goodFn);
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
            $this->storeContext->injectStoredValues('(the test_property_4 of the testObj)', $goodFn)
        );

        // test overwriting property
        $goodFn = function ($thing) {
            $thing->test_property_1 = 'overwritten';

            return $thing;
        };
        $this->assertEquals(
            'overwritten',
            $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $goodFn)
        );

        /******************************
         * Validate $hasValue argument
         *****************************/

        // Null $hasValue should default to using isset
        $this->assertEmpty($this->storeContext->injectStoredValues('', null, null));

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
                $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', null, $wrongArgCount);
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
            function (/* @noinspection PhpUnusedParameterInspection */ $a, $b) {
                return '';
            },
            function (/* @noinspection PhpUnusedParameterInspection */ $a, $b) {
                return function () {
                };
            },
        ];
        foreach ($wrongReturnTypes as $wrongReturnType) {
            try {
                $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', null, $wrongReturnType);
                $this->setExpectedException('Exception');
            } catch (Exception $e) {
                $this->assertInstanceOf('Exception', $e);
                $this->assertEquals('$hasValue lambda must return a boolean!', $e->getMessage());
            }
        }

        // Correct error is thrown when property does not exist
        try {
            $this->storeContext->injectStoredValues(
                '(the test_property_1 of the testObj)',
                null,
                function (/* @noinspection PhpUnusedParameterInspection */ $a, $b) {
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
            $this->storeContext->injectStoredValues(
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
        $this->assertEquals(
            '2028-10-28T15:30:10+0000',
            $this->storeContext->injectStoredValues('(the date_prop of the testObj)')
        );

        // DateTime is formatted with specified format
        $this->assertEquals(
            '10/28/2028',
            $this->storeContext->injectStoredValues('(the date_prop of the testObj formatted as a US date)')
        );

        // DateTime is formatted as per host object format
        $testObj->dateFormat = 'm/d/Y H:i';
        $this->assertEquals(
            '10/28/2028 15:30',
            $this->storeContext->injectStoredValues('(the date_prop of the testObj)')
        );

        // DateTime is formatted as specified format, even if host object has format
        $this->assertEquals(
            '10/28/2028 at 3:30 PM',
            $this->storeContext->injectStoredValues(
                '(the date_prop of the testObj formatted as a US date and 12hr time)'
            )
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

        $this->storeContext->set($name, $mock);

        $this->assertEquals('test_value_1', $this->storeContext->injectStoredValues("(the test_property_1 of the $name)"));
    }
}
