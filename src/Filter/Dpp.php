<?php namespace KlbV2\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class Dpp
 *
 * @package KlbV2\Core\Filter
 */
class Dpp implements UserFilterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function filter( $value )
    {
        return $value / 1.1;
    }
}
