<?php namespace Klb\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class Dpp
 *
 * @package Klb\Core\Filter
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
