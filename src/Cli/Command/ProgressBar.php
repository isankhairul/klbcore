<?php namespace KlbV2\Core\Cli\Command;

declare( ticks=1 );
pcntl_signal( SIGINT, function ( $signo ) {
    fwrite( STDOUT, "\n\033[?25h" );
    fwrite( STDERR, "\n\033[?25h" );
    exit;
} );

/**
 * Class ProgressBar
 *
 * @package KlbV2\Core\Cli\Command
 * Flexible ascii progress bar.
 *
 * ## Installation
 *
 * ```
 * Include the ProgressBar class
 * ```
 *
 * ## Usage
 *
 * First we create a `ProgressBar`, giving it a `format` string
 * as well as the `total`, telling the progress bar when it will
 * be considered complete. After that all we need to do is `tick()` appropriately.
 *
 * ```php
 * // example/basic.php
 *
 * $pg = new ProgressBar(1000);
 *
 * for ($i = 0; $i < 1000; $i++) {
 *    usleep(10000);
 *    $pg->tick();
 * }
 * ```
 *
 * You can also use `update(amount)` to set the current tick value instead of ticking each time there is an increment:
 *
 * ```php
 * // example/update.php
 * include('ProgressBar.php');
 *
 * $pg = new ProgressBar(1000);
 *
 * for ($i = 0; $i < 1000; $i++) {
 *    usleep(10000);
 *    $pg->update($i);
 * }
 * ```
 *
 * ### Options
 *
 * These are properties in the object you can read/set:
 *
 * - `symbolComplete` completion character defaulting to "="
 * - `symbolIncomplete` incomplete character defaulting to " "
 * - `throttle` minimum time between updates in seconds defaulting to 0.016
 * - `current` current tick
 * - `total` same value passed in when initialising
 * - `percent` (read only) current percentage completion
 * - `eta` (read only) estimate seconds until completion
 * - `rate` (read only) number of ticks per second
 * - `elapsed` (read only) seconds since initialisation
 *
 * ### Tokens
 *
 * These are tokens you can use in the format of your progress bar.
 *
 * - `:bar` the progress bar itself
 * - `:current` current tick number
 * - `:total` total ticks
 * - `:elapsed` time elapsed in seconds
 * - `:percent` completion percentage
 * - `:eta` estimated completion time in seconds
 * - `:rate` rate of ticks per second
 *
 * ### Format example
 * ```php
 * // example/format.php
 * // Full options
 * new ProgressBar(10, "Progress: [:bar] - :current/:total - :percent% - Elapsed::elapseds - ETA::etas -
 * Rate::rate/s");
 * ```
 *
 * ```php
 * // example/format_percent.php
 * // Just percentage plus the bar
 * new ProgressBar(10, ":bar :percent%");
 * ```
 *
 * ```php
 * // example/format_no_bar.php
 * // You don't even have to have a bar
 * new ProgressBar(10, "Look mum, no bar! :current/:total - :percent% - Elapsed::elapseds - ETA::etas - Rate::rate/s");
 * ```
 *
 * ### Interrupt example
 *
 * To display a message during progress bar execution, use `interrupt()`
 * ```php
 * // example/interrupt.php
 * $pg = new ProgressBar(1000);
 *
 * for ($i = 0; $i < 1000; $i++) {
 *    usleep(10000);
 *    if ($i % 100 == 0) {
 *        // Interupt every 100th tick
 *        $pg->interupt($i);
 *    }
 *    $pg->tick();
 * }
 * ```
 *
 * ### Symbols example
 *
 * To change the symbols used on the progress bar
 * ```php
 * // example/symbols.php
 * $pg = new ProgressBar(1000);
 * $pg->symbolComplete = "#";
 * $pg->symbolIncomplete = "-";
 * ```
 *
 * ### Throttle example
 *
 * If you are `ticking` several hundred or thousands of times per second, the `throttle` setting will be prevent the
 * progress bar from slowing down execution time too much, however, 16ms is quite optimistic, so you may wish to
 * increase it on slower machines.
 *
 * ```php
 * // example/throttle.php
 * $pg = new ProgressBar(1000);
 * // Set a 100 millisecond threshold
 * $pg->throttle = 0.1;
 * ```
 *
 * ## License
 *
 */
class ProgressBar
{
    const MOVE_START = "\033[1G";
    const HIDE_CURSOR = "\033[?25l";
    const SHOW_CURSOR = "\033[?25h";
    const ERASE_LINE = "\033[2K";
    // Available screen width
    public $throttle = 0.016;
    // Ouput stream. Usually STDOUT or STDERR
    public $symbolComplete = "=";
    // Output string format
    public $symbolIncomplete = " ";
    // Time the progress bar was initialised in seconds (with millisecond precision)
    public $current = 0;
    // Time since the last draw
    public $total = 1;
    // Pre-defined tokens in the format
    public $elapsed = 0;
    // Do not run drawBar more often than this (bypassed by interupt())
    public $percent = 0; // 16 ms
    // The symbol to denote completed parts of the bar
    public $eta = 0;
    // The symbol to denote incomplete parts of the bar
    public $rate = 0;
    // Current tick number
    private $width;
    // Maximum number of ticks
    private $stream;
    // Seconds elapsed
    private $format;
    // Current percentage complete
    private $startTime;
    // Estimated time until completion
    private $timeSinceLastCall;
    // Current rate
    private $ouputFind = [ ':current', ':total', ':elapsed', ':percent', ':eta', ':rate' ];

    public function __construct( $total = 1, $format = "Progress: [:bar] - :current/:total - :percent% - Elapsed::elapseds - ETA::etas - Rate::rate/s", $stream = STDERR )
    {
        // Get the terminal width
        $this->width = exec( "tput cols" );
        if ( !is_numeric( $this->width ) ) {
            // Default to 80 columns, mainly for windows users with no tput
            $this->width = 80;
        }
        $this->total = $total;
        $this->format = $format;
        $this->stream = $stream;
        // Initialise the display
        fwrite( $this->stream, self::HIDE_CURSOR );
        fwrite( $this->stream, self::MOVE_START );
        // Set the start time
        $this->startTime = microtime( true );
        $this->timeSinceLastCall = microtime( true );
        $this->drawBar();
    }

    /**
     * Does the actual drawing
     */
    private function drawBar()
    {
        $this->timeSinceLastCall = microtime( true );
        fwrite( $this->stream, self::MOVE_START );
        $replace = [
            $this->current,
            $this->total,
            $this->roundAndPadd( $this->elapsed ),
            $this->roundAndPadd( $this->percent ),
            $this->roundAndPadd( $this->eta ),
            $this->roundAndPadd( $this->rate ),
        ];
        $output = str_replace( $this->ouputFind, $replace, $this->format );
        if ( strpos( $output, ':bar' ) !== false ) {
            $availableSpace = $this->width - strlen( $output ) + 4;
            $done = $availableSpace * ( $this->percent / 100 );
            $left = $availableSpace - $done;
            $symbolComplete = $this->symbolComplete ? str_repeat( $this->symbolComplete, ( $done >= 0 ) ? $done : 0 ) : "";
            $symbolInComplete = $this->symbolIncomplete ? str_repeat( $this->symbolIncomplete, ( $left >= 0 ) ? $left : 0 ) : "";
            $output = str_replace( ':bar', $symbolComplete . $symbolInComplete, $output );
        }
        fwrite( $this->stream, $output );
    }

    /**
     * Adds 0 and space padding onto floats to ensure the format is fixed length nnn.nn
     */
    private function roundAndPadd( $input )
    {
        $parts = explode( ".", round( $input, 2 ) );
        $output = $parts[0];
        if ( isset( $parts[1] ) ) {
            $output .= "." . str_pad( $parts[1], 2, 0 );
        } else {
            $output .= ".00";
        }

        return str_pad( $output, 6, " ", STR_PAD_LEFT );
    }

    /**
     * Add $amount of ticks. Usually 1, but maybe different amounts if calling
     * this on a timer or other unstable method, like a file download.
     */
    public function tick( $amount = 1 )
    {
        $this->update( $this->current + $amount );
    }

    public function update( $amount )
    {
        $this->current = $amount;
        $this->elapsed = microtime( true ) - $this->startTime;
        $this->percent = $this->current / $this->total * 100;
        $this->rate = $this->current / $this->elapsed;
        $this->eta = ( $this->current ) ? ( $this->elapsed / $this->current * $this->total - $this->elapsed ) : false;
        $drawElapse = microtime( true ) - $this->timeSinceLastCall;
        if ( $drawElapse > $this->throttle ) {
            $this->drawBar();
        }
    }

    /**
     * Add a message on a newline before the progress bar
     */
    public function interupt( $message )
    {
        fwrite( $this->stream, self::MOVE_START );
        fwrite( $this->stream, self::ERASE_LINE );
        fwrite( $this->stream, $message . "\n" );
        $this->drawBar();
    }

    public function __destruct()
    {
        $this->end();
    }

    /**
     * Cleanup
     */
    public function end()
    {
        fwrite( $this->stream, "\n" . self::SHOW_CURSOR );
    }
}
