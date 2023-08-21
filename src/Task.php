<?php namespace Klb\Core;

use Danzabar\CLI\Input\InputOption;
use Danzabar\CLI\Tasks\Helpers\Confirmation;
use Danzabar\CLI\Tools\ParamBag;
use Klb\Core\Solr\ConnectionContract;
use Phalcon\Annotations\Extended\Adapter\Memory as Annotation;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Queue\Beanstalk;

/**
 * Class Task
 *
 * @package App
 * @property ParamBag      $argument
 * @property InputOption   $option
 * @method DiInterface getDI()
 * @property Beanstalk          $queue
 * @property ConnectionContract $solr
 */
class Task extends \Danzabar\CLI\Tasks\Task
{
    /**
     * @var array
     */
    private $foregroundColors = [];
    /**
     * @var array
     */
    private $backgroundColors = [];

    // Time the progress bar was initialised in seconds (with millisecond precision)
    private $startTime;

    private $elapsed;
    /**
     * @var string
     */
    private $prefixLog = '';

    public function initialize()
    {
        // Set up shell colors
        $this->foregroundColors['black'] = '0;30';
        $this->foregroundColors['dark_gray'] = '1;30';
        $this->foregroundColors['blue'] = '0;34';
        $this->foregroundColors['light_blue'] = '1;34';
        $this->foregroundColors['green'] = '0;32';
        $this->foregroundColors['light_green'] = '1;32';
        $this->foregroundColors['cyan'] = '0;36';
        $this->foregroundColors['light_cyan'] = '1;36';
        $this->foregroundColors['red'] = '0;31';
        $this->foregroundColors['light_red'] = '1;31';
        $this->foregroundColors['purple'] = '0;35';
        $this->foregroundColors['light_purple'] = '1;35';
        $this->foregroundColors['brown'] = '0;33';
        $this->foregroundColors['yellow'] = '1;33';
        $this->foregroundColors['light_gray'] = '0;37';
        $this->foregroundColors['white'] = '1;37';

        $this->backgroundColors['black'] = '40';
        $this->backgroundColors['red'] = '41';
        $this->backgroundColors['green'] = '42';
        $this->backgroundColors['yellow'] = '43';
        $this->backgroundColors['blue'] = '44';
        $this->backgroundColors['magenta'] = '45';
        $this->backgroundColors['cyan'] = '46';
        $this->backgroundColors['light_gray'] = '47';
    }

    // Returns all foreground color names

    /**
     * @param      $string
     * @param null $foregroundColor
     * @param null $backgroundColor
     *
     * @return string
     */
    public function getColoredString( $string, $foregroundColor = null, $backgroundColor = null )
    {
        $coloredString = "";

        // Check if given foreground color found
        if ( isset( $this->foregroundColors[$foregroundColor] ) ) {
            $coloredString .= "\033[" . $this->foregroundColors[$foregroundColor] . "m";
        }
        // Check if given background color found
        if ( isset( $this->backgroundColors[$backgroundColor] ) ) {
            $coloredString .= "\033[" . $this->backgroundColors[$backgroundColor] . "m";
        }

        // Add string and end coloring
        $coloredString .= $string . "\033[0m";

        return $coloredString;
    }

    // Returns all background color names

    public function getForegroundColors()
    {
        return array_keys( $this->foregroundColors );
    }

    public function getBackgroundColors()
    {
        return array_keys( $this->backgroundColors );
    }

    /**
     * @param $message
     */
    public function comment( $message )
    {
        $this->_out( $message );
    }

    /**
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * @param        $message
     * @param string $type
     */
    protected function _out( $message, $type = 'Comment' )
    {
        $this->output->writeln( "<$type>" . $this->getPrefixLog() . $message . "</$type>" );
    }

    /**
     * @return string
     */
    public function getPrefixLog()
    {
        return $this->prefixLog;
    }

    /**
     * @param $prefixLog
     *
     * @return $this
     */
    protected function setPrefixLog( $prefixLog )
    {
        $this->prefixLog = $prefixLog;
        if ( !empty( $this->prefixLog ) ) {
            $this->prefixLog .= "\t";
        }

        return $this;
    }

    /**
     * @param $message
     */
    public function error( $message )
    {
        $this->_out( $message, 'Error' );
    }

    /**
     * @param $message
     */
    public function question( $message )
    {
        $this->_out( $message, 'Question' );
    }

    /**
     * @param        $message
     * @param string $type
     */
    public function logger( $message, $type = 'debug' )
    {
        $this->getOutput()->writeln( $this->getPrefixLog() . $message );
        $this->getLog()->$type( $this->getPrefixLog() . $message );
    }

    /**
     * @return mixed
     */
    protected function getLog()
    {
        return $this->getDI()->get( 'logger' );
    }

    /**
     * @return ManagerInterface
     */
    protected function getModelManager()
    {
        return $this->getDI()->get( 'modelsManager' );
    }

    /**
     * @return Mysql
     */
    protected function getDb()
    {
        return $this->getDI()->get( 'db' );
    }

    /**
     *
     */
    protected function startTime()
    {
        // Set the start time
        $this->startTime = microtime( true );
        $this->info( "START ... " . date( 'Y-m-d H:i:s' ) );
    }

    /**
     *
     */
    protected function endTime()
    {
        // Set the start time
        $this->elapsed = microtime( true ) - $this->startTime;
        $this->info( "FINISH ... " . $this->elapsedTime() . " " . date( 'Y-m-d H:i:s' ) );
    }

    /**
     * @param $message
     */
    public function info( $message )
    {
        $this->_out( $message, 'Info' );
    }

    /**
     * @param int $precision
     *
     * @return string
     */
    private function elapsedTime( $precision = 2 )
    {
        $time = $this->elapsed;
        $a = [ 'decade' => 315576000, 'year' => 31557600, 'month' => 2629800, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'min' => 60, 'sec' => 1 ];
        $i = 0;
        $result = '';
        foreach ( $a as $k => $v ) {
            $$k = floor( $time / $v );
            if ( $$k ) $i++;
            $time = $i >= $precision ? 0 : $time - $$k * $v;
            $s = $$k > 1 ? 's' : '';
            $$k = $$k ? $$k . ' ' . $k . $s . ' ' : '';
            $result .= $$k;
        }

        return $result ? $result . 'ago' : '1 sec ago';
    }

    /**
     * @return Confirmation
     */
    protected function getHelperConfirmation()
    {
        return $this->helpers->load( 'confirm' );
    }

    /**
     * The main action for this command
     *
     * @Action
     * @return void
     */
    public function main()
    {
        $this->info("$this->name ....");
    }
    /**
     * @Action
     */
    public function help()
    {
        $this->getOutput()->writeln( '' );
        $this->getOutput()->writeln( ucwords( $this->name ) . " available list
---------------------------------------------------------------------------------------------
$this->description.
-----------------------s----------------------------------------------------------------------
" );
        $list = [];
        $annotions = new Annotation();
        $methods = $annotions->getMethods( get_called_class() );
        foreach ( $methods as $methodName => $collections ) {
            /** @var \Phalcon\Annotations\Collection $collections */
            foreach ( $collections as $collection ) {
                if ( $collection->getName() === 'Action' ) {
                    $list[$methodName] = $this->getColoredString( sprintf( " php cli %s:%s\n", $this->name, $methodName ), 'brown' );
                }
            }
        }

        ksort( $list );
        foreach ( $list as $cmd ) {
            $this->getOutput()->write( $cmd );
        }
        $this->getOutput()->writeln( '' );
    }
}
