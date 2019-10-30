<?php namespace Klb\Core\Solr;

use Phalcon\DiInterface;

/**
 * Interface ConnectionContract
 *
 * @package Klb\Core\Solr
 */
interface ConnectionContract
{

    /**
     * Connection constructor.
     *
     * @param DiInterface $di
     */
    public function __construct( DiInterface $di = null );

    /**
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $headers
     *
     * @return $this
     * @throws SolrException
     */
    public function call( $url, $parameters = null, $method = 'GET', array $headers = [], $noDefaultParams = false );

    /**
     * @param        $collectionName
     * @param        $url
     * @param null   $parameters
     * @param string $method
     * @param array  $headers
     * @param bool   $noDefaultParams
     *
     * @return mixed
     * @throws SolrException
     */
    public function collection( $collectionName, $url, $parameters = null, $method = 'GET', array $headers = [], $noDefaultParams = false );

    /**
     * @param array $params
     *
     * @return $this
     */
    public function select( array $params );

    /**
     * @return array
     */
    public function toArray();

    /**
     * @return mixed
     */
    public function toJson();

    /**
     * @return bool
     */
    public function isSuccess();

    /**
     * @return mixed
     */
    public function getCurrentUrl();
}
