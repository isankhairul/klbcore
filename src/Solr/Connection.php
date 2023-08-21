<?php namespace Klb\Core\Solr;


use InvalidArgumentException;
use Phalcon\Di\DiInterface;

/**
 * Class Connection
 *
 * @package Klb\Core\Solr
 */
class Connection implements ConnectionContract
{
    /**
     * @var DiInterface
     */
    private $di;
    /**
     * @var
     */
    private $currentUrl;
    /**
     * @var string
     */
    private $_responseCall;
    /**
     * @var array
     */
    private $_defaultParameters = [
        'q'      => '*:*',
        'indent' => 'true',
        'wt'     => 'json',
    ];

    /**
     * Connection constructor.
     *
     * @param DiInterface $di
     */
    public function __construct( DiInterface $di = null )
    {
        if ( null === $di ) {
            $di = di();
        }
        $this->di = $di;
    }

    /**
     * @inheritDoc
     */
    public function collection( $collectionName, $url, $parameters = null, $method = 'GET', array $headers = [], $noDefaultParams = false )
    {
        $map = $this->di->get( 'config' )->solr->collections;

        if ( !array_key_exists( $collectionName, $map ) ) {
            throw new InvalidArgumentException( 'Invalid collection name on mapping: ' . $collectionName );
        }
        $url = $map[$collectionName] . '/' . $url;
        return $this->call( $url, $parameters, $method, $headers, $noDefaultParams );
    }

    /**
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $headers
     *
     * @return $this
     * @throws SolrException
     */
    public function call( $url, $parameters = null, $method = 'GET', array $headers = [], $noDefaultParams = false )
    {

        $header = array(
            "Content-Type: application/json",
        );

        if ( strpos( $url, '?' ) !== false ) {
            $separator = '&';
        } else {
            $separator = '?';
        }

        if ( is_array( $parameters ) || is_object( $parameters ) ) {
            $parameters = http_build_query( $parameters, null, '&' );
            $parameters = preg_replace( '/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $parameters );
        }

        $solrConfig = $this->di->get( 'config' )->solr;

        $url = $solrConfig->uri . ltrim( $url, '/' );


        if ( $noDefaultParams === false ) {
            $url .= $separator . http_build_query( $this->_defaultParameters, null, '&' );
        }

        $ch = curl_init();
        if ( !empty( $headers ) ) {
            $header = $headers;
        }
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        if ( $method === 'POST' ) {
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $parameters );
        } else {
            $url .= '&' . $parameters;
        }
        $this->currentUrl = $url;
        $this->di->get( 'logger' )->debug( "SOLR-REQUEST: " . $url . "\tPARAMS: " . $parameters . "\tAUTH: " . json_encode( $solrConfig->auth ) );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_VERBOSE, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        if ( !empty( $solrConfig->auth ) ) {
            $value = null;
            if ( is_string( $solrConfig->auth ) ) {
                $value = $solrConfig->auth;
            } else if ( !empty( $solrConfig->auth->read ) && !empty( $solrConfig->auth->write ) ) {
                if ( strpos( $url, 'select?' ) !== false ) {
                    $value = $solrConfig->auth->read;
                } else {
                    $value = $solrConfig->auth->write;
                }
            }
            if ( null !== $value ) {
                curl_setopt( $ch, CURLOPT_USERPWD, $value );
            }
        }

        $this->_responseCall = curl_exec( $ch );
        // Check for errors and display the error message
        $errorNo = curl_errno( $ch );
        curl_close( $ch );
        if ( $errorNo ) {
            $errorNo = null;
            $errorMsg = curl_strerror( $errorNo );
            $this->di->get( 'logger' )->error( "SOLR-RESPONSE-EXCEPTION\tERRNO: {$errorNo}\tMESSAGE: {$errorMsg}" );
            throw new SolrException( $errorMsg, $errorNo );
        }
        $this->di->get( 'logger' )->debug( "SOLR-RESPONSE: " . preg_replace( "/[\n\r\s+]/", "", $this->_responseCall ) );
        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function select( array $params )
    {
        return $this->call( 'select', $params );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $decode = json_decode( $this->_responseCall, true );
        if ( empty( $decode['response'] ) ) {
            return [];
        }
        return $decode['response'];
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        $decode = json_decode( $this->_responseCall, true );
        if ( isset( $decode['responseHeader']['status'] ) ) {
            if ( $decode['responseHeader']['status'] == '0' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function toJson()
    {
        return $this->_responseCall;
    }

    /**
     * @return mixed
     */
    public function getCurrentUrl()
    {
        return $this->currentUrl;
    }
}
