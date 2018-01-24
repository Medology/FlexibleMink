<?php

namespace Tests\Medology\Behat;

use Exception;
use Medology\Behat\StoreContext;
use PHPUnit_Framework_Error;
use PHPUnit_Framework_TestCase;
use stdClass;

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
            $this->assertEquals("Did not find $badName in the store", $e->getMessage());
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

        // test invalid values
        try {
            $this->storeContext->injectStoredValues('', '');
            $this->setExpectedException('TypeError');
        } catch (PHPUnit_Framework_Error $e) {
            $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
        }

        try {
            $this->storeContext->injectStoredValues('', 0);
            $this->setExpectedException('TypeError');
        } catch (PHPUnit_Framework_Error $e) {
            $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
        }

        try {
            $this->storeContext->injectStoredValues('', $testObj);
            $this->setExpectedException('TypeError');
        } catch (PHPUnit_Framework_Error $e) {
            $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
        }

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

        // Non-callable $hasValue throws appropriate error
        foreach (['', 0, $testObj] as $nonCallable) {
            try {
                $this->storeContext->injectStoredValues('', null, $nonCallable);
                $this->setExpectedException('TypeError');
            } catch (PHPUnit_Framework_Error $e) {
                $this->assertNotEquals(-1, strpos($e->getMessage(), 'injectStoredValues() must be callable'));
            }
        }

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
