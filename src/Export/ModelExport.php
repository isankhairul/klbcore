<?php namespace KlbV2\Core\Export;

use Exception;
use function array_combine;
use function fclose;
use function fgetcsv;
use function file_exists;
use function fopen;
use function fputcsv;
use function json_encode;
use function str_replace;
use function unlink;
use function usleep;

/**
 * Trait TraitExport
 *
 * @package KlbV2\Core\Export
 */
abstract class ModelExport implements ExportContract
{
    use TraitExport;
    /**
     * @var string
     */
    protected $model;
    /**
     * @var array
     */
    protected $columns = [];
    /**
     * @var int
     */
    private $duplicate = 0;
    /**
     * @var array
     */
    private $ignoredCause = [];
    /**
     * @var int
     */
    private $affected = 0;
    /**
     * @var int
     */
    private $ignore = 0;

    /**
     * @inheritDoc
     */
    public function handle()
    {

        $this->start();
        $count = $this->getQueueExport()->total;
        $path = $this->getQueueExport()->path;
        $name = $this->getQueueExport()->filename;
        /** Define Variable */
        $this->duplicate = 0;
        $this->ignore = 0;
        $this->affected = 0;
        foreach ( $this->ignoredCause as &$value ) {
            $value = 0;
        }
        $this->getTask()->info( "EXPORT-PROGRESS[$name] Process for: ($count) ..." );
        $handle = fopen( $path, 'r' );
        $filedFile = str_replace( '.csv', '_failed.csv', $path );
        $duplicateFile = str_replace( '.csv', '_duplicate.csv', $path );
        if ( file_exists( $filedFile ) ) {
            unlink( $filedFile );
        }
        $fp = fopen( $filedFile, 'w' );
        $duplicatFp = fopen( $duplicateFile, 'w' );
        $header = null;
        $i = 0;
        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            if ( null === $header ) {
                $header = $data;
                fputcsv( $fp, $data );
                fputcsv( $duplicatFp, $data );
            } else {
                $row = @array_combine( $header, $data );
                if ( false === $row ) {
                    fputcsv( $fp, $data );
                    $this->ignore++;
                    continue;
                }

                if ( false === $this->process( $row, $duplicatFp, $data ) ) {
                    fputcsv( $fp, $data );
                    $this->ignore++;
                }
                usleep( 10000 );
                $i++;
                $this->tick( $i );

            }
        }
        fclose( $fp );
        fclose( $handle );
        fclose( $duplicatFp );
        $this->finish( $i, [
            'duplicate'    => $this->duplicate,
            'ignore'       => $this->ignore,
            'ignoredCause' => $this->ignoredCause,
            'affected'     => $this->affected,
        ] );
        $name = $this->getQueueExport()->filename;
        $this->getTask()->info( "EXPORT-PROGRESS[$name] Done.\nIgnore: $this->ignore\tDuplicate: $this->duplicate\tAffected: $this->affected ..." );
        $this->getTask()->comment( "EXPORT-PROGRESS[$name] IGNORE: " . json_encode( $this->ignoredCause ) );
        $this->getTask()->info( "EXPORT-PROGRESS[$name] Done" );
    }

    /**
     * @param array $row
     * @param       $duplicatFp
     * @param       $dataOriginal
     *
     * @return bool
     * @throws Exception
     */
    abstract protected function process( array $row, $duplicatFp, $dataOriginal );

    /**
     * @inheritDoc
     */
    public function getName()
    {
        $path = explode( '\\', $this->model );

        return array_pop( $path );
    }
}
