<?php namespace KlbV2\Core\Filter;

use Phalcon\Filter\UserFilterInterface;

/**
 * Class Telephone
 *
 * @package KlbV2\Core\Filter
 */
class Telephone implements UserFilterInterface
{
    /**
     * @param $value
     *
     * @return string
     */
    public function filter( $value )
    {
        $aValue = ltrim( $value, '+' );
        if ( !is_numeric( $aValue ) ) {
            return $aValue;
        }
        $aValue = preg_replace( '/\D/', '', $aValue );
        if ( $aValue[0] === '0' ) {
            $aValue = ltrim( $aValue, '0' );
            if ( substr( $aValue, 0, 2 ) !== '62' ) $aValue = '62' . $aValue;
        }

        return $aValue;
    }
}
