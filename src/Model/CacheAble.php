<?php namespace Klb\Core\Model;
/**
 * Class CacheAble
 *
 * @package Klb\Core\Model
 */
trait CacheAble
{
    /**
     * @param null $parameters
     *
     * @return mixed
     */
    public static function find( $parameters = null )
    {
        // Convert the parameters to an array
        if ( !is_array( $parameters ) ) {
            $parameters = [ $parameters ];
        }

        // Check if a cache key wasn't passed
        // and create the cache parameters
        if ( !isset( $parameters["cache"] ) ) {
            $parameters["cache"] = [
                "key"      => crc32( __CLASS__ . '_find_' . self::_createKey( $parameters ) ),
                "lifetime" => 300,
            ];
        }

        return parent::find( $parameters );
    }

    /**
     * Implement a method that returns a string key based
     * on the query parameters
     */
    protected static function _createKey( $parameters )
    {
        $uniqueKey = [];

        foreach ( $parameters as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $uniqueKey[] = $key . ":" . $value;
            } else if ( is_array( $value ) ) {
                $uniqueKey[] = $key . ":[" . self::_createKey( $value ) . "]";
            }
        }

        return join( ",", $uniqueKey );
    }

    /**
     * @param null $parameters
     *
     * @return mixed
     */
    public static function findFirst( $parameters = null )
    {
        // Convert the parameters to an array
        if ( !is_array( $parameters ) ) {
            $parameters = [ $parameters ];
        }

        // Check if a cache key wasn't passed
        // and create the cache parameters
        if ( !isset( $parameters["cache"] ) ) {
            $parameters["cache"] = [
                "key"      => crc32( __CLASS__ . '_findFirst_' . self::_createKey( $parameters ) ),
                "lifetime" => 300,
            ];
        }

        return parent::findFirst( $parameters );
    }
}
