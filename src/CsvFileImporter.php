<?php namespace KlbV2\Core;


use Exception;
use Phalcon\Http\Request\FileInterface;
use RuntimeException;
use const STORAGE_PATH;

/**
 * Class CsvFileImporter
 *
 * @package App
 */
class CsvFileImporter
{
    /**
     * @param $csvImport
     * @param $csvImportId
     *
     * @return mixed
     * @throws Exception
     */
    public function import( $csvImport, $csvImportId )
    {
        $movedFile = $this->moveFile( $csvImport );

        $normalizedFile = $this->normalize( $movedFile );

        $csvHeader = $this->getCSVHeader( $movedFile );

        return $this->importFileContents( $normalizedFile, $csvImportId, $csvHeader );
    }

    /**
     * @param FileInterface $csvImport
     *
     * @return string
     * @throws RuntimeException
     */
    private function moveFile( FileInterface $csvImport )
    {
        $path = $this->getStorageTempPath();
        $target = $path . DIRECTORY_SEPARATOR . $csvImport->getName();
        if ( is_dir( $path ) ) {
            chmod( $path, 0755 );
        } else {
            mkdir( $path, 0755, true );
        }

        if ( !$csvImport->moveTo( $target ) ) {
            throw new RuntimeException( 'Unable to upload file: ' . $target );
        }

        return $target;
    }

    /**
     * @return string
     */
    private function getStorageTempPath()
    {
        return STORAGE_PATH . '/temp';
    }

    /**
     * @param $filePath
     *
     * @return mixed
     */
    private function normalize( $filePath )
    {
        $string = @file_get_contents( $filePath );

        if ( !$string ) {
            return $filePath;
        }

        $string = preg_replace( '~\r\n?~', "\n", $string );

        file_put_contents( $filePath, $string );

        return $filePath;
    }

    /**
     * @param $file
     *
     * @return bool|string
     * @throws Exception
     */
    private function getCSVHeader( $file )
    {
        if ( ( $file = fopen( $file, 'r' ) ) === false ) {
            throw new Exception( "The file ({$file}) could not be opened for reading" );
        }

        ini_set( 'auto_detect_line_endings', true );

        $header = fgets( $file );

        fclose( $file );

        return $header;
    }

    /**
     * @param $filePath
     * @param $csvImportId
     * @param $csvHeader
     *
     * @return mixed
     */
    private function importFileContents( $filePath, $csvImportId, $csvHeader )
    {
        /*$query = sprintf("LOAD DATA LOCAL INFILE '%s' INTO TABLE csv_rows
            LINES TERMINATED BY '\\n'
            FIELDS TERMINATED BY '\\n'
            IGNORE 1 LINES (`content`)
            SET csv_import_id = '%s', header = '%s'", addslashes($file_path), $csv_import_id, $csv_header);

        return DB::connection()->getpdo()->exec($query);*/
    }
}
