<?php

namespace Klb\Core\Assets;

use Phalcon\Assets\FilterInterface;

/**
 * Adds a license message to the top of the file
 *
 * @param string $contents
 *
 * @return string
 */
class LicenseStamper implements FilterInterface
{
    /**
     * Do the filtering
     *
     * @param string $contents
     *
     * @return string
     */
    public function filter( $contents )
    {
        $license = "/* (c) " . date( 'Y-m-d H:i:s' ) . " Kalbe Platform */";

        return $license . PHP_EOL . PHP_EOL . $contents;
    }
}
