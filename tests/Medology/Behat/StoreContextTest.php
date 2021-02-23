<?php

/**
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

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
     * Returns a list of non-callable values.
     *
     * @return array
     */
    public function nonCallableValuesProvider()
    {
        return [[''], [0],  [$this->getMockObject()]];
    }

    /**
     * Provides examples of strings that have injection-like syntax, but are not really injectable.
     *
     * @return string[][]
     */
    public function injectLikeSyntaxDataProvider()
    {
        return [
            ['the total_cost of the Order'],
            ['(the total_cost of the Order'],
            ['the total_cost of the Order)'],
            ['the (total_cost of the Order)'],
            ['(the total_cost of Order)'],
        ];
    }

    /**
     * @dataProvider injectLikeSyntaxDataProvider
     *
     * @param string $string the value to pass to injectStoredValues
     */
    public function testInjectionLikeSyntaxIsNotInjected($string)
    {
        $this->assertEquals($string, $this->storeContext->injectStoredValues($string));
    }

    /**
     * Provides examples of strings that reference things not in the store, and the expected error message.
     *
     * @return string[][]
     */
    public function nonExistentItemDataProvider()
    {
        return [
            ['(the test_property_1 of the FakeObj)', "Entry 'FakeObj' was not found in the store."],
        ];
    }

    /**
     * @dataProvider nonExistentItemDataProvider
     *
     * @param string $string the value to pass to injectStoredValues
     * @param string $error  the expected Exception error message
     */
    public function testInjectNonExistentItem($string, $error)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($error);

        $this->storeContext->injectStoredValues($string);
    }

    public function onGetFnWrongArgsDataProvider()
    {
        return [
            'no args'       => [function () {}],
            'too many args' => [function (/* @scrutinizer ignore-unused */ $a, /* @scrutinizer ignore-unused */ $b) {}],
        ];
    }

    /**
     * @dataProvider onGetFnWrongArgsDataProvider
     *
     * @param $onGetFn
     */
    public function testOnGetFnMustTakeOneArgument($onGetFn)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Method $onGetFn must take one argument!');

        $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', $onGetFn);
    }

    /**
     * Provides examples of onGetFn that return the wrong data type.
     *
     * @return callable[][]
     */
    public function onGetFnWrongReturnTypeDataProvider()
    {
        return [
            'no return'     => [function (/* @scrutinizer ignore-unused */ $a) {}],
            'return string' => [function ($a) {
                return gettype($a);
            }],
            'return function' => [function ($a) {
                return function () use ($a) {
                    gettype($a);
                };
            }],
        ];
    }

    /**
     * @dataProvider onGetFnWrongReturnTypeDataProvider
     */
    public function testOnGetFnWrongReturnType(callable $onGetFn)
    {
        $this->storeContext->set('person', $this->getMockObject());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The $onGetFn method must return an array or a non-callable object!');

        $this->storeContext->injectStoredValues('(the name of the person)', $onGetFn);
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

        // test reflection of non-matching inputs
        $this->assertEquals(1452, $this->storeContext->injectStoredValues(1452));
        $this->assertEquals('lol', $this->storeContext->injectStoredValues('lol'));

        // test bad property
        $badProperty = 'bad_property_1';

        try {
            $this->storeContext->injectStoredValues("(the $badProperty of the $name)");
            $this->expectException('Exception');
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
            $this->expectException('Exception');
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
                gettype($a);
            },
            function ($a, $b, $c) {
                gettype($a);
                gettype($b);
                gettype($c);
            },
        ];
        foreach ($wrongArgCounts as $wrongArgCount) {
            try {
                $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', null, $wrongArgCount);
                $this->expectException('Exception');
            } catch (Exception $e) {
                $this->assertInstanceOf('Exception', $e);
                $this->assertEquals('Lambda $hasValue must take two arguments!', $e->getMessage());
            }
        }

        // Lambda with wrong return type throws appropriate error
        $wrongReturnTypes = [
            function (/* @scrutinizer ignore-unused */ $a, /* @scrutinizer ignore-unused */ $b) {
            },
            function ($a, $b) {
                return gettype($a) . gettype($b);
            },
            function ($a, $b) {
                return function () use ($a, $b) {
                    return gettype($a) . gettype($b);
                };
            },
        ];
        foreach ($wrongReturnTypes as $wrongReturnType) {
            try {
                $this->storeContext->injectStoredValues('(the test_property_1 of the testObj)', null, $wrongReturnType);
                $this->expectException('Exception');
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
            $this->expectException('Exception');
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

        // DateTime is formatted as per host object format
        $testObj->dateFormat = 'm/d/Y H:i';
        $this->assertEquals(
            '10/28/2028 15:30',
            $this->storeContext->injectStoredValues('(the date_prop of the testObj)')
        );
    }

    public function dateTimeFormatDataProvider()
    {
        return [
            'DateTime is formatted with default format when no format is specified' => [
                '(the date_prop of the testObj)',
                '2028-10-28T15:30:10+0000',
            ],
            'DateTime is formatted with specified format' => [
                '(the date_prop of the testObj formatted as a US date)',
                '10/28/2028',
            ],
            'DateTime is formatted as specified format, even if host object has format' => [
                '(the date_prop of the testObj formatted as a US date and 12hr time)',
                '10/28/2028 at 3:30 PM',
            ],
        ];
    }

    /**
     * @dataProvider dateTimeFormatDataProvider
     *
     * @param $input
     * @param $output
     */
    public function testDateTimeFormatting($input, $output)
    {
        $this->storeContext->set('testObj', $this->getMockObject());
        $this->assertEquals($output, $this->storeContext->injectStoredValues($input));
    }

    public function phoneNumberFormatDataProvider()
    {
        return [
            'Phone is formatted with default format when no format is specified' => [
                '(the phone_prop of the testObj)',
                '4414031737',
            ],
            'Phone is formatted with specified US format' => [
                '(the phone_prop of the testObj formatted as a US phone number)',
                '(441) 403-1737',
            ],
            'Phone is formatted as specified ###-###-#### format' => [
                '(the phone_prop of the testObj formatted as a ###-###-### phone number)',
                '441-403-1737',
            ],
        ];
    }

    /**
     * @dataProvider phoneNumberFormatDataProvider
     *
     * @param $input
     * @param $output
     */
    public function testPhoneNumberFormatting($input, $output)
    {
        $this->storeContext->set('testObj', $this->getMockObject());
        $this->assertEquals($output, $this->storeContext->injectStoredValues($input));
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

    /**
     * Expects the correct type error exception depending on the php version.
     *
     * @throws Exception when a unsupported version of PHP is being used
     */
    protected function expectTypeErrorException()
    {
        list($majorVersion, $minorVersion) = explode('.', PHP_VERSION, 3);

        if ($majorVersion >= 7) {
            $this->expectException(TypeError::class);
        } elseif ($majorVersion == 5 && $minorVersion == 6) {
            $this->expectException(PHPUnit_Framework_Error::class);
        } else {
            throw new Exception('This php version is not supported. PHP version must be >= 5.6');
        }
    }

    /**
     * Asserts that a function throws a type error that contains a string.
     *
     * @param callable $fn              a closure expected to throw the exception
     * @param string   $expectedMessage the message expected to be found in the exception message
     *
     * @throws Exception when a unsupported version of PHP is being used
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
     * Creates a simple mock object.
     *
     * @return stdClass A mock object with properties test_property_1/2/3
     */
    private function getMockObject()
    {
        return (object) [
            'test_property_1' => 'test_value_1',
            'test_property_2' => 'test_value_2',
            'test_property_3' => 'test_value_3',
            'date_prop'       => new DateTime('2028-10-28 15:30:10'),
            'phone_prop'      => '4414031737',
        ];
    }
}
