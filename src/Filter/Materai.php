<?php namespace Klb\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class Materai
 *
 * @package Klb\Core\Coa
 */
class Materai implements UserFilterInterface
{
    const MATERAI_ZERO = 0;
    const MATERAI_3000 = 3000;
    const MATERAI_6000 = 6000;

    /**
     * @param $value
     *
     * @return int
     */
    public function filter( $value )
    {
        if ( $value <= 250000 ) {
            return self::MATERAI_ZERO;
        }

        if ( $value > 250000 && $value <= 1000000 ) {
            return self::MATERAI_3000;
        }

        return self::MATERAI_6000;
    }
}
