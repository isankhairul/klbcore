<?php namespace Klb\Core;

/**
 * This class allows you to convert from Roman numerals to natural numbers and vice versa.
 * I decided to do this as a fun challenge after reading http://thedailywtf.com/articles/Roman-Enumeration
 * Took me about 30 minutes to come up with, research and code the solution.
 * It can convert numbers up to 3,999,999 because I couldn't find any numerals for 5,000,000 above.
 * Due to my inability to get the correct accented characters 5000 above, I resulted to using the pipe (|) to represent accent.
 */
class Number
{
    /**
     * @var string[] A notation map to represent the common Roman numeral values.
     * @static
     */
    protected static $NOTATION =
        [
            '|', //one
            '[', //five
            ']', //ten
        ];

    /**
     * @var \ArrayObject[] A map of Roman numerals based on place value. Each item ends with the first numeral in the next place value.
     * @static
     */
    protected static $NUMERALS_BY_PLACE_VALUE =
        [
            ['I', 'V', 'X',], //ones
            ['X', 'L', 'C',], //tens
            ['C', 'D', 'M',], // hundreds
            ['M', 'V|', 'X|',], //thousands
            ['X|', 'L|', 'C|',], //tens of thousands
            ['C|', 'D|', 'M|',], //hundreds of thousands
            ['M|', '~', '~',], // millions. there are no values for the last two that I could find
        ];

    /**
     * @var string[]  sA map of numbers and their representative Roman numerals in notation format. This map allows us to make any numeral by replacing the the notation with the place value equivalent.
     * @static
     */
    protected static $NUMBER_TO_NOTATION =
        [
            '0' => '',
            '1' => '|',
            '2' => '||',
            '3' => '|||',
            '4' => '|[',
            '5' => '[',
            '6' => '[|',
            '7' => '[||',
            '8' => '[|||',
            '9' => '|]',
        ];

    /**
     * @var int[] A map of the major Roman numerals and the number equivalent.
     * @static
     */
    protected static $NUMERALS_TO_NUMBER =
        [
            'I' => 1,
            'V' => 5,
            'X' => 10,
            'L' => 50,
            'C' => 100,
            'D' => 500,
            'M' => 1000,
            'V|' => 5000,
            'X|' => 10000,
            'L|' => 50000,
            'C|' => 100000,
            'D|' => 500000,
            'M|' => 1000000,
        ];

    /**
     * Converts natural numbers to Roman numerals.
     *
     * @static
     * @param int|string $number a number or numeric string less than 3,999,999
     * @throws \InvalidArgumentException if the provided $number argument is not numeric or greater than 3,999,999.
     * @return string Roman numeral equivalent
     */
    public static function numberToRoman($number) {
        if(!is_numeric($number))
            throw new \InvalidArgumentException('Only numbers allowed');
        if($number > 3999999)
            throw new \InvalidArgumentException('Number cannot be greater than 3,999,999');

        //floats are not supported
        $number = (int) $number;

        $numerals = '';
        $number_string = strrev((string) $number);
        $length = strlen($number_string);

        for($i = 0; $i < $length; $i++) {
            $char = $number_string[$i];

            $num_map = self::$NUMERALS_BY_PLACE_VALUE[$i];
            $numerals = str_replace(self::$NOTATION, $num_map, self::$NUMBER_TO_NOTATION[$char]) . $numerals;
        }

        return $numerals;
    }

    /**
     * Converts Roman numerals to natural numbers.
     *
     * @static
     * @param string $numerals the Roman numerals to be converted
     * @throws \InvalidArgumentException if the provided $numerals argument contains invalid characters.
     * @return int the equivalent number
     */
    public static function romanToNumber($numerals) {
        $number = 0;
        $numeral_string = strrev((string) $numerals);
        $length = strlen($numeral_string);

        $prev_number = false;
        $is_accented = false;

        for($i = 0; $i < $length; $i++) {
            $char = $numeral_string[$i];

            if($char == '|') //check if it is an accent character
            {
                $is_accented = true;
                continue;//skip this iteration and process it in the next one as the accent applies to the next char
            }
            else if($is_accented)
            {
                $char .= '|';
                $is_accented = false;
            }

            //TODO Make a check using maybe regex at the beginning of the method.
            if(!isset(self::$NUMERALS_TO_NUMBER[$char]))
                throw new \InvalidArgumentException("Invalid character '{$char}' in numeral string");


            $num = self::$NUMERALS_TO_NUMBER[$char];

            //this is where the magic happens
            //if the previous number divided by 5 or 10 is equal to the current number, then we subtract eg. 9 = IX. I = 1, X = 10, 10/10 = 1
            if($prev_number)
            {
                if(($prev_number / 5) == $num || ($prev_number / 10) == $num)
                    $number -= $num;
                else
                    $number += $num;
            }
            else
                $number += $num;


            $prev_number = $num;
        }

        return $number;
    }
}
