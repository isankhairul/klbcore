<?php namespace KlbV2\Core\Ftp;

use Exception;
use InvalidArgumentException;
use Phalcon\DiInterface;

class FtpManager
{

    /**
     * The application instance.
     *
     * @var DiInterface
     */
    protected $app;
    protected $config;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     *
     * FtpManager constructor.
     *
     * @param DiInterface $app
     * @param null        $connectionName
     *
     * @throws Exception
     */
    public function __construct( DiInterface $app, $connectionName = null )
    {
        $this->app = $app;
        $this->config = require APPLICATION_PATH . '/config/ftp.php';
        if ( null !== $connectionName ) {
            $this->connection( $connectionName );
        }
    }

    /**
     * Get a FTP connection instance.
     *
     * @param string $name
     *
     * @return Ftp
     * @throws Exception
     */
    public function connection( $name = null )
    {
        $name = $name ?: $this->getDefaultConnection();

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application.
        if ( !isset( $this->connections[$name] ) ) {
            $this->connections[$name] = $this->makeConnection( $name );
        }

        return $this->connections[$name];
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->config['default'];
    }

    /**
     * Make the FTP connection instance.
     *
     * @param $name
     *
     * @return Ftp
     * @throws Exception
     */
    protected function makeConnection( $name )
    {
        $config = $this->getConfig( $name );

        return new Ftp( $config );
    }

    /**
     * Get the configuration for a connection.
     *
     * @param string $name
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function getConfig( $name )
    {
        $name = $name ?: $this->getDefaultConnection();

        // To get the ftp connection configuration, we will just pull each of the
        // connection configurations and get the configurations for the given name.
        // If the configuration doesn't exist, we'll throw an exception and bail.
        $connections = $this->config['connections'];
        $config = isset( $connections[$name] ) ? $connections[$name] : null;
        if ( is_null( $config ) ) {
            throw new InvalidArgumentException( "Ftp [$name] not configured." );
        }

        return $config;
    }

    /**
     * Reconnect to the given ftp.
     *
     * @param string $name
     *
     * @return Ftp
     * @throws Exception
     */
    public function reconnect( $name = null )
    {
        $name = $name ?: $this->getDefaultConnection();

        $this->disconnect( $name );

        return $this->connection( $name );
    }

    /**
     * Disconnect from the given ftp.
     *
     * @param string $name
     *
     * @return void
     */
    public function disconnect( $name = null )
    {
        $name = $name ?: $this->getDefaultConnection();

        if ( $this->connections[$name] ) {
            $this->connections[$name]->disconnect();
            unset( $this->connections[$name] );
        }
    }

    /**
     * Return all of the created connections.
     *
     * @return Ftp[]
     */
    public function getConnections()
    {
        return $this->connections;
    }
}
