<?php namespace Klb\Core\Assets\Filter;

use MatthiasMullie\Minify\CSS;
use Phalcon\Assets\FilterInterface;

/**
 * Class CssMin
 *
 * @package Klb\Core\Assets\Filter
 */
class CssMin implements FilterInterface
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
        $minifier = new CSS();
        $minifier->add( $content );
        return $minifier->minify();
    }
}
