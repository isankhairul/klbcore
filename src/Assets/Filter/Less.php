<?php namespace KlbV2\Core\Assets\Filter;

use Phalcon\Assets\FilterInterface;

/**
 * Class Less
 *
 * @package KlbV2\Core\Assets\Filter
 */
class Less implements FilterInterface
{
    /**
     * Filters the content returning a string with the filtered content
     *
     * @param string $content
     *
     * @return string
     */
    public function filter( $content )
    {
        return $content;
    }

}
