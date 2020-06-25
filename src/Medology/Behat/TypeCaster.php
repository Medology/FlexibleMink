<?php

namespace Medology\Behat;

/**
 * Adds type casting for step arguments.
 */
trait TypeCaster
{
    /**
     * Casts a step argument from a string to an int.
     *
     * Will cast the string to an int if the string is int like and within the max int range of the system.
     * Otherwise, the original string will be returned unmodified.
     *
     * @Transform /^((\-\d+)|\d+)$/
     *
     * @param string $string the string to cast
     *
     * @return int|string the string cast to an int, or the original string if it is outside the max int range of the system
     */
    public function castStringToInt($string)
    {
        $intval = intval($string);

        return strval($intval) === $string ? $intval : $string;
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
        /** @todo Add PHP_FLOAT_MAX check when we move all our projects to at least php 7.2 */
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
     * accepts values that look like other scalar types, such as int or
     * bool.
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
