<?php namespace KlbV2\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class Ppn
 *
 * @package KlbV2\Core\Filter
 */
class Ppn implements UserFilterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function filter( $value )
    {
        return $value / 100 * 10;
    }
}
