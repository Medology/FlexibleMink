<?php

namespace Behat\FlexibleMink\Context;

/**
 * Adds type casting for step arguments.
 */
trait TypeCaster
{
    /**
     * Casts a step argument from a string to an int.
     *
     * @Transform /^(0|[1-9]\d*)$/
     *
     * @param string $string the string to cast
     *
     * @return int the resulting int
     */
    public function castStringToInt($string)
    {
        return intval($string);
    }

    /**
     * Casts a step argument from a string to a float.
     *
     * @Transform /^\d*\.\d+$/
     *
     * @param string $string the string to cast
     *
     * @return float the resulting float
     */
    public function castStringToFloat($string)
    {
        return floatval($string);
    }

    /**
     * Casts a step argument from string to a bool.
     *
     * Supports true and false only. e.g. will not cast 0 or 1.
     *
     * @Transform /^(true|false)$/i
     *
     * @param string $string the string to cast
     *
     * @return bool the resulting bool
     */
    public function castStringToBool($string)
    {
        return strtolower($string) === 'true';
    }

    /**
     * Casts a Quoted string to a string.
     *
     * This is helpful for when you want to write a step definition that
     * accepts values that look like other scalar types, such as ints or
     * bools.
     *
     * For example, if you wrote your step definition as follows:
     *
     *      Given /^the value is "(?P<value>[^"]*)"$/
     *
     * And you have the following step:
     *
     *      Given the value is "1"
     *
     * Then the castStringToInt() method will kick in and cast this to
     * an integer.
     *
     * However, if you were to modify your step definition as follows:
     *
     *      Given /^the value is (?P<value>.*)$/
     *
     * Then the quotes (if you choose to use them) become part of the
     * $value argument, and therefore "1" would no longer match the
     * pattern for castStringToInt(), and would instead match this
     * methods pattern and be casted to a string *without* the quotes.
     *
     * @Transform /^('([^']|\\')*'|"([^"]|\\")*")$/
     *
     * @param string $string the string to cast
     *
     * @return string the resulting bool
     */
    public function castQuotedStringToString($string)
    {
        return substr($string, 1, -1);
    }
}
