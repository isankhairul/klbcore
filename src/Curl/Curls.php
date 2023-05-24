<?php

namespace KlbV2\Core\Curl;

use Exception;

class Curls
{
    public function callWebservice( $url, $username, $password )
    {
        $headers = [
            'Content-Type:application/json',
        ];
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_PORT, 8473 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $password );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString); // the SOAP request
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        // converting
        $response = curl_exec( $ch );
        curl_close( $ch );

        return $response;
    }

    public function callApiSomd( $url, $username, $password )
    {
        $headers = [
            'Content-Type:application/json',
        ];
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $password );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString); // the SOAP request
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        // converting
        $response = curl_exec( $ch );
        curl_close( $ch );

        return $response;
    }

    public function callConnector( $url, $username, $password, $jsonPostParams )
    {
        $headers = [
            'Content-Type:application/json',
        ];
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $password );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonPostParams ); // the SOAP request
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        // converting
        $response = curl_exec( $ch );
        $err = curl_error( $ch );
        if ( $err ) {
            throw new Exception( $err );
        }
        curl_close( $ch );

        return $response;
    }

    public function callApi( $url, $jsonPostParams )
    {
        $headers = [
            'Content-Type:application/json',
        ];
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $jsonPostParams ); // the SOAP request
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        // converting
        $response = curl_exec( $ch );
        $err = curl_error( $ch );
        if ( $err ) {
            throw new Exception( $err );
        }
        curl_close( $ch );

        return $response;
    }

    public function callApiKd( $url, $apiAuthKey, $postData )
    {
        $headers = [
            'Content-Type:application/json',
            'X-API-AUTH:' . $apiAuthKey
        ];
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        $response = curl_exec( $ch );
        curl_close( $ch );

        return $response;
    }

    public function callApiGet( $url )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
        curl_setopt( $ch, CURLOPT_ENCODING, "" );

        // converting
        $response = curl_exec( $ch );
        $err = curl_error( $ch );
        if ( $err ) {
            throw new Exception( $err );
        }
        curl_close( $ch );

        return $response;
    }

    public function callConnectorPost( $url, $username, $password, $jsonPostParams )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_USERPWD, $username . ":" . $password );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $jsonPostParams ) ); // the SOAP request

        // converting
        $response = curl_exec( $ch );
        $err = curl_error( $ch );
        if ( $err ) {
            throw new Exception( $err );
        }
        curl_close( $ch );

        return $response;
    }
}
